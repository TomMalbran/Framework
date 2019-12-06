<?php
namespace Framework\Schema;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Database Query
 */
class Query {
    
    public $where     = "";
    public $params    = [];
    public $prefix    = "AND";
    public $addPrefix = false;
    
    public $limit     = "";
    public $groupBy   = "";
    public $orderBy   = "";

    public $columns   = [];
    public $groups    = [];
    public $orders    = [];
    
    
    /**
     * Creates a new Query instance
     * @param Query $query Optional.
     */
    public function __construct(Query $query = null) {
        if (!empty($query)) {
            $this->where     = $query->where;
            $this->params    = $query->params;
            $this->prefix    = $query->prefix;
            $this->addPrefix = $query->addPrefix;
            
            $this->limit     = $query->limit;
            $this->orderBy   = $query->orderBy;
            $this->groupBy   = $query->groupBy;

            $this->columns   = $query->columns;
            $this->groups    = $query->groups;
            $this->orders    = $query->orders;
        }
    }
    
    
    
    /**
     * Returns the Prefix
     * @return string
     */
    public function getPrefix() {
        $prefix = $this->addPrefix ? $this->prefix . " " : "";
        if (!$this->addPrefix) {
            $this->addPrefix = true;
        }
        return $prefix;
    }

    /**
     * Adds an expression as an and
     * @param string          $column
     * @param string          $expression
     * @param string|string[] $value
     * @return Query
     */
    public function add($column, $expression, $value) {
        $prefix = $this->getPrefix();
        $binds  = is_array($value) ? self::createBinds($value) : "?";
        $value  = $expression == "LIKE" ? "%$value%" : $value;
        $values = Arrays::toArray($value);

        $this->where    .= "{$prefix} {$column} {$expression} {$binds} ";
        $this->params    = array_merge($this->params, $values);
        $this->columns[] = $column;
        return $this;
    }
    
    /**
     * Adds an expression as an and if the value is not empty
     * @param string  $column
     * @param string  $expression
     * @param mixed   $value
     * @param boolean $condition  Optional.
     * @return Query
     */
    public function addIf($column, $expression, $value, $condition = null) {
        if ($condition !== null && $condition) {
            $this->add($column, $expression, $value);
        } elseif ($condition === null && !empty($value)) {
            $this->add($column, $expression, $value);
        }
        return $this;
    }

