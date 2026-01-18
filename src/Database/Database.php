<?php
namespace Framework\Database;

use Framework\Database\Query\Query;
use Framework\Database\Type\Assign;
use Framework\Log\QueryLog;
use Framework\System\Config;
use Framework\Date\Timer;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Utils\Server;
use Framework\Utils\Strings;

use mysqli;
use mysqli_stmt;
use mysqli_sql_exception;

/**
 * The mysqli Database Wrapper
 */
class Database {

    private mysqli $mysqli;

    public string $host;
    public int    $port;
    public string $database;
    public string $username;
    public string $password;
    public string $charset;

    public bool $skipLog     = false;
    public bool $isConnected = false;


    /**
     * Creates a new Database instance
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $charset
     * @param int    $port     Optional.
     */
    public function __construct(
        string $host,
        string $database,
        string $username,
        string $password,
        string $charset,
        int $port = 3306,
    ) {
        $this->host     = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->charset  = $charset;
        $this->port     = $port;

        $this->connect();
    }

    /**
     * Closes the connection
     */
    public function __destruct() {
        $this->mysqli->close();
    }



    /**
     * Connects with the database
     * @return bool
     */
    public function connect(): bool {
        $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database, $this->port);
        if ($this->mysqli->connect_error !== null) {
            trigger_error("Connect Error ({$this->mysqli->connect_errno}) {$this->mysqli->connect_error}", E_USER_ERROR);
        } elseif ($this->database !== "") {
            $this->isConnected = true;
        }

