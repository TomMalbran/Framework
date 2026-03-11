<?php
namespace Framework\Database\Query;

use Framework\Database\Query\Operator;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Where Builder
 */
class WhereBuilder {

    private string $where         = "";
    private bool   $addOperator   = false;
    private string $nextOperator  = "AND";

    /** @var list<string> */
    private array  $usedOperators = [];

    private string $limit         = "";
    private string $orderBy       = "";
    private string $groupBy       = "";

    /** @var list<float|int|string> */
    private array  $params        = [];

    /** @var list<string> */
    private array  $columns       = [];

    /** @var list<string> */
    private array  $groups        = [];

    /** @var list<string> */
    private array  $orders        = [];



    /**
     * Copies the Data from another WhereBuilder instance
     * @param WhereBuilder $builder
     * @return WhereBuilder
     */
    public function copy(WhereBuilder $builder): WhereBuilder {
        $this->where         = $builder->where;
        $this->addOperator   = $builder->addOperator;
        $this->nextOperator  = $builder->nextOperator;
        $this->usedOperators = $builder->usedOperators;

        $this->limit         = $builder->limit;
        $this->orderBy       = $builder->orderBy;
        $this->groupBy       = $builder->groupBy;

        $this->params        = $builder->params;
        $this->columns       = $builder->columns;
        $this->groups        = $builder->groups;
        $this->orders        = $builder->orders;
        return $this;
    }

    /**
     * Joins the data from another Where Builder
     * @param WhereBuilder $builder
     * @return WhereBuilder
     */
    public function join(WhereBuilder $builder): WhereBuilder {
        $this->where         = Strings::join([ $this->where, $builder->where ], " AND ", withoutEmpty: true);
        $this->addOperator   = $builder->addOperator;
        $this->nextOperator  = $builder->nextOperator;
        $this->usedOperators = Arrays::mergeLists($this->usedOperators, $builder->usedOperators);

        $this->limit         = $builder->limit;
        $this->orderBy       = Strings::join([ $this->orderBy, $builder->orderBy ], ", ", withoutEmpty: true);
        $this->groupBy       = Strings::join([ $this->groupBy, $builder->groupBy ], ", ", withoutEmpty: true);

        $this->params        = Arrays::mergeLists($this->params, $builder->params);
        $this->columns       = Arrays::mergeLists($this->columns, $builder->columns);
        $this->groups        = Arrays::mergeLists($this->groups, $builder->groups);
        $this->orders        = Arrays::mergeLists($this->orders, $builder->orders);
        return $this;
    }



    /**
     * Adds a Where expression
     * @param string                      $column
     * @param Operator|string             $operator
     * @param list<int|string>|int|string $value
     * @param bool                        $caseSensitive
     * @return void
     */
    public function where(
        string $column,
        Operator|string $operator,
        array|int|string $value,
        bool $caseSensitive,
    ): void {
        $operator = Operator::fromValue($operator);
        $compare  = $operator->toString();
        $param    = null;
        $binds    = "?";

        switch ($operator) {
        case Operator::None:
            return;

        case Operator::Equal:
            if (!is_array($value)) {
                $param = $value;
            } elseif (array_is_list($value)) {
                if (count($value) === 1) {
                    $param   = $value[0];
                } elseif (count($value) > 1) {
                    $param   = $value;
                    $compare = "IN";
                    $binds   = $this->createBinds($value);
                }
            }
            break;

        case Operator::NotEqual:
            if (!is_array($value)) {
                $param = $value;
            } elseif (array_is_list($value)) {
                if (count($value) === 1) {
                    $param   = $value[0];
                } elseif (count($value) > 1) {
                    $param   = $value;
                    $compare = "NOT IN";
                    $binds   = $this->createBinds($value);
                }
            }
            break;

        case Operator::In:
            if (!is_array($value)) {
                $param   = $value;
                $compare = "=";
            } elseif (array_is_list($value)) {
                if (count($value) === 1) {
                    $param   = $value[0];
                    $compare = "=";
                } elseif (count($value) > 1) {
                    $param   = $value;
                    $binds   = $this->createBinds($value);
                }
            }
            break;

        case Operator::NotIn:
            if (!is_array($value)) {
                $param   = $value;
                $compare = "<>";
            } elseif (array_is_list($value)) {
                if (count($value) === 1) {
                    $param   = $value[0];
                    $compare = "<>";
                } elseif (count($value) > 1) {
                    $param   = $value;
                    $binds   = $this->createBinds($value);
                }
            }
            break;

        case Operator::GreaterThan:
        case Operator::LessThan:
        case Operator::GreaterOrEqual:
        case Operator::LessOrEqual:
            $param = $value;
            break;

        case Operator::Like:
        case Operator::NotLike:
            if (!is_array($value)) {
                $param = Strings::addPrefixSuffix(trim(strtolower((string)$value)), "%", "%");
            }
            break;

        case Operator::StartsWith:
        case Operator::NotStartsWith:
            if (!is_array($value)) {
                $param   = Strings::addSuffix(trim(strtolower((string)$value)), "%");
                $compare = Strings::replace($operator->toString(), "STARTS", "LIKE");
            }
            break;

        case Operator::EndsWith:
        case Operator::NotEndsWith:
            if (!is_array($value)) {
                $param   = Strings::addPrefix(trim(strtolower((string)$value)), "%");
                $compare = Strings::replace($operator->toString(), "ENDS", "LIKE");
            }
            break;
        }

        if ($param === null) {
            return;
        }


        $prefix = $this->getWhereOperator();
        $suffix = $caseSensitive ? "BINARY" : "";

        $this->where    .= "$prefix $column $compare $suffix $binds ";
        $this->columns[] = $column;

        if (is_array($param)) {
            $this->addParam(...$param);
        } else {
            $this->addParam($param);
        }
    }

