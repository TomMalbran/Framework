<?php
namespace Framework\Schema;

use Framework\Schema\Query;
use Framework\Utils\Strings;
use Framework\Utils\Utils;
use mysqli;

/**
 * The mysqli Database Wrapper
 */
class Database {

    private $mysqli;
    
    public $host;
    public $username;
    public $password;
    public $database;
    public $prefix;
    public $email;
    public $persist;
        
    
    /**
     * Creates a new Database instance
     * @param object  $config  Object[
     *    host      Usually localhost
     *    username  Database username
     *    password  Database password
     *    database  Database name
     *    prefix    Table prefix
     *    email     Dump email
     * ].
     * @param boolean $persist True to persist the connection. Defaults to false.
     */
    public function __construct($config, $persist = false) {
        $this->host     = $config->host;
        $this->username = $config->username;
        $this->password = $config->password;
        $this->database = $config->database;
        $this->prefix   = $config->prefix;
        $this->email    = $config->email;
        $this->persist  = $persist;
        
        $this->connect();
    }
    
    /**
     * Closes the connection
     */
    public function __destruct() {
        if (!empty($this->mysqli) && !$this->persist) {
            $this->mysqli->close();
        }
    }
    
    
    
    /**
     * Connects with the database
     * @return void
     */
    public function connect() {
        $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->mysqli->connect_error) {
            die("Connect Error ({$this->mysqli->connect_errno}) {$this->mysqli->connect_error}");
        }
    }
    
    /**
     * Closes the connection
     * @return void
     */
    public function close() {
        $this->mysqli->close();
    }
    
    
    
    /**
     * Process the given query
     * @param string $expression
     * @param mixed  $params     Optional.
     * @return array
     */
    public function query($expression, $params = []) {
        $binds     = $params instanceof Query ? $params->params : $params;
        $statement = $this->processQuery($expression, $binds);
        return $this->dynamicBindResults($statement);
    }
    
    /**
     * Selects the given columns from a single table and returns the result as an array
     * @param string          $table
     * @param string|string[] $columns Optional.
     * @param Query           $query   Optional.
     * @return array
     */
    public function getAll($table, $columns = "*", Query $query = null) {
        $selection  = Strings::join($columns, ", ");
        $expression = "SELECT $selection FROM {dbPrefix}$table ";
        $params     = [];

        if (!empty($query)) {
            $expression .= $query->get();
            $params      = $query->params;
        }
        return $this->query($expression, $params);
    }
    
    /**
     * Selects the given column from a single table and returns a single value
     * @param string $table
     * @param string $column
     * @param Query  $query
     * @return string
     */
    public function getValue($table, $column, Query $query) {
        $request = $this->getAll($table, $column, $query->limit(1));
        
        if (isset($request[0][$column])) {
            return $request[0][$column];
        }
        return "";
    }
    
    /**
     * Selects the given columns from a single table and returns the first row
     * @param string          $table
     * @param string|string[] $columns
     * @param Query           $query
     * @return array
     */
    public function getRow($table, $columns, Query $query) {
        $request = $this->getAll($table, $columns, $query->limit(1));
        
        if (isset($request[0])) {
            return $request[0];
        }
        return "";
    }
    
    /**
     * Selects the given column from a single table and returns the entire column
     * @param string $table
     * @param string $column
     * @param Query  $query
     * @return string[]
     */
    public function getColumn($table, $column, Query $query) {
        $request = $this->getAll($table, $column, $query);
        $result  = [];
        
        foreach ($request as $row) {
            if (!empty($row[$column]) && !in_array($row[$column], $result)) {
                $result[] = $row[$column];
            }
        }
        return $result;
    }
    


    /**
     * Returns true if the given Data is already in the given table
     * @param string $table
     * @param Query  $query
     * @return boolean
     */
    public function exists($table, Query $query) {
        return $this->getTotal($table, $query) == 1;
    }
    
    /**
     * Returns the Count in the given table
     * @param string $table
     * @param Query  $query
     * @return integer
     */
    public function getTotal($table, Query $query) {
        $expression = "SELECT COUNT(*) AS cnt FROM `{dbPrefix}$table` " . $query->get();
        $request    = $this->query($expression, $query);
        
        if (isset($request[0]["cnt"])) {
            return (int)$request[0]["cnt"];
        }
        return 0;
    }
    
    /**
     * Returns the Sums of the given column in the given table
     * @param string $table
     * @param string $column
     * @param Query  $query
     * @return integer
     */
    public function getSum($table, $column, Query $query) {
        $expression = "SELECT COALESCE(SUM($column), 0) AS sum FROM `{dbPrefix}$table` "  . $query->get();
        $request    = $this->query($expression, $query);
        
        if (isset($request[0]["sum"])) {
            return (int)$request[0]["sum"];
        }
        return 0;
    }
    
    

    /**
     * Replaces or Inserts the given content into the given table
     * @param string $table
     * @param array  $fields
     * @param string $method Optional.
     * @return integer The Inserted ID or -1
     */
    public function insert($table, array $fields, $method = "INSERT") {
        $bindParams  = [];
        $expression  = "$method INTO `{dbPrefix}$table` ";
        $expression .= $this->buildInsertHeader($fields);
        $expression .= $this->buildTableData($fields, $bindParams, true);
        $statement   = $this->processQuery($expression, $bindParams);
        
        return ($statement->affected_rows > 0 ? $statement->insert_id : -1);
    }
    
    /**
     * Replaces or Inserts multiple rows
     * @param string $table
     * @param array  $fields
     * @param string $method Optional.
     * @return boolean
     */
    public function batch($table, array $fields, $method = "REPLACE") {
        $bindParams  = [];
        $expression  = "$method INTO `{dbPrefix}$table` ";
        $expression .= $this->buildInsertHeader($fields[0]);
        
        $rows = [];
        foreach ($fields as $tableData) {
            $rows[] = $this->buildTableData($tableData, $bindParams, true);
        }
        
        $expression .= Strings::join($rows, ", ");
        $statement   = $this->processQuery($expression, $bindParams);
        
        return ($statement->affected_rows > 0);
    }

    
    
    /**
     * Updates the content of the database based on the query and given fields
     * @param string $table
     * @param array  $fields
     * @param Query  $query
     * @return boolean
     */
    public function update($table, array $fields, Query $query) {
        $bindParams  = [];
        $expression  = "UPDATE `{dbPrefix}$table` SET ";
        $expression .= $this->buildTableData($fields, $bindParams, false);
        $expression .= " " . $query->get();
        $bindParams  = array_merge($bindParams, $query->params);
        $statement   = $this->processQuery($expression, $bindParams);
        
        return ($statement->affected_rows > 0);
    }
    
    /**
     * Updates a single value increasing it by the given amount
     * @param string  $table
     * @param string  $column
     * @param integer $amount
     * @param Query   $query
     * @return boolean
     */
    public function increase($table, $column, $amount, Query $query) {
        return $this->update($table, [ $column => Query::inc($amount) ], $query);
    }

    
    
    /**
     * Deletes from the given table
     * @param string $table
     * @param Query  $query
     * @return boolean
     */
    public function delete($table, Query $query) {
        $expression = "DELETE FROM `{dbPrefix}$table` " . $query->get();
        $statement  = $this->processQuery($expression, $query->params);
        
        return ($statement->affected_rows > 0);
    }

    /**
     * Deletes from the given table
     * @param string $table
     * @return boolean
     */
    public function deleteAll($table) {
        $expression = "DELETE FROM `{dbPrefix}$table`";
        $statement  = $this->processQuery($expression, []);
        
        return ($statement->affected_rows > 0);
    }
    
    

    /**
     * Escape harmful characters which might affect a query
     * @param string $str The string to escape.
     * @return string The escaped string.
     */
    public function escape($str) {
        return $this->mysqli->real_escape_string($str);
    }
    
    /**
     * Process a mysqli query
     * @param string $expression
     * @param array  $bindParams Optional.
     * @return mixed
     */
    private function processQuery($expression, array $bindParams = []) {
        $expression = Strings::replace(trim($expression), "{dbPrefix}", $this->prefix);
        $query      = Strings::replace($expression, "\n", "");
        $statement  = $this->mysqli->prepare($expression);
        
        if (!$statement) {
            trigger_error("Problem preparing query: {$this->mysqli->error} ($query)", E_USER_ERROR);
        }
        
        if (is_array($bindParams) && !empty($bindParams)) {
            $params = [ "" ]; // Create the empty 0 index
            foreach ($bindParams as $value) {
                $params[0] .= $this->determineType($value);
                array_push($params, $value);
            }
            call_user_func_array([ $statement, "bind_param" ], $this->refValues($params));
        }
        
        $statement->execute();
        if ($statement->error) {
            trigger_error("Problem executing query: {$statement->error} {$this->mysqli->error} ($query)", E_USER_ERROR);
        }
        
        return $statement;
    }
    
    /**
     * This method is used to prepare the statements by turning the item type into a type used by mysqli
     * @param string $item Input to determine the type.
     * @return string The parameter type.
     */
    private function determineType($item) {
        switch (gettype($item)) {
        case "NULL":
        case "string":
            return "s";
        case "boolean":
        case "integer":
            return "i";
        case "blob":
            return "b";
        case "double":
            return "d";
        }
        return "";
    }
    
    /**
     * This is required for PHP 5.3+
     * @param string[] $arr
     * @return string[]
     */
    private function refValues(array $arr) {
        if (strnatcmp(phpversion(), "5.3") >= 0) {
            $refs = [];
            for ($i = 0; $i < count($arr); $i++) {
                $refs[$i] = & $arr[$i];
            }
            return $refs;
        }
        return $arr;
    }
    
    /**
     * Takes care of prepared statements' bind_result method, when the number of variables to pass is unknown.
     * @param mixed $statement Equal to the prepared statement object.
     * @return array The results of the SQL fetch.
     */
    private function dynamicBindResults($statement) {
        $parameters = [];
        $results    = [];
        $meta       = $statement->result_metadata();

        // If $meta is false yet sqlstate is true, there's no sql error but the query is
        // most likely an update/insert/delete which doesn't produce any results
        if (!$meta && $statement->sqlstate) {
            return [];
        }
        
        $row = [];
        while ($field = $meta->fetch_field()) {
            $row[$field->name] = null;
            $parameters[]      = & $row[$field->name];
        }
        call_user_func_array([ $statement, "bind_result" ], $parameters);

        while ($statement->fetch()) {
            $x = [];
            foreach ($row as $key => $val) {
                $x[$key] = ctype_digit($val) && strrpos($val, "0", -strlen($val)) === false ? (int)$val : $val;
            }
            array_push($results, $x);
        }
        $statement->free_result();
        
        return $results;
    }
    
    /**
     * Builds the query for inserting or updating
     * @param array $fields
     * @return string
     */
    private function buildInsertHeader(array $fields) {
        return "(`" . Strings::join(array_keys($fields), "`, `") . "`) VALUES ";
    }
    
    /**
     * Process the table data for building the query for inserting or updating
     * @param array   $fields
     * @param array   $bindParams
     * @param boolean $isInsert
     * @return string
     */
    private function buildTableData(array $fields, array &$bindParams, $isInsert) {
        $result = "";
        if ($isInsert) {
             $result .= "(";
        }
        
        foreach ($fields as $column => $value) {
            if (!$isInsert) {
                $result .= "`$column` = ";
            }
            
            if (!is_array($value)) {
                $result .= "?, ";
                $bindParams[] = $value;
            } else {
                $key = key($value);
                $val = $value[$key];
                switch ($key) {
                case "[E]":
                    $result .= "`$val`, ";
                    break;
                case "[I]":
                    $result .= $column . $val . ", ";
                    break;
                case "[F]":
                    $result .= $val[0] . ", ";
                    if (!empty($val[1])) {
                        foreach ($val[1] as $v) {
                            $bindParams[] = $v;
                        }
                    }
                    break;
                case "[N]":
                    if ($val == null) {
                        $result .= "!$column, ";
                    } else {
                        $result .= "!$val, ";
                    }
                    break;
                default:
                    die("Wrong operation");
                }
            }
        }
        
        $result = rtrim($result, ", ");
        if ($isInsert) {
            $result .= ")";
        }
        return $result;
    }
    
    

    /**
     * Returns the Table Name
     * @param string $name
     * @return string
     */
    public function getTableName($name) {
        if (!Strings::startsWith($name, $this->prefix)) {
            return $this->prefix . $name;
        }
        return $name;
    }

    /**
     * Returns an array with all the tables
     * @param string[] $filter     Optional.
     * @param boolean  $withPrefix Optional.
     * @return string[]
     */
    public function getTables(array $filter = null, $withPrefix = true) {
        $request = $this->query("SHOW TABLES FROM `$this->database`");
        $result  = [];

        foreach ($request as $row) {
            foreach ($row as $value) {
                if ((!empty($filter) && !in_array($value, $filter)) || empty($filter)) {
                    if (!$withPrefix) {
                        $result[] =  Strings::replace($value, $this->prefix, "");
                    } else {
                        $result[] = $value;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Returns the Table Primary Keys
     * @param string $table
     * @return array
     */
    public function getPrimaryKeys($table) {
        $tableName = $this->getTableName($table);
        $request   = $this->query("SHOW KEYS FROM `$tableName`");
        $result    = [];
        
        foreach ($request as $row) {
            if ($row["Key_name"] == "PRIMARY") {
                $result[] = $row["Column_name"];
            }
        }
        return $result;
    }

    /**
     * Returns the Table Keys
     * @param string $table
     * @return array
     */
    public function getTableKeys($table) {
        $tableName = $this->getTableName($table);
        return $this->query("SHOW INDEXES IN `$tableName`");
    }
    
    /**
     * Returns the Table Fields
     * @param string $table
     * @return array
     */
    public function getTableFields($table) {
        $tableName = $this->getTableName($table);
        return $this->query("SHOW FIELDS FROM `$tableName`");
    }



    /**
     * Creates a Table
     * @param string $table
     * @param array  $fields
     * @param array  $primary
     * @param array  $keys
     * @return string
     */
    public function createTable($table, array $fields, array $primary, array $keys) {
        $tableName = $this->getTableName($table);
        $sql       = "CREATE TABLE $tableName (\n";
        
        foreach ($fields as $key => $type) {
            $sql .= "  `$key` " . $type . ",\n";
        }
        $sql .= "  PRIMARY KEY (" . Strings::join($primary, ", ") . ")\n";
        foreach ($keys as $key) {
            $sql .= "  KEY `$field->key` (`$field->key`)\n";
        }
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $this->query($sql);
        return $sql;
    }

    /**
     * Deletes a Table
     * @param string $table
     * @return string
     */
    public function deleteTable($table) {
        $tableName = $this->getTableName($table);
        $sql       = "DROP TABLE `$tableName`";
        $this->query($sql);
        return $sql;
    }

    /**
     * Renames a Column from the Table
     * @param string $table
     * @param string $column
     * @param string $type
     * @param string $afterColumn Optional.
     * @return string
     */
    public function addColumn($table, $column, $type, $afterColumn = null) {
        $tableName = $this->getTableName($table);
        $sql       = "ALTER TABLE $tableName ADD COLUMN `$column` $type ";
        $sql      .= !empty($afterColumn) ? "AFTER `$afterColumn`" : "FIRST";
        $this->query($sql);
        return $sql;
    }

    /**
     * Renames a Column from the Table
     * @param string $table
     * @param string $oldColumn
     * @param string $newColumn
     * @param string $type
     * @return string
     */
    public function renameColumn($table, $oldColumn, $newColumn, $type) {
        $tableName = $this->getTableName($table);
        $sql       = "ALTER TABLE $tableName CHANGE `$oldColumn` `$newColumn` $type";
        $this->query($sql);
        return $sql;
    }

    /**
     * Updates a Column from the Table
     * @param string $table
     * @param string $column
     * @param string $type
     * @return string
     */
    public function updateColumn($table, $column, $type) {
        $tableName = $this->getTableName($table);
        $sql       = "ALTER TABLE $tableName MODIFY COLUMN `$column` $type";
        $this->query($sql);
        return $sql;
    }

    /**
     * Deletes a Column from the Table
     * @param string  $table
     * @param string  $column
     * @param boolean $execute Optional.
     * @return string
     */
    public function deleteColumn($table, $column, $execute = true) {
        $tableName = $this->getTableName($table);
        $sql       = "ALTER TABLE $tableName DROP COLUMN `$column`";
        if ($execute) {
            $this->query($sql);
        }
        return $sql;
    }

    /**
     * Updates the Primary Keys on the Table
     * @param string $table
     * @param array  $primary
     * @return string
     */
    public function updatePrimary($table, array $primary) {
        $tableName = $this->getTableName($table);
        $sql       = "DROP PRIMARY KEY \n";
        $sql      .= "ADD PRIMARY KEY (" . Strings::join($primary, ", ") . ")";
        $this->query($sql);
        return $sql;
    }

    /**
     * Creates an Index on the Table
     * @param string $table
     * @param string $key
     * @return string
     */
    public function createIndex($table, $key) {
        $tableName = $this->getTableName($table);
        $sql       = "CREATE INDEX $key ON $tableName($key)";
        $this->query($sql);
        return $sql;
    }

    

    /**
     * Dumps the entire database
     * @param string[] $filter Optional.
     * @param mixed    $fp     Optional.
     * @return void
     */
    public function dump(array $filter = null, $fp = null) {
        $crlf = "\r\n";
        
        // SQL Dump Header
        $this->write(
            $fp,
            "# ========================================================= $crlf" .
            "# $crlf" .
            "# Database dump of tables in `{$this->getName()}` $crlf" .
            "# " . date("d M Y, H:i:s") . $crlf .
            "# $crlf" .
            "# ========================================================= $crlf" .
            $crlf
        );
        
        // Get all tables in the database
        $tables = $this->getTables($filter);
        
        // Dump each table
        foreach ($tables as $table) {
            if (function_exists("apache_reset_timeout")) {
                apache_reset_timeout();
            }
            
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
            if (!empty($rows)) {
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
        $this->write($fp, $crlf . "# Done" . $crlf);
    }
    
    /**
     * Writes the content in a file or prints them in the screen
     * @param mixed  $fp
     * @param string $content
     * @return void
     */
    private function write($fp, $content) {
        if (!empty($fp)) {
            fwrite($fp, $content);
        } else {
            print($content);
        }
    }
    
    /**
     * Returns the table's SQL data
     * @param string $tableName
     * @return string
     */
    private function getTableSQLData($tableName) {
        $crlf    = "\r\n";
        $result  = "CREATE TABLE `$tableName` (" . $crlf;
        $request = $this->query("SHOW FIELDS FROM `$tableName`");
        
        foreach ($request as $row) {
            // Make the CREATE for this column.
            $result .= "  " . $row["Field"] . " " . $row["Type"] . ($row["Null"] != "YES" ? " NOT NULL" : "");

            // Add a default...?
            if (isset($row["Default"])) {
                // Make a special case of auto-timestamp.
                if ($row["Default"] == "CURRENT_TIMESTAMP") {
                    $result .= " /*!40102 NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP */";
                } else {
                    $result .= " default " . (is_numeric($row["Default"]) ? $row["Default"] : "'" . $this->escape($row["Default"]) . "'");
                }
            }

            // And now any extra information. (such as auto_increment.)
            $result .= ($row["Extra"] != "" ? " " . $row["Extra"] : "") . "," . $crlf;
        }
        
        // Take off the last comma.
        $result = substr($result, 0, -strlen($crlf) - 1);
        
        // Find the keys.
        $request = $this->query("SHOW KEYS FROM `$tableName`");
        $indexes = array();
        
        foreach ($request as $row) {
            // IS this a primary key, unique index, or regular index?
            if ($row["Key_name"] == "PRIMARY") {
                $row["Key_name"] = "PRIMARY KEY";
            } elseif (empty($row["Non_unique"])) {
                $row["Key_name"] = "UNIQUE " . $row["Key_name"];
            } elseif ($row["Comment"] == "FULLTEXT" || (isset($row["Index_type"]) && $row["Index_type"] == "FULLTEXT")) {
                $row["Key_name"] = "FULLTEXT " . $row["Key_name"];
            } else {
                $row["Key_name"] = "KEY " . $row["Key_name"];
            }

            // Is this the first column in the index?
            if (empty($indexes[$row["Key_name"]])) {
                $indexes[$row["Key_name"]] = array();
            }

            // A sub part, like only indexing 15 characters of a varchar.
            if (!empty($row["Sub_part"])) {
                $indexes[$row["Key_name"]][$row["Seq_in_index"]] = $row["Column_name"] . "(" . $row["Sub_part"] . ")";
            } else {
                $indexes[$row["Key_name"]][$row["Seq_in_index"]] = $row["Column_name"];
            }
        }

        // Build the CREATEs for the keys.
        foreach ($indexes as $keyname => $columns) {
            ksort($columns);
            $result .= "," . $crlf . "  $keyname (" . Strings::join($columns, ", ") . ")";
        }
        
        // Now just get the comment and type... (MyISAM, etc.)
        $request = $this->query("
            SHOW TABLE STATUS
            LIKE '" . strtr($tableName, array('_' => '\\_', '%' => '\\%')) . "'
        ");
        
        // Probably MyISAM.... and it might have a comment.
        $result .= $crlf . ") ENGINE=" . (isset($request[0]["Type"]) ? $request[0]["Type"] : $request[0]["Engine"]);
        $result .= $request[0]["Comment"] != "" ? " COMMENT='" . $request[0]["Comment"] . "'" : "";
        
        return $result;
    }

    /**
     * Returns the table content
     * @param string $tableName
     * @return string
     */
    private function getTableContent($tableName) {
        $crlf   = "\r\n";
        $result = "";
        $start  = 0;
        
        do {
            $request = $this->query("SELECT /*!40001 SQL_NO_CACHE */ * FROM $tableName LIMIT $start, 250");
            $start  += 250;
            
            if (!empty($request)) {
                $result .= "INSERT INTO `$tableName`" . $crlf . "\t(`" . Strings::join("`, `", array_keys($request[0])) . "`) $crlf VALUES ";
                
                foreach ($request as $index => $row) {
                    $fieldList = [];
                    foreach ($row as $value) {
                        // Try to figure out the type of each field. (NULL, number, or 'string'.)
                        if (!isset($value)) {
                            $fieldList[] = "NULL";
                        } elseif (is_numeric($value)) {
                            $fieldList[] = $value;
                        } else {
                            $fieldList[] = "'" . $this->escape($value) . "'";
                        }
                    }
                    $result .= "(" . Strings::join($fieldList, ", ") . ")";
                    
                    if ($index < count($request) - 1) {
                        $result .= "," . $crlf . "\t";
                    }
                }
                $result .= ";" . $crlf;
            }
        } while (!empty($request));
        
        return $result;
    }
}