    /**
     * Adds an expression with a value
     * @param string $expression
     * @param mixed  ...$values
     * @return Query
     */
    public function addExp($expression, ...$values) {
        $prefix       = $this->getPrefix();
        $this->where .= "{$prefix}{$expression} ";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    /**
     * Adds an expression with a value if the value is not empty
     * @param string $expression
     * @param mixed  ...$values
     * @return Query
     */
    public function addExpIf($expression, ...$values) {
        if (!empty($values[0])) {
            $this->addExp($expression, ...$values);
        }
        return $this;
    }

    /**
     * Adds an expression as NULL
     * @param string $column
     * @return Query
     */
    public function addNull($column) {
        $prefix          = $this->getPrefix();
        $this->where    .= "{$prefix}ISNULL($column) = 1";
        $this->columns[] = $column;
        return $this;
    }
    

    
    /**
     * Starts a Parenthesis
     * @return Query
     */
    public function startParen() {
        $prefix          = $this->getPrefix();
        $this->where    .= "{$prefix}(";
        $this->addPrefix = false;
        return $this;
    }

    /**
     * Starts a Parenthesis
     * @return Query
     */
    public function endParen() {
        $this->where .= ") ";
        return $this;
    }

    /**
     * Starts an Or expression
     * @return Query
     */
    public function or() {
        $this->where    .= " OR ";
        $this->addPrefix = false;
        return $this;
    }

    /**
     * Starts an Or expression
     * @return Query
     */
    public function startOr() {
        $prefix          = $this->getPrefix();
        $this->where    .= "{$prefix}(";
        $this->prefix    = "OR";
        $this->addPrefix = false;
        return $this;
    }
    
    /**
     * Ends an Or expression
     * @return Query
     */
    public function endOr() {
        $this->where .= ") ";
        $this->prefix = "AND";
        return $this;
    }
    
    /**
     * Adds a Search expression
     * @param string|string[] $column
     * @param mixed           $value
     * @param string          $expression Optional.
     * @return Query
     */
    public function search($column, $value, $expression = "LIKE") {
        if (!empty($value)) {
            $columns = Arrays::toArray($column);
            if (count($columns) > 1) {
                $this->startOr();
                foreach ($columns as $col) {
                    $this->add($col, "LIKE", $value);
                }
                $this->endOr();
            } else {
                $this->add($columns[0], "LIKE", $value);
            }
        }
        return $this;
    }

    /**
     * Adds an expression as an and where the column is between the given times
     * @param string  $column
     * @param integer $fromTime
     * @param integer $toTime
     * @return Query
     */
    public function betweenTimes($column, $fromTime, $toTime) {
        if (!empty($fromTime) && !empty($toTime)) {
            $this->add($column, ">=", $fromTime);
            $this->add($column, "<=", $toTime);
        } elseif (!empty($fromTime)) {
            $this->add($column, ">=", $fromTime);
        } elseif (!empty($toTime)) {
            $this->add($column, "<=", $toTime);
        }
        return $this;
    }
    
    /**
     * Adds an Group By
     * @param string $column
     * @return Query
     */
    public function groupBy($column) {
        if (!empty($column)) {
            $this->groupBy  = $column;
            $this->groups[] = $column;
        }
        return $this;
    }

    /**
     * Adds an Order By
     * @param string  $column
     * @param boolean $isASC  Optional.
     * @return Query
     */
    public function orderBy($column, $isASC = true) {
        if (!empty($column)) {
            $prefix         = !empty($this->orderBy) ? ", " : "";
            $this->orderBy .= " {$prefix}{$column} " . ($isASC ? "ASC" : "DESC");
            $this->orders[] = $column;
        }
        return $this;
    }
    
    /**
     * Orders Randomly
     * @return Query
     */
    public function random() {
        $this->orderBy = "RAND()";
        $this->orders  = [];
        return $this;
    }
    
    /**
     * Adds an Limit
     * @param integer $from
     * @param integer $to   Optional.
     * @return Query
     */
    public function limit($from, $to = null) {
        if ($to != null) {
            $this->limit = max($from, 0) . ", " . max($to - $from + 1, 1);
        } else {
            $this->limit = $from;
        }
        return $this;
    }

    /**
     * Adds a limit using pagination
     * @param integer $page   Optional.
     * @param integer $amount Optional.
     * @return Query
     */
    public function paginate($page = 0, $amount = 100) {
        $from = $page * $amount;
        $to   = $from + $amount;
        return $this->limit($from, $to);
    }
    
    

    /**
     * Returns true if the Query is empty
     * @return boolean
     */
    public function isEmpty() {
        return empty($this->where);
    }

    /**
     * Returns true if the given Column is in the Query
     * @param string $column
     * @return boolean
     */
    public function hasColumn($column) {
        return Arrays::contains($this->columns, $column);
    }

    /**
     * Returns true if there is an Order By
     * @param string $order Optional.
     * @return boolean
     */
    public function hasOrder($order = null) {
        if (!empty($order)) {
            return Arrays::contains($this->orders, $order);
        }
        return !empty($this->orderBy);
    }
    


    /**
     * Returns the complete Query to use with the Database
     * @param boolean $addWhere Optional.
     * @return string
     */
    public function get($addWhere = true) {
        $result  = $this->getWhere($addWhere);
        $result .= $this->getOrderLimit();
        return preg_replace("!\s+!", " ", $result);
    }

    /**
     * Returns the where part of the Query to use with the Database
     * @param boolean $addWhere Optional.
     * @return string
     */
    public function getWhere($addWhere = false) {
        if (!empty($this->where)) {
            return ($addWhere ? "WHERE " : "AND ") . $this->where;
        }
        return "";
    }

    /**
     * Returns the group order and limit part of the Query to use with the Database
     * @return string
     */
    public function getOrderLimit() {
        $result = "";
        if (!empty($this->groupBy)) {
            $result .= " GROUP BY " . $this->groupBy;
        }
        if (!empty($this->orderBy)) {
            $result .= " ORDER BY " . $this->orderBy;
        }
        if (!empty($this->limit)) {
            $result .= " LIMIT " . $this->limit;
        }
        return $result;
    }

    /**
     * Returns the params part of the Query to use with the Database
     * @param boolean $duplicate Optional.
     * @return string
     */
    public function getParams($duplicate = false) {
        if ($duplicate) {
            return array_merge($this->params, $this->params);
        }
        return $this->params;
    }

    /**
     * Returns the Columns
     * @return array
     */
    public function getColumns() {
        $result = array_merge($this->columns, $this->groups, $this->orders);
        return array_unique($result);
    }

    /**
     * Updates a Column
     * @param string $oldColumn
     * @param string $newColumn
     * @return void
     */
    public function updateColumn($oldColumn, $newColumn) {
        foreach ([ "where", "orderBy", "groupBy" ] as $type) {
            foreach ([ "(", " " ] as $prefix) {
                $this->{$type} = Strings::replace(
                    $this->{$type},
                    "{$prefix}{$oldColumn}",
                    "{$prefix}{$newColumn}"
                );
            }
        }
    }



    /**
     * Creates a new Query with the given values
     * @param string $column     Optional.
     * @param string $expression Optional.
     * @param mixed  $value      Optional.
     * @return Query
     */
    public static function create($column = "", $expression = "", $value = null) {
        $query = new Query();
        if (!empty($column)) {
            $query->add($column, $expression, $value);
        }
        return $query;
    }

    /**
     * Creates a new Query with the given values
     * @param string $column     Optional.
     * @param string $expression Optional.
     * @param mixed  $value      Optional.
     * @return Query
     */
    public static function createIf($column = "", $expression = "", $value = null) {
        $query = new Query();
        if (!empty($column) && !empty($value)) {
            $query->add($column, $expression, $value);
        }
        return $query;
    }

    /**
     * Creates a new Query with the given values
     * @param string|string[] $column     Optional.
     * @param mixed           $value      Optional.
     * @param string          $expression Optional.
     * @return Query
     */
    public static function createSearch($column = null, $value = null, $expression = "LIKE") {
        $query = new Query();
        if (!empty($column) && !empty($value)) {
            $query->search($column, $value, $expression);
        }
        return $query;
    }

    /**
     * Creates a new Query with an Order
     * @param string  $column   Optional.
     * @param integer $fromTime Optional.
     * @param integer $toTime   Optional.
     * @return Query
     */
    public static function createBetween($column = "", $fromTime = 0, $toTime = 0) {
        $query = new Query();
        if (!empty($column)) {
            $query->betweenTimes($column, $fromTime, $toTime);
        }
        return $query;
    }

    /**
     * Creates a new Query with an Order
     * @param string  $column Optional.
     * @param boolean $isASC  Optional.
     * @return Query
     */
    public static function createOrderBy($column = "", $isASC = false) {
        $query = new Query();
        if (!empty($column)) {
            $query->orderBy($column, $isASC);
        }
        return $query;
    }

    /**
     * Creates a list of question marks for the given array
     * @param array $array
     * @return string
     */
    public static function createBinds(array $array) {
        $bind = [];
        for ($i = 0; $i < count($array); $i++) {
            $bind[] = "?";
        }
        return "(" . Strings::join($bind, ",") . ")";
    }



    /**
     * Method generates equality between columns function call
     * @param string $column Optional.
     * @return array
     */
    public static function equal($column = null) {
        return [ "[E]" => (string)$column ];
    }
    
    /**
     * Method generates incremental function call
     * @param integer $amount Optional.
     * @return array
     */
    public static function inc($amount = 1) {
        return [ "[I]" => "+" . (int)$amount ];
    }

    /**
     * Method generates decrimental function call
     * @param integer $amount Optional.
     * @return array
     */
    public static function dec($amount = 1) {
        return [ "[I]" => "-" . (int)$amount ];
    }
    
    /**
     * Method generates change boolean function call
     * @param string $column Optional.
     * @return array
     */
    public static function not($column = null) {
        return [ "[N]" => (string)$column ];
    }

    /**
     * Method generates user defined function call
     * @param string $expression
     * @param array  $params     Optional.
     * @return array
     */
    public static function func($expression, array $params = []) {
        return [ "[F]" => [ $expression, $params ]];
    }
    
    /**
     * Method generates an AES Encrypt function call
     * @param string $value
     * @param string $key
     * @return array
     */
    public static function encrypt($value, $key) {
        return self::func("AES_ENCRYPT(?, ?)", [ $value, $key ]);
    }

    /**
     * Method generates an REPLACE function call
     * @param string $column
     * @param string $value
     * @param string $replace
     * @return array
     */
    public static function replace($column, $value, $replace) {
        return self::func("REPLACE($column, ?, ?)", [ $value, $replace ]);
    }
}