    /**
     * Adds an OR Where expression
     * @param string                      $column
     * @param Operator|string             $operator
     * @param list<int|string>|int|string $value
     * @param bool                        $caseSensitive
     * @return void
     */
    public function orWhere(
        string $column,
        Operator|string $operator,
        array|int|string $value,
        bool $caseSensitive,
    ): void {
        // Add an OR before the next expression if needed
        if ($this->addOperator) {
            $this->or();
        }
        $this->where($column, $operator, $value, $caseSensitive);
    }

    /**
     * Adds a Where expression with a value
     * @param string           $expression
     * @param float|int|string ...$values
     * @return void
     */
    public function whereExp(string $expression, float|int|string ...$values): void {
        $operator     = $this->getWhereOperator();
        $this->where .= "$operator $expression ";
        $this->addParam(...$values);
    }

    /**
     * Adds a Where Exists expression
     * @param Query $subQuery
     * @param bool  $notExists Optional.
     * @return void
     */
    public function whereExists(Query $subQuery, bool $notExists = false): void {
        if ($subQuery->isEmpty() || !$subQuery->isSelect()) {
            return;
        }

        $operator = $this->getWhereOperator();
        $not      = $notExists ? "NOT " : "";
        $sql      = $subQuery->columns("1")->toSQL();

        $this->where .= "$operator {$not}EXISTS ($sql) ";
        $this->addParam(...$subQuery->getBindings());
    }

    /**
     * Returns the Where Operator to be placed before the next expression
     * The operator should be "AND" or "OR"
     * @return string
     */
    private function getWhereOperator(): string {
        $result = "";
        if ($this->addOperator) {
            $result = $this->nextOperator;
        }

        // Always add an Operator in the next expression
        $this->addOperator = true;
        return $result;
    }

    /**
     * Creates a list of question marks for the given array
     * @param list<int|string> $array
     * @return string
     */
    private function createBinds(array $array): string {
        $count = count($array);
        $bind  = [];
        for ($i = 0; $i < $count; $i += 1) {
            $bind[] = "?";
        }
        return "(" . Strings::join($bind, ",") . ")";
    }



