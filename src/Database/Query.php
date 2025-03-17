<?php
namespace Framework\Database;

use Framework\Request;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;
use Framework\Utils\Period;

/**
 * The Database Query
 */
class Query {

    public string $where     = "";
    public string $prefix    = "AND";
    public string $oldPrefix = "";
    public bool   $addPrefix = false;

    public string $limit     = "";
    public string $groupBy   = "";
    public string $orderBy   = "";

    /** @var mixed[] */
    public array  $params    = [];

    /** @var string[] */
    public array  $columns   = [];

    /** @var string[] */
    public array  $groups    = [];

    /** @var string[] */
    public array  $orders    = [];


    /**
     * Creates a new Query instance
     * @param Query|null $query Optional.
     */
    public function __construct(?Query $query = null) {
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
     * Creates a new Query with the given values
     * @param string                      $column        Optional.
     * @param string                      $expression    Optional.
     * @param mixed[]|integer|string|null $value         Optional.
     * @param boolean                     $caseSensitive Optional.
     * @return Query
     */
    public static function create(string $column = "", string $expression = "", array|int|string|null $value = null, bool $caseSensitive = false): Query {
        $query = new Query();
        if (!empty($column)) {
            $query->add($column, $expression, $value, $caseSensitive);
        }
        return $query;
    }



    /**
     * Returns the Prefix
     * @return string
     */
    public function getPrefix(): string {
        $prefix = $this->addPrefix ? $this->prefix . " " : "";
        if (!$this->addPrefix) {
            $this->addPrefix = true;
        }
        return $prefix;
    }

    /**
     * Adds an expression as an and
     * @param string                 $column
     * @param string                 $expression
     * @param mixed[]|string|integer $value
     * @param boolean                $caseSensitive Optional.
     * @param boolean|null           $condition     Optional.
     * @return Query
     */
    public function add(
        string $column,
        string $expression,
        array|int|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): Query {
        if ($condition !== null && !$condition) {
            return $this;
        }

        $prefix = $this->getPrefix();
        $suffix = $caseSensitive ? "BINARY" : "";
        $binds  = "?";

        switch ($expression) {
        case "=":
            if (is_array($value)) {
                $expression = "IN";
                $binds      = $this->createBinds($value);
            }
            break;

        case "<>":
            if (is_array($value)) {
                $expression = "NOT IN";
                $binds      = $this->createBinds($value);
            }
            break;

        case "IN":
            if (is_array($value)) {
                $binds = $this->createBinds($value);
            } else {
                $expression = "=";
            }
            break;

        case "NOT IN":
            if (is_array($value)) {
                $binds = $this->createBinds($value);
            } else {
                $expression = "<>";
            }
            break;

        case "LIKE":
        case "NOT LIKE":
            if (!is_array($value)) {
                $value = "%" . trim(strtolower((string)$value)) . "%";
            }
            break;

        case "STARTS":
        case "NOT STARTS":
            if (!is_array($value)) {
                $expression = Strings::replace($expression, "STARTS", "LIKE");
                $value      = trim(strtolower((string)$value)) . "%";
            }
            break;

        case "ENDS":
        case "NOT ENDS":
            if (!is_array($value)) {
                $expression = Strings::replace($expression, "ENDS", "LIKE");
                $value      = "%" . trim(strtolower((string)$value));
            }
            break;
        default:
        }

        $this->where    .= "$prefix $column $expression $suffix $binds ";
        $this->params    = array_merge($this->params, Arrays::toArray($value));
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Adds an expression as an and if the value is not empty
     * @param string                      $column
     * @param string                      $expression
     * @param mixed[]|integer|string|null $value
     * @param boolean|null                $condition  Optional.
     * @return Query
     */
    public function addIf(string $column, string $expression, array|int|string|null $value, ?bool $condition = null): Query {
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
    public function addExp(string $expression, mixed ...$values): Query {
        $prefix       = $this->getPrefix();
        $this->where .= "{$prefix}{$expression} ";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    /**
     * Uses the Period to add a Between expression
     * @param string  $column
     * @param Request $request
     * @return Query
     */
    public function addPeriod(string $column, Request $request): Query {
        $period = new Period($request);
        if ($period->fromTime > 0) {
            $this->add($column, ">=", $period->fromTime);
        }
        if ($period->toTime > 0) {
            $this->add($column, "<=", $period->toTime);
        }
        return $this;
    }

    /**
     * Adds a Search expression
     * @param string[]|string $column
     * @param mixed           $value
     * @param string          $expression      Optional.
     * @param boolean         $caseInsensitive Optional.
     * @param boolean         $splitValue      Optional.
     * @param string          $splitText       Optional.
     * @param boolean         $matchAny        Optional.
     * @return Query
     */
    public function search(
        array|string $column,
        mixed        $value,
        string       $expression = "LIKE",
        bool         $caseInsensitive = true,
        bool         $splitValue = false,
        string       $splitText = " ",
        bool         $matchAny = false,
    ): Query {
        if (empty($value)) {
            return $this;
        }

        $valueParts = $splitValue ? Strings::split($value, $splitText) : Arrays::toArray($value);
        $valueParts = Arrays::removeEmpty($valueParts);
        $columns    = Arrays::toArray($column);
        $multiParts = Arrays::length($valueParts) > 1;
        $multiCols  = Arrays::length($columns) > 1;
        $isFirst    = true;

        if ($multiParts) {
            $this->startParen();
        }
        foreach ($valueParts as $valuePart) {
            $valueSearch = $caseInsensitive ? Strings::toLowerCase($valuePart) : $valuePart;
            if ($multiParts && !$isFirst) {
                if ($matchAny) {
                    $this->or();
                } else {
                    $this->and();
                }
            }
            if ($multiCols) {
                $this->startOr();
            }
            foreach ($columns as $columnSearch) {
                $this->add($columnSearch, $expression, $valueSearch);
            }
            if ($multiCols) {
                $this->endOr();
            }
            $isFirst = false;
        }
        if ($multiParts) {
            $this->endParen();
        }
        return $this;
    }

    /**
     * Adds a param to the Query
     * @param mixed $param
     * @return Query
     */
    public function addParam(mixed $param): Query {
        $this->params[] = $param;
        return $this;
    }



    /**
     * Adds a Open Parenthesis
     * @return Query
     */
    public function startParen(): Query {
        $prefix          = $this->getPrefix();
        $this->where    .= "{$prefix}(";
        $this->addPrefix = false;
        return $this;
    }

    /**
     * Adds a Close Parenthesis
     * @return Query
     */
    public function endParen(): Query {
        $this->where    .= ") ";
        $this->addPrefix = true;
        return $this;
    }

    /**
     * Adds an And
     * @return Query
     */
    public function and(): Query {
        $this->where    .= " AND ";
        $this->addPrefix = false;
        return $this;
    }

    /**
     * Starts an And expression
     * @return Query
     */
    public function startAnd(): Query {
        $prefix          = $this->getPrefix();
        $this->where    .= "{$prefix}(";
        $this->oldPrefix = $this->prefix;
        $this->prefix    = "AND";
        $this->addPrefix = false;
        return $this;
    }

    /**
     * Ends an And expression
     * @return Query
     */
    public function endAnd(): Query {
        return $this->endAndOr();
    }

    /**
     * Adds an Or
     * @return Query
     */
    public function or(): Query {
        $this->where    .= " OR ";
        $this->addPrefix = false;
        return $this;
    }

    /**
     * Starts an Or expression
     * @return Query
     */
    public function startOr(): Query {
        $prefix          = $this->getPrefix();
        $this->where    .= "{$prefix}(";
        $this->oldPrefix = $this->prefix;
        $this->prefix    = "OR";
        $this->addPrefix = false;
        return $this;
    }

    /**
     * Ends an Or expression
     * @return Query
     */
    public function endOr(): Query {
        return $this->endAndOr();
    }

    /**
     * Ends an And o Or expression
     * @return Query
     */
    private function endAndOr(): Query {
        if (Strings::endsWith($this->where, "AND (")) {
            $this->where = Strings::stripEnd($this->where, "AND (");
        } elseif (Strings::endsWith($this->where, "OR (")) {
            $this->where = Strings::stripEnd($this->where, "OR (");
        } elseif (Strings::endsWith($this->where, "(")) {
            $this->where = Strings::stripEnd($this->where, "(");
        } else {
            $this->where .= ") ";
        }

        $this->prefix    = $this->oldPrefix;
        $this->addPrefix = true;
        return $this;
    }



    /**
     * Adds a Group By
     * @param string $column
     * @return Query
     */
    public function groupBy(string $column): Query {
        $prefix         = !empty($this->groupBy) ? "," : "";
        $this->groupBy .= "$prefix $column ";
        $this->groups[] = $column;
        return $this;
    }

    /**
     * Adds an Order By
     * @param string  $column
     * @param boolean $isASC  Optional.
     * @return Query
     */
    public function orderBy(string $column, bool $isASC = true): Query {
        $prefix         = !empty($this->orderBy) ? "," : "";
        $this->orderBy .= "$prefix $column " . ($isASC ? "ASC" : "DESC");
        $this->orders[] = $column;
        return $this;
    }

    /**
     * Adds an Limit
     * @param integer      $from
     * @param integer|null $to   Optional.
     * @return Query
     */
    public function limit(int $from, ?int $to = null): Query {
        if (!empty($from) || !empty($to)) {
            if ($to != null) {
                $this->limit = max($from, 0) . ", " . max($to - $from + 1, 1);
            } else {
                $this->limit = (string)$from;
            }
        }
        return $this;
    }

    /**
     * Adds a limit using pagination
     * @param integer $page   Optional.
     * @param integer $amount Optional.
     * @return Query
     */
    public function paginate(int $page = 0, int $amount = 100): Query {
        $from = $page * $amount;
        $to   = $from + $amount - 1;
        return $this->limit($from, $to);
    }



    /**
     * Returns true if the Query is empty
     * @return boolean
     */
    public function isEmpty(): bool {
        return empty($this->where);
    }

    /**
     * Returns true if the given Column is in the Query
     * @param string $column
     * @return boolean
     */
    public function hasColumn(string $column): bool {
        return Arrays::contains($this->columns, $column);
    }

    /**
     * Returns true if there is an Order By
     * @param string|null $order Optional.
     * @return boolean
     */
    public function hasOrder(?string $order = null): bool {
        if (!empty($order)) {
            return Arrays::contains($this->orders, $order);
        }
        return !empty($this->orderBy);
    }

    /**
     * Returns true if there is an Group By
     * @param string|null $group Optional.
     * @return boolean
     */
    public function hasGroup(?string $group = null): bool {
        if (!empty($group)) {
            return Arrays::contains($this->groups, $group);
        }
        return !empty($this->groupBy);
    }



    /**
     * Returns the complete Query to use with the Database
     * @param boolean $addWhere Optional.
     * @return string
     */
    public function get(bool $addWhere = true): string {
        $result = "";
        if (!empty($this->where)) {
            $result .= ($addWhere ? "WHERE " : "AND ") . $this->where;
        }
        if (!empty($this->groupBy)) {
            $result .= " GROUP BY {$this->groupBy}";
        }
        if (!empty($this->orderBy)) {
            $result .= " ORDER BY {$this->orderBy}";
        }
        if (!empty($this->limit)) {
            $result .= " LIMIT {$this->limit}";
        }
        return Strings::replacePattern($result, "!\s+!", " ");
    }

    /**
     * Returns the params part of the Query to use with the Database
     * @param boolean $duplicate Optional.
     * @return mixed[]
     */
    public function getParams(bool $duplicate = false): array {
        if ($duplicate) {
            return array_merge($this->params, $this->params);
        }
        return $this->params;
    }

    /**
     * Returns the Columns
     * @return string[]
     */
    public function getColumns(): array {
        $result = array_merge($this->columns, $this->groups, $this->orders);
        return array_unique($result);
    }

    /**
     * Updates a Column
     * @param string $oldColumn
     * @param string $newColumn
     * @return Query
     */
    public function updateColumn(string $oldColumn, string $newColumn): Query {
        $this->where   = Strings::replace($this->where,   " $oldColumn ", " $newColumn ");
        $this->orderBy = Strings::replace($this->orderBy, " $oldColumn ", " $newColumn ");
        $this->groupBy = Strings::replace($this->groupBy, " $oldColumn ", " $newColumn ");
        return $this;
    }

    /**
     * Creates a list of question marks for the given array
     * @param mixed[] $array
     * @return string
     */
    public function createBinds(array $array): string {
        $bind = [];
        for ($i = 0; $i < count($array); $i++) {
            $bind[] = "?";
        }
        return "(" . Strings::join($bind, ",") . ")";
    }
}