        if ($this->charset !== "") {
            $this->mysqli->set_charset($this->charset);
        }
        return true;
    }

    /**
     * Sets the database to use
     * @param string $database
     * @return bool
     */
    public function setDatabase(string $database): bool {
        $this->database = $database;
        if ($this->mysqli->select_db($database)) {
            $this->isConnected = true;
            return true;
        }
        return false;
    }

    /**
     * Closes the connection
     * @return bool
     */
    public function close(): bool {
        return $this->mysqli->close();
    }



    /**
     * Executes the given expression
     * @param string $expression
     * @return bool
     */
    public function execute(string $expression): bool {
        $timer     = new Timer();
        $statement = $this->processQuery($expression, []);
        $this->processTime($timer, $expression, []);
        return $this->closeQuery($statement);
    }

    /**
     * Process the given expression
     * @param string        $expression
     * @param Query|mixed[] $params     Optional.
     * @return array<string,string|int|null>[]
     */
    public function queryData(string $expression, Query|array $params = []): array {
        $timer     = new Timer();
        $binds     = $params instanceof Query ? $params->params : $params;
        $statement = $this->processQuery($expression, $binds);
        $result    = $this->dynamicBindResults($statement);
        $this->processTime($timer, $expression, $binds);
        return $result;
    }

    /**
     * Process the given expression using a Query
     * @param string        $expression
     * @param Query|mixed[] $query      Optional.
     * @return array<string,string|int>[]
     */
    public function getData(string $expression, Query|array $query = []): array {
        $params = [];
        if ($query instanceof Query) {
            $expression .= $query->get(true);
            $params      = $query->params;
        } else {
            $params = $query;
        }

        $request = $this->queryData($expression, $params);
        $result  = [];

        foreach ($request as $index => $row) {
            foreach ($row as $key => $value) {
                if ($value === null) {
                    $result[$index][$key] = "";
                } else {
                    $result[$index][$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Process the given expression using a Query and returns a Dictionary
     * @param string     $expression
     * @param Query|null $query      Optional.
     * @return Dictionary
     */
    public function getDictionary(string $expression, ?Query $query = null): Dictionary {
        $request = $this->getData($expression, $query ?? []);
        return new Dictionary($request);
    }

    /**
     * Selects the given columns from a single table and returns the result as an array
     * @param string          $table
     * @param string[]|string $columns Optional.
     * @param Query|null      $query   Optional.
     * @return array<string,string|int>[]
     */
    public function getAll(string $table, array|string $columns = "*", ?Query $query = null): array {
        $selection  = Strings::join($columns, ", ");
        $expression = "SELECT $selection FROM `$table` ";
        return $this->getData($expression, $query ?? []);
    }

    /**
     * Selects the given column from a single table and returns a single value
     * @param string $table
     * @param string $column
     * @param Query  $query
     * @return string|int
     */
    public function getValue(string $table, string $column, Query $query): string|int {
        $request = $this->getAll($table, $column, $query->limit(1));

        if (isset($request[0][$column])) {
            return $request[0][$column];
        }
        return "";
    }



    /**
     * Returns true if the given Data is already in the given table
     * @param string $table
     * @param Query  $query
     * @return bool
     */
    public function exists(string $table, Query $query): bool {
        return $this->getTotal($table, $query) === 1;
    }

    /**
     * Returns the Count in the given table
     * @param string $table
     * @param Query  $query
     * @return int
     */
    public function getTotal(string $table, Query $query): int {
        $expression = "SELECT COUNT(*) AS cnt FROM `$table` " . $query->get();
        $request    = $this->queryData($expression, $query);

        if (isset($request[0]["cnt"])) {
            return Numbers::toInt($request[0]["cnt"]);
        }
        return 0;
    }



    /**
     * Replaces or Inserts the given content into the given table
     * @param string              $table
     * @param array<string,mixed> $fields
     * @param string              $method Optional.
     * @return int The Inserted ID or -1
     */
    public function insert(string $table, array $fields, string $method = "INSERT"): int {
        $bindParams  = [];
        $expression  = "$method INTO `$table` ";
        $expression .= $this->buildInsertHeader($fields);
        $expression .= $this->buildTableData($fields, $bindParams, true);
        $statement   = $this->processQuery($expression, $bindParams);

        if ($statement === null) {
            return 0;
        }
        $result = $statement->affected_rows > 0 ? (int)$statement->insert_id : -1;
        $statement->close();
        return $result;
    }

    /**
     * Replaces the given content into the given table
     * @param string              $table
     * @param array<string,mixed> $fields
     * @return int The Inserted ID or -1
     */
    public function replace(string $table, array $fields): int {
        return $this->insert($table, $fields, "REPLACE");
    }

    /**
     * Replaces or Inserts multiple rows
     * @param string    $table
     * @param array{}[] $fields
     * @param string    $method Optional.
     * @return bool
     */
    public function batch(string $table, array $fields, string $method = "REPLACE"): bool {
        if (!isset($fields[0])) {
            return false;
        }

        $bindParams  = [];
        $expression  = "$method INTO `$table` ";
        $expression .= $this->buildInsertHeader($fields[0]);

        $rows = [];
        foreach ($fields as $tableData) {
            $rows[] = $this->buildTableData($tableData, $bindParams, true);
        }

        $expression .= Strings::join($rows, ", ");
        $statement   = $this->processQuery($expression, $bindParams);
        return $this->closeQuery($statement);
    }

    /**
     * Updates the content of the database based on the query and given fields
     * @param string              $table
     * @param array<string,mixed> $fields
     * @param Query               $query
     * @return bool
     */
    public function update(string $table, array $fields, Query $query): bool {
        $bindParams  = [];
        $expression  = "UPDATE `$table` SET ";
        $expression .= $this->buildTableData($fields, $bindParams, false);
        $expression .= " " . $query->get();
        $bindParams  = array_merge($bindParams, $query->params);
        $statement   = $this->processQuery($expression, $bindParams);
        return $this->closeQuery($statement);
    }

    /**
     * Deletes from the given table
     * @param string $table
     * @param Query  $query
     * @return bool
     */
    public function delete(string $table, Query $query): bool {
        $expression = "DELETE FROM `$table` " . $query->get();
        $statement  = $this->processQuery($expression, $query->params);
        return $this->closeQuery($statement);
    }

    /**
     * Deletes from the given table
     * @param string $table
     * @return bool
     */
    public function deleteAll(string $table): bool {
        $expression = "DELETE FROM `$table`";
        $statement  = $this->processQuery($expression, []);
        return $this->closeQuery($statement);
    }

    /**
     * Truncates the given table
     * @param string $table
     * @return bool
     */
    public function truncate(string $table): bool {
        $expression = "TRUNCATE TABLE `$table`";
        $statement  = $this->processQuery($expression, []);
        return $this->closeQuery($statement);
    }



    /**
     * Escape harmful characters which might affect a query
     * @param string $str The string to escape.
     * @return string The escaped string.
     */
    public function escape(string $str): string {
        return $this->mysqli->real_escape_string($str);
    }

    /**
     * Process a mysqli query
     * @param string  $expression
     * @param mixed[] $bindParams Optional.
     * @return mysqli_stmt|null
     */
    private function processQuery(string $expression, array $bindParams = []): ?mysqli_stmt {
        if (!$this->isConnected) {
            return null;
        }

        $query = Strings::replace(trim($expression), "\n", "");
        try {
            $statement = $this->mysqli->prepare($expression);
            if ($statement === false) {
                return null;
            }

            if (count($bindParams) > 0) {
                $types  = "";
                $params = [];
                foreach ($bindParams as $value) {
                    // NOTE: For bind_params the first parameter is a string with the types of the following parameters:
                    //       i | corresponding variable has type `int`
                    //       d | corresponding variable has type `float`
                    //       s | corresponding variable has type `string`
                    //       b | corresponding variable is a blob and will be sent in packets
                    $types .= match (gettype($value)) {
                        "NULL", "string"     => "s",
                        "boolean", "integer" => "i",
                        "double"             => "d",
                        "resource"           => "b",
                        default              => "",
                    };

                    array_push($params, $value);
                }

                $values = $this->refValues($params);
                if (!$statement->bind_param($types, ...$values)) {
                    return null;
                }
            }

            if (!$statement->execute()) {
                $statement->close();
                return null;
            }
            return $statement;

        // Catch any MySQL Error and throw it to the Error Log
        } catch (mysqli_sql_exception $e) {
            $message = $e->getMessage();
            $params  = JSON::encode($bindParams);
            $error   = "MySQL Error: $message.\n\n$query\n\n$params";
            if (Server::isLocalHost()) {
                die($error);
            }
            trigger_error($error, E_USER_ERROR);
        }

        // @phpstan-ignore deadCode.unreachable
        return null;
    }

    /**
     * Takes care of prepared statements' bind_result method, when the number of variables to pass is unknown.
     * @param mysqli_stmt|null $statement
     * @return bool
     */
    private function closeQuery(?mysqli_stmt $statement): bool {
        if ($statement === null) {
            return false;
        }
        $result = $statement->affected_rows > 0;
        $statement->close();
        return $result;
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     * @param string        $expression
     * @param Query|mixed[] $query
     * @return string
     */
    public function interpolateQuery(string $expression, Query|array $query): string {
        $expression = Strings::replace(trim($expression), "\n", "");
        if ($query instanceof Query) {
            $expression .= $query->get(true);
            $params      = $query->params;
        } else {
            $params = $query;
        }

        $keys   = [];
        $values = [];

        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
            if (is_string($value)) {
                $values[] = "'$value'";
            } else {
                $values[] = Strings::toString($value);
            }
        }
        return Strings::replacePattern($expression, $keys, $values, 1);
    }

    /**
     * Creates a reference of each value
     * @param mixed[] $array
     * @return mixed[]
     */
    private function refValues(array $array): array {
        $total = count($array);
        $refs  = [];
        for ($i = 0; $i < $total; $i += 1) {
            $refs[$i] = & $array[$i];
        }
        return $refs;
    }

    /**
     * Takes care of prepared statements' bind_result method, when the number of variables to pass is unknown.
     * @param mysqli_stmt|null $statement
     * @return array<string,string|int|null>[]
     */
    private function dynamicBindResults(?mysqli_stmt $statement): array {
        if ($statement === null) {
            return [];
        }
        $parameters = [];
        $results    = [];
        $meta       = $statement->result_metadata();

        // If $meta is false yet sqlstate is true, there's no sql error but the query is
        // most likely an update/insert/delete which doesn't produce any results
        if ($meta === false) {
            return [];
        }

        $row   = [];
        $field = $meta->fetch_field();
        while ($field !== false) {
            $row[$field->name] = null;
            $parameters[]      = & $row[$field->name];
            $field             = $meta->fetch_field();
        }
        $statement->bind_result(...$parameters);

        $statement->store_result();
        while ($statement->fetch() === true) {
            $x = [];
            foreach ($row as $key => $val) {
                $key    = Strings::toString($key);
                $string = Strings::toString($val);
                if (ctype_digit($string) && strrpos($string, "0", -strlen($string)) === false) {
                    $x[$key] = (int)$val;
                } else {
                    $x[$key] = $val;
                }
            }
            $results[] = $x;
        }
        $statement->free_result();

        return $results;
    }

    /**
     * Builds the query for inserting or updating
     * @param array<string,mixed> $fields
     * @return string
     */
    private function buildInsertHeader(array $fields): string {
        return "(`" . Strings::joinKeys($fields, "`, `") . "`) VALUES ";
    }

    /**
     * Process the table data for building the query for inserting or updating
     * @param array<string,mixed> $fields
     * @param mixed[]             $bindParams
     * @param bool                $isInsert
     * @return string
     */
    private function buildTableData(array $fields, array &$bindParams, bool $isInsert): string {
        $result = "";
        if ($isInsert) {
             $result .= "(";
        }

        foreach ($fields as $column => $value) {
            if (!$isInsert) {
                $result .= "`$column` = ";
            }

            if ($value instanceof Assign) {
                switch ($value->type) {
                case "equal":
                    $result .= "`{$value->column}`, ";
                    break;
                case "not":
                    $result .= "!`{$value->column}`, ";
                    break;
                case "increase":
                    $result .= "`$column` + ?, ";
                    break;
                case "decrease":
                    $result .= "`$column` - ?, ";
                    break;
                case "uuid":
                    $result .= "UUID(), ";
                    break;
                case "encrypt":
                    $result .= "AES_ENCRYPT(?, ?), ";
                    break;
                case "replace":
                    $result .= "REPLACE(`$column`, ?, ?), ";
                    break;
                case "greatest":
                    $result .= "GREATEST(`$column`, ?), ";
                    break;
                default:
                    die("Wrong operation");
                }
                foreach ($value->params as $param) {
                    $bindParams[] = $param;
                }
            } else {
                $result      .= "?, ";
                $bindParams[] = $value;
            }
        }

        $result = rtrim($result, ", ");
        if ($isInsert) {
            $result .= ")";
        }
        return $result;
    }

    /**
     * Process a elapsed time and saves it if it last more than 5 seconds
     * @param Timer   $timer
     * @param string  $expression
     * @param mixed[] $params
     * @return bool
     */
    protected function processTime(Timer $timer, string $expression, array $params): bool {
        $logTime = Config::getDbLogTime();
        $time    = $timer->getElapsedSeconds();
        if ($logTime === 0 || $time < $logTime || $this->skipLog) {
            return false;
        }

        $this->skipLog = true;
        $result = QueryLog::createOrEdit($time, $expression, $params);
        $this->skipLog = false;
        return $result;
    }



    /**
     * Returns an array with all the tables
     * @param string[]|null $filter Optional.
     * @return string[]
     */
    public function getTables(?array $filter = null): array {
        $request   = $this->queryData("SHOW TABLES FROM `{$this->database}`");
        $hasFilter = $filter !== null;
        $result    = [];

        foreach ($request as $row) {
            foreach ($row as $value) {
                $tableName = Strings::toString($value);
                if (($hasFilter && !Arrays::contains($filter, $tableName)) || !$hasFilter) {
                    $result[] = $tableName;
                }
            }
        }
        return $result;
    }

    /**
     * Returns an array with all the tables
     * @param string $tableName
     * @return bool
     */
    public function hasTable(string $tableName): bool {
        $request = $this->queryData("SHOW TABLES LIKE '$tableName'");
        return !Arrays::isEmpty($request);
    }

    /**
     * Returns the Table Primary Keys
     * @param string $tableName
     * @return string[]
     */
    public function getPrimaryKeys(string $tableName): array {
        $request = $this->getDictionary("SHOW KEYS FROM `$tableName`");
        $result  = [];

        foreach ($request as $row) {
            if ($row->getString("Key_name") === "PRIMARY") {
                $result[] = $row->getString("Column_name");
            }
        }
        return $result;
    }

    /**
     * Returns the Table Primary Key with Auto Increment
     * @param string $tableName
     * @return string
     */
    public function getAutoIncrement(string $tableName): string {
        $request = $this->getDictionary("
            SELECT *
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = '$tableName'
                AND DATA_TYPE = 'int'
                AND COLUMN_DEFAULT IS NULL
                AND IS_NULLABLE = 'NO'
                AND EXTRA like '%auto_increment%'
            LIMIT 1
        ")->getFirst();
        return $request->getString("COLUMN_NAME");
    }

    /**
     * Returns the Table Keys
     * @param string $tableName
     * @return array<string,mixed>[]
     */
    public function getTableKeys(string $tableName): array {
        return $this->queryData("SHOW INDEXES IN `$tableName`");
    }

    /**
     * Returns the Table Fields
     * @param string $tableName
     * @return array<string,string>
     */
    public function getTableFields(string $tableName): array {
        $request = $this->queryData("SHOW FIELDS FROM `$tableName`");
        $result  = [];
        foreach ($request as $row) {
            $field = Strings::toString($row["Field"] ?? "");
            $result[$field] = $this->parseColumnType($row);
        }
        return $result;
    }



    /**
     * Returns true if a Table exists
     * @param string $tableName
     * @return bool
     */
    public function tableExists(string $tableName): bool {
        if ($tableName === "") {
            return false;
        }
        $request = $this->getDictionary("SHOW TABLES LIKE '$tableName'");
        return $request->isNotEmpty();
    }

    /**
     * Returns true if a Table exists
     * @param string $tableName
     * @return bool
     */
    public function tableIsEmpty(string $tableName): bool {
        $request = $this->getDictionary("
            SELECT COUNT(*) AS count
            FROM `$tableName`
        ")->getFirst();
        return $request->getInt("count") === 0;
    }

    /**
     * Creates a Table
     * @param string               $tableName
     * @param array<string,string> $fields
     * @param string[]             $primary
     * @param string[]             $keys
     * @return string
     */
    public function createTable(string $tableName, array $fields, array $primary, array $keys): string {
        $charset = $this->charset !== "" ? $this->charset : "utf8";
        $sql     = "CREATE TABLE `$tableName` (\n";

        foreach ($fields as $key => $type) {
            $sql .= "  `$key` $type,\n";
        }

        $sql .= "  PRIMARY KEY (`" . Strings::join($primary, "`, `") . "`)";
        foreach ($keys as $key) {
            $sql .= ",\n  KEY `$key` (`$key`)";
        }
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=$charset";

        $this->execute($sql);
        return $sql;
    }

    /**
     * Renames a Table
     * @param string $oldTableName
     * @param string $newTableName
     * @return string
     */
    public function renameTable(string $oldTableName, string $newTableName): string {
        $sql = "ALTER TABLE `$oldTableName` RENAME `$newTableName`";
        $this->execute($sql);
        return $sql;
    }

    /**
     * Deletes a Table
     * @param string $tableName
     * @return string
     */
    public function deleteTable(string $tableName): string {
        $sql = "DROP TABLE `$tableName`";
        $this->execute($sql);
        return $sql;
    }

    /**
     * Returns true if a Column exists
     * @param string $tableName
     * @param string $column
     * @return bool
     */
    public function columnExists(string $tableName, string $column): bool {
        $type = $this->getColumnType($tableName, $column);
        return $type !== "";
    }

    /**
     * Returns the Column Type
     * @param string $tableName
     * @param string $column
     * @return string
     */
    public function getColumnType(string $tableName, string $column): string {
        $result = $this->queryData("SHOW COLUMNS FROM `$tableName` LIKE '$column'");
        return isset($result[0]) ? $this->parseColumnType($result[0]) : "";
    }

    /**
     * Parses the Column Type
     * @param array<string,mixed> $row
     * @return string
     */
    private function parseColumnType(array $row): string {
        $data    = new Dictionary($row);
        $type    = $data->getString("Type");
        $null    = $data->getString("Null");
        $default = $data->getString("Default");
        $extra   = $data->getString("Extra");

        $result = $type;
        if ($null === "NO") {
            $result .= " NOT NULL";
        } else {
            $result .= " NULL";
        }
        if (isset($row["Default"]) && (is_string($row["Default"]) || is_int($row["Default"]))) {
            $result .= " DEFAULT '$default'";
        }
        if ($extra !== "") {
            $result .= " " . Strings::toUpperCase($extra);
        }
        return $result;
    }

    /**
     * Renames a Column from the Table
     * @param string $tableName
     * @param string $column
     * @param string $type
     * @param string $afterColumn Optional.
     * @return string
     */
    public function addColumn(string $tableName, string $column, string $type, string $afterColumn = ""): string {
        $sql  = "ALTER TABLE `$tableName` ADD COLUMN `$column` $type ";
        $sql .= $afterColumn !== "" ? "AFTER `$afterColumn`" : "FIRST";
        $this->execute($sql);
        return $sql;
    }

    /**
     * Renames a Column from the Table
     * @param string $tableName
     * @param string $oldColumn
     * @param string $newColumn
     * @param string $type      Optional.
     * @return string
     */
    public function renameColumn(string $tableName, string $oldColumn, string $newColumn, string $type = ""): string {
        if ($type === "") {
            $sql = "ALTER TABLE `$tableName` RENAME COLUMN `$oldColumn` TO `$newColumn`";
        } else {
            $sql = "ALTER TABLE `$tableName` CHANGE `$oldColumn` `$newColumn` $type";
        }
        $this->execute($sql);
        return $sql;
    }

    /**
     * Updates a Column from the Table
     * @param string $tableName
     * @param string $column
     * @param string $type
     * @param string $afterColumn Optional.
     * @return string
     */
    public function updateColumn(string $tableName, string $column, string $type, string $afterColumn = ""): string {
        $sql  = "ALTER TABLE `$tableName` MODIFY COLUMN `$column` $type ";
        $sql .= $afterColumn !== "" ? "AFTER `$afterColumn`" : "FIRST";
        $this->execute($sql);
        return $sql;
    }

    /**
     * Deletes a Column from the Table
     * @param string $tableName
     * @param string $column
     * @param bool   $execute   Optional.
     * @return string
     */
    public function deleteColumn(string $tableName, string $column, bool $execute = true): string {
        $sql = "ALTER TABLE `$tableName` DROP COLUMN `$column`";
        if ($execute) {
            $this->execute($sql);
        }
        return $sql;
    }

    /**
     * Updates the Primary Keys on the Table
     * @param string   $tableName
     * @param string[] $primary
     * @return string
     */
    public function updatePrimary(string $tableName, array $primary): string {
        $sql  = "ALTER TABLE `$tableName` DROP PRIMARY KEY, ";
        $sql .= "ADD PRIMARY KEY (" . Strings::join($primary, ", ") . ")";
        $this->execute($sql);
        return $sql;
    }

    /**
     * Drops the Primary Keys on the Table
     * @param string $tableName
     * @return string
     */
    public function dropPrimary(string $tableName): string {
        $sql = "ALTER TABLE `$tableName` DROP PRIMARY KEY";
        $this->execute($sql);
        return $sql;
    }

    /**
     * Creates an Index on the Table
     * @param string $tableName
     * @param string $key
     * @return string
     */
    public function createIndex(string $tableName, string $key): string {
        $sql = "CREATE INDEX `$key` ON `$tableName`(`$key`)";
        $this->execute($sql);
        return $sql;
    }



    /**
     * Dumps the entire database
     * @param string[]      $filter Optional.
     * @param resource|null $fp     Optional.
     * @return bool
     */
    public function dump(array $filter = [], mixed $fp = null): bool {
        $crlf = "\r\n";

        // SQL Dump Header
        $this->write(
            $fp,
            "# ========================================================= $crlf" .
            "# $crlf" .
            "# Database dump of tables in `{$this->database}` $crlf" .
            "# " . date("d M Y, H:i:s") . $crlf .
            "# $crlf" .
            "# ========================================================= $crlf" .
            $crlf
        );

        // Get all tables in the database
        $tables = $this->getTables($filter);

        // Dump each table
        foreach ($tables as $table) {
            $this->write(
                $fp,
                $crlf .
                "#$crlf" .
                "# Table structure for table `$table` $crlf" .
                "#$crlf" .
                $crlf .
                "DROP TABLE IF EXISTS `$table`; $crlf" .
                $crlf .
                $this->getTableSQLData($table) . "; $crlf"
            );

            // Are there any rows in this table?
            $rows = $this->getTableContent($table);
            if ($rows !== "") {
                $this->write(
                    $fp,
                    $crlf .
                    "# $crlf" .
                    "# Dumping data in `$table` $crlf" .
                    "# $crlf" .
                    $crlf .
                    $rows .
                    "# -------------------------------------------------------- $crlf"
                );
            }
        }
        $this->write($fp, "$crlf # Done $crlf");
        return true;
    }

    /**
     * Writes the content in a file or prints them in the screen
     * @param resource|null $fp
     * @param string        $content
     * @return bool
     */
    private function write(mixed $fp, string $content): bool {
        if ($fp !== null) {
            fwrite($fp, $content);
        } else {
            print($content);
        }
        return true;
    }

    /**
     * Returns the table's SQL data
     * @param string $tableName
     * @return string
     */
    private function getTableSQLData(string $tableName): string {
        $crlf    = "\r\n";
        $result  = "CREATE TABLE `$tableName` ($crlf";
        $request = $this->queryData("SHOW FIELDS FROM `$tableName`");

        foreach ($request as $row) {
            $data    = new Dictionary($row);
            $field   = $data->getString("Field");
            $type    = $data->getString("Type");
            $null    = $data->getString("Null");
            $default = $data->getString("Default");
            $extra   = $data->getString("Extra");

            // Make the CREATE for this column.
            $result .= "  $field $type" . ($null !== "YES" ? " NOT NULL" : "");

            // Add a default...?
            if ($default !== "") {
                // Make a special case of auto-timestamp.
                if ($default === "CURRENT_TIMESTAMP") {
                    $result .= " /*!40102 NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP */";
                } elseif (is_numeric($default)) {
                    $result .= " default $default";
                } else {
                    $result .= " default '" . $this->escape($default) . "'";
                }
            }

            // And now any extra information. (such as auto_increment.)
            if ($extra !== "") {
                $result .= " $extra";
            }
            $result .= ",$crlf";
        }

        // Take off the last comma.
        $result = substr($result, 0, -strlen($crlf) - 1);

        // Find the keys.
        $request = $this->queryData("SHOW KEYS FROM `$tableName`");
        $indexes = [];

        foreach ($request as $row) {
            $data       = new Dictionary($row);
            $keyName    = $data->getString("Key_name");
            $columnName = $data->getString("Column_name");
            $subPart    = $data->getString("Sub_part");
            $seqIndex   = $data->getString("Seq_in_index");

            // IS this a primary key, unique index, or regular index?
            if ($keyName === "PRIMARY") {
                $row["Key_name"] = "PRIMARY KEY";
            } elseif (!isset($row["Non_unique"])) {
                $row["Key_name"] = "UNIQUE $keyName";
            } elseif ($data->getString("Comment") === "FULLTEXT" || $data->getString("Index_type") === "FULLTEXT") {
                $row["Key_name"] = "FULLTEXT $keyName";
            } else {
                $row["Key_name"] = "KEY $keyName";
            }

            // Is this the first column in the index?
            if (!isset($indexes[$keyName])) {
                $indexes[$keyName] = [];
            }

            // A sub part, like only indexing 15 characters of a varchar.
            if ($subPart !== "") {
                $indexes[$keyName][$seqIndex] = "$columnName($subPart)";
            } else {
                $indexes[$keyName][$seqIndex] = $columnName;
            }
        }

        // Build the CREATEs for the keys.
        foreach ($indexes as $keyName => $columns) {
            ksort($columns);
            $result .= ",$crlf $keyName (" . Strings::join($columns, ", ") . ")";
        }

        // Now just get the comment and type...
        $request = $this->queryData("
            SHOW TABLE STATUS
            LIKE '" . strtr($tableName, [ '_' => '\\_', '%' => '\\%' ]) . "'
        ");
        if (isset($request[0])) {
            $data    = new Dictionary($request[0]);
            $type    = $data->getString("Type");
            $engine  = $data->getString("Engine");
            $comment = $data->getString("Comment");

            $result .= $crlf . ") ENGINE=" . ($type !== "" ? $type : $engine);
            $result .= $comment !== "" ? " COMMENT='$comment'" : "";
        }

        return $result;
    }

    /**
     * Returns the table content
     * @param string $tableName
     * @return string
     */
    private function getTableContent(string $tableName): string {
        $crlf   = "\r\n";
        $result = "";
        $start  = 0;

        do {
            $request = $this->queryData("SELECT /*!40001 SQL_NO_CACHE */ * FROM `$tableName` LIMIT $start, 250");
            $start  += 250;

            if (!Arrays::isEmpty($request) && isset($request[0])) {
                $result .= "INSERT INTO `$tableName` $crlf\t(`" . Strings::joinKeys($request[0], "`, `") . "`) $crlf VALUES ";

                foreach ($request as $index => $row) {
                    $fieldList = [];
                    foreach ($row as $value) {
                        // Try to figure out the type of each field. (NULL, number, or 'string'.)
                        if (is_int($value)) {
                            $fieldList[] = $value;
                        } elseif (is_string($value)) {
                            $fieldList[] = "'" . $this->escape($value) . "'";
                        } else {
                            $fieldList[] = "NULL";
                        }
                    }
                    $result .= "(" . Strings::join($fieldList, ", ") . ")";

                    if ($index < count($request) - 1) {
                        $result .= ",$crlf\t";
                    }
                }
                $result .= ";$crlf";
            }
        } while (!Arrays::isEmpty($request));

        return $result;
    }
}