    /**
     * Adds a Search expression
     * @param list<string>|string $column
     * @param mixed               $value
     * @param Operator|string     $operator        Optional.
     * @param bool                $caseInsensitive Optional.
     * @param bool                $splitValue      Optional.
     * @param string              $splitText       Optional.
     * @param bool                $matchAny        Optional.
     * @return void
     */
    public function search(
        array|string $column,
        mixed $value,
        Operator|string $operator = Operator::Like,
        bool $caseInsensitive = true,
        bool $splitValue = false,
        string $splitText = " ",
        bool $matchAny = false,
    ): void {
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
                $this->where($columnSearch, $operator, $valuePart, caseSensitive: false);
            }
            if ($multiCols) {
                $this->endOr();
            }
            $isFirst = false;
        }
        if ($multiParts) {
            $this->endParen();
        }
    }

    /**
     * Adds a param to the Query
     * @param float|int|string ...$params
     * @return void
     */
    public function addParam(float|int|string ...$params): void {
        foreach ($params as $param) {
            $this->params[] = $param;
        }
    }


    /**
     * Adds an Open Parenthesis
     * @return void
     */
    public function startParen(): void {
        $operator          = $this->getWhereOperator();
        $this->where      .= "$operator (";
        $this->addOperator = false;
    }

    /**
     * Adds a Close Parenthesis
     * @return void
     */
    public function endParen(): void {
        $this->where      .= ") ";
        $this->addOperator = true;
    }

    /**
     * Adds an And
     * @return void
     */
    public function and(): void {
        $this->where      .= " AND ";
        $this->addOperator = false;
    }

    /**
     * Starts an And expression
     * @return void
     */
    public function startAnd(): void {
        $operator              = $this->getWhereOperator();
        $this->where          .= "$operator (";
        $this->usedOperators[] = $this->nextOperator;
        $this->nextOperator    = "AND";
        $this->addOperator     = false;
    }

    /**
     * Ends an And expression
     * @return void
     */
    public function endAnd(): void {
        $this->endAndOr();
    }

    /**
     * Adds an Or
     * @return void
     */
    public function or(): void {
        $this->where      .= " OR ";
        $this->addOperator = false;
    }

    /**
     * Starts an Or expression
     * @return void
     */
    public function startOr(): void {
        $operator              = $this->getWhereOperator();
        $this->where          .= "$operator (";
        $this->usedOperators[] = $this->nextOperator;
        $this->nextOperator    = "OR";
        $this->addOperator     = false;
    }

    /**
     * Ends an Or expression
     * @return void
     */
    public function endOr(): void {
        $this->endAndOr();
    }

    /**
     * Ends an And o Or expression
     * @return void
     */
    private function endAndOr(): void {
        if (Strings::endsWith($this->where, "AND (")) {
            $this->where = Strings::stripEnd($this->where, "AND (");
        } elseif (Strings::endsWith($this->where, "OR (")) {
            $this->where = Strings::stripEnd($this->where, "OR (");
        } elseif (Strings::endsWith($this->where, "(")) {
            $this->where = Strings::stripEnd($this->where, "(");
        } else {
            $this->where .= ") ";
        }

        $this->nextOperator = array_pop($this->usedOperators) ?? "AND";
        $this->addOperator  = true;
    }



    /**
     * Adds a Group By
     * @param string ...$columns
     * @return void
     */
    public function groupBy(string ...$columns): void {
        foreach ($columns as $column) {
            $prefix         = $this->groupBy !== "" ? "," : "";
            $this->groupBy .= "$prefix $column ";
            $this->groups[] = $column;
        }
    }

    /**
     * Adds an Order By
     * @param string $column
     * @param bool   $isASC
     * @return void
     */
    public function orderBy(string $column, bool $isASC): void {
        if ($column !== "") {
            $prefix         = $this->orderBy !== "" ? "," : "";
            $this->orderBy .= "$prefix $column " . ($isASC ? "ASC" : "DESC");
            $this->orders[] = $column;
        }
    }

    /**
     * Adds a Limit
     * @param int      $from
     * @param int|null $to   Optional.
     * @return void
     */
    public function limit(int $from, ?int $to = null): void {
        if ($from !== 0 || ($to !== null && $to !== 0)) {
            if ($to !== null) {
                $this->limit = max($from, 0) . ", " . max($to - $from + 1, 1);
            } else {
                $this->limit = (string)$from;
            }
        }
    }


    /**
     * Returns true if the Query is empty
     * @return bool
     */
    public function isEmpty(): bool {
        return $this->where === "";
    }

    /**
     * Returns true if the given Column is in the Query
     * @param string $column
     * @return bool
     */
    public function hasColumn(string $column): bool {
        return Arrays::contains($this->columns, $column);
    }

    /**
     * Returns true if there is an Order By
     * @param string|null $order Optional.
     * @return bool
     */
    public function hasOrder(?string $order = null): bool {
        if ($order !== null && $order !== "") {
            return Arrays::contains($this->orders, $order);
        }
        return $this->orderBy !== "";
    }

    /**
     * Returns true if there is a Group By
     * @param string|null $group Optional.
     * @return bool
     */
    public function hasGroup(?string $group = null): bool {
        if ($group !== null && $group !== "") {
            return Arrays::contains($this->groups, $group);
        }
        return $this->groupBy !== "";
    }


    /**
     * Returns the Columns
     * @return list<string>
     */
    public function getColumns(): array {
        $result = array_merge($this->columns, $this->groups, $this->orders);
        $result = array_unique($result);
        return array_values($result);
    }

    /**
     * Updates a Column
     * @param string $oldColumn
     * @param string $newColumn
     * @return void
     */
    public function updateColumn(string $oldColumn, string $newColumn): void {
        $this->where   = Strings::replace($this->where, " $oldColumn ", " $newColumn ");
        $this->orderBy = Strings::replace($this->orderBy, " $oldColumn ", " $newColumn ");
        $this->groupBy = Strings::replace($this->groupBy, " $oldColumn ", " $newColumn ");
    }

    /**
     * Returns the complete Query to use with the Database
     * @param bool $addWhere Optional.
     * @return string
     */
    public function toSQL(bool $addWhere = true): string {
        $expression = "";
        if ($this->where !== "") {
            $where       = Strings::stripStart($this->where, "AND ");
            $expression .= ($addWhere ? "WHERE " : "AND ") . $where;
        }
        if ($this->groupBy !== "") {
            $expression .= " GROUP BY {$this->groupBy}";
        }
        if ($this->orderBy !== "") {
            $expression .= " ORDER BY {$this->orderBy}";
        }
        if ($this->limit !== "") {
            $expression .= " LIMIT {$this->limit}";
        }
        return Strings::replacePattern($expression, "!\s+!", " ");
    }

    /**
     * Returns the Params
     * @return list<float|int|string>
     */
    public function getParams(): array {
        return $this->params;
    }
}
