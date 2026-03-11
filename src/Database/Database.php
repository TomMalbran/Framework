<?php
namespace Framework\Database;

use Framework\Log\QueryLog;
use Framework\System\Config;
use Framework\Date\Timer;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Framework\Utils\Server;
use Framework\Utils\Strings;

use mysqli;
use mysqli_stmt;
use mysqli_sql_exception;

/**
 * The mysqli Database Wrapper
 */
class Database {

    private static ?Database $db = null;


    private mysqli $mysqli;

    private string $host;
    private int    $port;
    private string $database;
    private string $username;
    private string $password;
    private string $charset;

    private bool $skipLog       = false;
    private bool $isConnected   = false;
    private bool $isLastSuccess = false;
    private int  $lastInsertID  = 0;


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
     * Returns the Database instance
     * @param Database|null $instance Optional.
     * @return Database
     */
    public static function getInstance(?Database $instance = null): Database {
        if ($instance !== null) {
            return $instance;
        }
        if (self::$db === null) {
            self::$db = new Database(
                Config::getDbHost(),
                Config::getDbDatabase(),
                Config::getDbUsername(),
                Config::getDbPassword(),
                Config::getDbCharset(),
                Config::getDbPort(),
            );
        }
        return self::$db;
    }



    /**
     * Connects with the database
     * @return void
     */
    public function connect(): void {
        $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database, $this->port);
        if ($this->mysqli->connect_error !== null) {
            $errno = $this->mysqli->connect_errno;
            $error = $this->mysqli->connect_error;
            trigger_error("Connect Error ($errno) $error", E_USER_ERROR);
        } elseif ($this->database !== "") {
            $this->isConnected = true;
        }

        if ($this->charset !== "") {
            $this->mysqli->set_charset($this->charset);
        }
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
     * @param string                 $expression
     * @param list<float|int|string> $bindings   Optional.
     * @return bool
     */
    public function execute(string $expression, array $bindings = []): bool {
        $timer     = new Timer();
        $statement = $this->processQuery($expression, $bindings);
        $this->processTime($timer, $expression, $bindings);
        return $this->closeQuery($statement);
    }

    /**
     * Executes the given expression and returns the result as an array
     * @param string                 $expression
     * @param list<float|int|string> $bindings   Optional.
     * @return list<array<string,int|string|null>>
     */
    private function queryData(string $expression, array $bindings = []): array {
        $timer     = new Timer();
        $statement = $this->processQuery($expression, $bindings);
        $result    = $this->dynamicBindResults($statement);
        $this->processTime($timer, $expression, $bindings);
        return $result;
    }

    /**
     * Executes the given expression and returns the result as a Dictionary
     * @param string                 $expression
     * @param list<float|int|string> $bindings   Optional.
     * @return Dictionary
     */
    public function getData(string $expression, array $bindings = []): Dictionary {
        $request = $this->queryData($expression, $bindings);
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

        return new Dictionary($result);
    }



    /**
     * Returns the ID generated by an INSERT or UPDATE query on a table with an AUTO_INCREMENT
     * @return int
     */
    public function getInsertID(): int {
        return $this->lastInsertID;
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
     * @param string      $expression
     * @param list<mixed> $bindings   Optional.
     * @return mysqli_stmt|null
     */
    private function processQuery(string $expression, array $bindings = []): ?mysqli_stmt {
        if (!$this->isConnected) {
            return null;
        }

        $query = Strings::replace(trim($expression), "\n", "");
        try {
            $statement = $this->mysqli->prepare($expression);
            if ($statement === false) {
                return null;
            }

            if (count($bindings) > 0) {
                $types  = "";
                $params = [];
                foreach ($bindings as $value) {
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
                    $params[] = $value;
                }

                $total  = count($params);
                $values = [];
                for ($i = 0; $i < $total; $i += 1) {
                    $values[$i] = & $params[$i];
                }

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
            $params  = JSON::encode($bindings);
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
            $this->isLastSuccess = false;
            $this->lastInsertID  = 0;
            return false;
        }

        $this->isLastSuccess = $statement->affected_rows > 0;
        $this->lastInsertID  = $this->isLastSuccess ? (int)$statement->insert_id : 0;

        $statement->close();
        return $this->isLastSuccess;
    }

    /**
     * Takes care of prepared statements' bind_result method, when the number of variables to pass is unknown.
     * @param mysqli_stmt|null $statement
     * @return list<array<string,int|string|null>>
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
     * Process a elapsed time and saves it if it last more than 5 seconds
     * @param Timer       $timer
     * @param string      $expression
     * @param list<mixed> $params
     * @return void
     */
    protected function processTime(Timer $timer, string $expression, array $params): void {
        $logTime = Config::getDbLogTime();
        $time    = $timer->getElapsedSeconds();
        if ($logTime === 0 || $time < $logTime || $this->skipLog) {
            return;
        }

        $this->skipLog = true;
        QueryLog::createOrEdit($time, $expression, $params);
        $this->skipLog = false;
    }



    /**
     * Returns an array with all the tables
     * @param list<string>|null $filter Optional.
     * @return list<string>
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
     * Returns the Table Primary Keys
     * @param string $tableName
     * @return list<string>
     */
    public function getPrimaryKeys(string $tableName): array {
        $request = $this->getData("SHOW KEYS FROM `$tableName`");
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
        $request = $this->getData("
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
     * @return list<array<string,mixed>>
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
        $request = $this->getData("SHOW TABLES LIKE '$tableName'");
        return $request->isNotEmpty();
    }

    /**
     * Returns true if a Table exists
     * @param string $tableName
     * @return bool
     */
    public function tableIsEmpty(string $tableName): bool {
        $request = $this->getData("
            SELECT COUNT(*) AS count
            FROM `$tableName`
        ")->getFirst();
        return $request->getInt("count") === 0;
    }

    /**
     * Creates a Table
     * @param string               $tableName
     * @param array<string,string> $fields
     * @param list<string>         $primary
     * @param list<string>         $keys
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
     * @param string       $tableName
     * @param list<string> $primary
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
}
