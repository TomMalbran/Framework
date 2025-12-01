<?php
namespace Framework\Database\Query;

use Framework\Database\Query\QueryOperator;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Database Query
 */
class Query {

    public string $where     = "";
    public string $prefix    = "AND";
    public bool   $addPrefix = false;

    public string $limit     = "";
    public string $groupBy   = "";
    public string $orderBy   = "";

    /** @var string[] */
    public array  $prefixes  = [];

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
        if ($query !== null) {
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
     * @param QueryOperator|string        $operator      Optional.
     * @param mixed[]|integer|string|null $value         Optional.
     * @param boolean                     $caseSensitive Optional.
     * @return Query
     */
    public static function create(
        string $column = "",
        QueryOperator|string $operator = QueryOperator::Equal,
        array|int|string|null $value = null,
        bool $caseSensitive = false,
    ): Query {
        $query = new Query();
        if ($column !== "" && $value !== null) {
            $query->add($column, $operator, $value, $caseSensitive);
        }
        return $query;
    }



    /**
     * Returns the Prefix
     * @return string
     */
    public function getPrefix(): string {
        $prefix = $this->addPrefix ? "{$this->prefix} " : "";
        if (!$this->addPrefix) {
            $this->addPrefix = true;
        }
        return $prefix;
    }

    /**
     * Adds an expression as an and
     * @param string                 $column
     * @param QueryOperator|string   $operator
     * @param mixed[]|string|integer $value
     * @param boolean                $caseSensitive Optional.
     * @param boolean|null           $condition     Optional.
     * @return Query
     */
    public function add(
        string $column,
        QueryOperator|string $operator,
        array|int|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): Query {
        if ($condition !== null && !$condition) {
            return $this;
        }

        $prefix   = $this->getPrefix();
        $suffix   = $caseSensitive ? "BINARY" : "";
        $operator = QueryOperator::fromValue($operator);
        $compare  = $operator->value;
        $param    = null;
        $binds    = "?";

        switch ($operator) {
        case QueryOperator::Equal:
            if (is_array($value) && count($value) === 1) {
                $param   = $value[0];
            } elseif (is_array($value) && count($value) > 1) {
                $param   = $value;
                $compare = "IN";
                $binds   = $this->createBinds($value);
            } elseif (!is_array($value)) {
                $param   = $value;
            }
            break;

        case QueryOperator::NotEqual:
            if (is_array($value) && count($value) === 1) {
                $param   = $value[0];
            } elseif (is_array($value) && count($value) > 1) {
                $param   = $value;
                $compare = "NOT IN";
                $binds   = $this->createBinds($value);
            } elseif (!is_array($value)) {
                $param   = $value;
            }
            break;

        case QueryOperator::In:
            if (is_array($value) && count($value) > 1) {
                $param   = $value;
                $binds   = $this->createBinds($value);
            } elseif (is_array($value) && count($value) === 1) {
                $param   = $value[0];
                $compare = "=";
            } elseif (!is_array($value)) {
                $param   = $value;
                $compare = "=";
            }
            break;

        case QueryOperator::NotIn:
            if (is_array($value) && count($value) > 1) {
                $param   = $value;
                $binds   = $this->createBinds($value);
            } elseif (is_array($value) && count($value) === 1) {
                $param   = $value[0];
                $compare = "<>";
            } elseif (!is_array($value)) {
                $param   = $value;
                $compare = "<>";
            }
            break;

        case QueryOperator::Like:
        case QueryOperator::NotLike:
            if (!is_array($value)) {
                $param = "%" . trim(strtolower((string)$value)) . "%";
            }
            break;

        case QueryOperator::StartsWith:
        case QueryOperator::NotStartsWith:
            if (!is_array($value)) {
                $param   = trim(strtolower((string)$value)) . "%";
                $compare = Strings::replace($operator->value, "STARTS", "LIKE");
            }
            break;

        case QueryOperator::EndsWith:
        case QueryOperator::NotEndsWith:
            if (!is_array($value)) {
                $param   = "%" . trim(strtolower((string)$value));
                $compare = Strings::replace($operator->value, "ENDS", "LIKE");
            }
            break;

        default:
            $param = $value;
        }

        if ($param === null) {
            return $this;
        }

        $this->where    .= "$prefix $column $compare $suffix $binds ";
        $this->params    = array_merge($this->params, Arrays::toArray($param));
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Adds an expression as an and if the value is not empty
     * @param string                      $column
     * @param QueryOperator|string        $operator
     * @param mixed[]|integer|string|null $value
     * @param boolean|null                $condition     Optional.
     * @param boolean                     $caseSensitive Optional.
     * @return Query
     */
    public function addIf(
        string $column,
        QueryOperator|string $operator,
        array|int|string|null $value,
        ?bool $condition = null,
        bool $caseSensitive = false,
    ): Query {
        if ($condition === true && $value !== null) {
            $this->add($column, $operator, $value, $caseSensitive);
        } elseif ($condition === null && $value !== null && !Arrays::isEmpty($value)) {
            $this->add($column, $operator, $value, $caseSensitive);
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
     * Adds a Search expression
     * @param string[]|string      $column
     * @param mixed                $value
     * @param QueryOperator|string $operator       Optional.
     * @param boolean              $caseInsensitive Optional.
     * @param boolean              $splitValue      Optional.
     * @param string               $splitText       Optional.
     * @param boolean              $matchAny        Optional.
     * @return Query
     */
    public function search(
        array|string $column,
        mixed $value,
        QueryOperator|string $operator = QueryOperator::Like,
        bool $caseInsensitive = true,
        bool $splitValue = false,
        string $splitText = " ",
        bool $matchAny = false,
    ): Query {
        if (Arrays::isEmpty($value)) {
            return $this;
        }

        // Prepare the columns
        $columns   = is_array($column) ? $column : [ $column ];
        $multiCols = Arrays::length($columns) > 1;

        // Prepare the values
        $valueParts = [];
        if (is_array($value)) {
            $valueParts = Arrays::toStrings($value, withoutEmpty: true);
        } elseif (is_string($value)) {
            $valueParts = $splitValue ? Strings::split($value, $splitText) : [ $value ];
            $valueParts = Arrays::removeEmpty($valueParts);
        } else {
            $valueParts = [ Strings::toString($value) ];
        }

        if ($caseInsensitive) {
            foreach ($valueParts as $index => $valuePart) {
                $valueParts[$index] = Strings::toLowerCase($valuePart);
            }
        }
        $multiParts = Arrays::length($valueParts) > 1;

        // Handle the search
        $isFirst = true;
        if ($multiParts) {
            $this->startParen();
        }
        foreach ($valueParts as $valuePart) {
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
                $this->add($columnSearch, $operator, $valuePart);
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
     * @param string|integer $param
     * @return Query
     */
    public function addParam(string|int $param): Query {
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
        $prefix           = $this->getPrefix();
        $this->where     .= "{$prefix}(";
        $this->prefixes[] = $this->prefix;
        $this->prefix     = "AND";
        $this->addPrefix  = false;
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
        $prefix           = $this->getPrefix();
        $this->where     .= "{$prefix}(";
        $this->prefixes[] = $this->prefix;
        $this->prefix     = "OR";
        $this->addPrefix  = false;
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

        $this->prefix    = array_pop($this->prefixes) ?? "AND";
        $this->addPrefix = true;
        return $this;
    }



    /**
     * Adds a Group By
     * @param string $column
     * @return Query
     */
    public function groupBy(string $column): Query {
        $prefix         = $this->groupBy !== "" ? "," : "";
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
        $prefix         = $this->orderBy !== "" ? "," : "";
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
        if ($from !== 0 || ($to !== null && $to !== 0)) {
            if ($to !== null) {
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
        return $this->where === "";
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
        if ($order !== null && $order !== "") {
            return Arrays::contains($this->orders, $order);
        }
        return $this->orderBy !== "";
    }

    /**
     * Returns true if there is an Group By
     * @param string|null $group Optional.
     * @return boolean
     */
    public function hasGroup(?string $group = null): bool {
        if ($group !== null && $group !== "") {
            return Arrays::contains($this->groups, $group);
        }
        return $this->groupBy !== "";
    }



    /**
     * Returns the complete Query to use with the Database
     * @param boolean $addWhere Optional.
     * @return string
     */
    public function get(bool $addWhere = true): string {
        $result = "";
        if ($this->where !== "") {
            $where   = Strings::stripStart($this->where, "AND ");
            $result .= ($addWhere ? "WHERE " : "AND ") . $where;
        }
        if ($this->groupBy !== "") {
            $result .= " GROUP BY {$this->groupBy}";
        }
        if ($this->orderBy !== "") {
            $result .= " ORDER BY {$this->orderBy}";
        }
        if ($this->limit !== "") {
            $result .= " LIMIT {$this->limit}";
        }
        return Strings::replacePattern($result, "!\s+!", " ");
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
