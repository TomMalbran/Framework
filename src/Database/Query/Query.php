<?php
namespace Framework\Database\Query;

use Framework\Framework;
use Framework\Database\Database;
use Framework\Database\Query\QueryMode;
use Framework\Database\Query\Operator;
use Framework\Database\Query\QueryBuilder;
use Framework\Database\Query\WhereBuilder;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

/**
 * The Database Query
 */
class Query {

    private QueryBuilder $queryBuilder;
    private WhereBuilder $whereBuilder;

    private QueryMode $mode = QueryMode::Select;


    /**
     * Creates a new Query instance
     * @param QueryMode $mode      Optional.
     * @param string    $tableName Optional.
     */
    public function __construct(QueryMode $mode = QueryMode::Select, string $tableName = "") {
        $this->whereBuilder = new WhereBuilder();
        $this->queryBuilder = new QueryBuilder($mode, $tableName, $this->whereBuilder);

        $this->mode = $mode;
    }

    /**
     * Creates a new SELECT Query for the given table
     * @param string $tableName
     * @return Query
     */
    public static function select(string $tableName): Query {
        return new Query(QueryMode::Select, $tableName);
    }

    /**
     * Creates a new INSERT Query for the given table
     * @param string $tableName
     * @return Query
     */
    public static function insert(string $tableName): Query {
        return new Query(QueryMode::Insert, $tableName);
    }

    /**
     * Creates a new REPLACE Query for the given table
     * @param string $tableName
     * @return Query
     */
    public static function replace(string $tableName): Query {
        return new Query(QueryMode::Replace, $tableName);
    }

    /**
     * Creates a new UPDATE Query for the given table
     * @param string $tableName
     * @return Query
     */
    public static function update(string $tableName): Query {
        return new Query(QueryMode::Update, $tableName);
    }

    /**
     * Creates a new DELETE Query for the given table
     * @param string $tableName
     * @return Query
     */
    public static function delete(string $tableName): Query {
        return new Query(QueryMode::Delete, $tableName);
    }

    /**
     * Creates a new TRUNCATE Query for the given table
     * @param string $tableName
     * @return Query
     */
    public static function truncate(string $tableName): Query {
        return new Query(QueryMode::Truncate, $tableName);
    }



    /**
     * Adds a Join to the Query
     * @param string $table
     * @param string $condition
     * @param string $type      Optional.
     * @return Query
     */
    public function join(string $table, string $condition, string $type = "LEFT"): Query {
        $this->queryBuilder->addJoin($table, $condition, $type);
        return $this;
    }

    /**
     * Sets the Columns used in a SELECT query
     * @param string ...$selects
     * @return Query
     */
    public function columns(string ...$selects): Query {
        foreach ($selects as $select) {
            $this->queryBuilder->addSelect($select);
        }
        return $this;
    }

    /**
     * Sets a field to be Inserted or Updated
     * @param string $field
     * @param mixed  $value
     * @return Query
     */
    public function set(string $field, mixed $value): Query {
        $this->queryBuilder->setField($field, $value);
        return $this;
    }

    /**
     * Sets a field to be Inserted or Updated using a raw SQL expression
     * @param string $field
     * @param string $sql
     * @return Query
     */
    public function setExp(string $field, string $sql): Query {
        $this->queryBuilder->setField($field, Assign::exp($sql));
        return $this;
    }

    /**
     * Sets multiple fields to be Inserted or Updated
     * @param array<string,mixed> $fields
     * @return Query
     */
    public function fields(array $fields): Query {
        $this->queryBuilder->setFields($fields);
        return $this;
    }



    /**
     * Copies the Where clause from another Query instance
     * @param Query|null $query Optional.
     * @return Query
     */
    public function query(?Query $query = null): Query {
        if ($query !== null) {
            $this->whereBuilder->copy($query->whereBuilder);
        }
        return $this;
    }

    /**
     * Adds a Where expression
     * @param string                      $column
     * @param Operator|string             $operator
     * @param list<int|string>|int|string $value
     * @param bool                        $caseSensitive Optional.
     * @param bool|null                   $condition     Optional.
     * @return Query
     */
    public function where(
        string $column,
        Operator|string $operator,
        array|int|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): Query {
        if ($condition !== null && !$condition) {
            return $this;
        }
        $this->whereBuilder->where($column, $operator, $value, $caseSensitive);
        return $this;
    }

    /**
     * Adds an OR Where expression
     * @param string                      $column
     * @param Operator|string             $operator
     * @param list<int|string>|int|string $value
     * @param bool                        $caseSensitive Optional.
     * @param bool|null                   $condition     Optional.
     * @return Query
     */
    public function orWhere(
        string $column,
        Operator|string $operator,
        array|int|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): Query {
        if ($condition !== null && !$condition) {
            return $this;
        }
        $this->whereBuilder->orWhere($column, $operator, $value, $caseSensitive);
        return $this;
    }

    /**
     * Adds a Where expression if the value is not empty
     * @param string                           $column
     * @param Operator|string                  $operator
     * @param list<int|string>|int|string|null $value
     * @param bool|null                        $condition     Optional.
     * @param bool                             $caseSensitive Optional.
     * @return Query
     */
    public function whereIf(
        string $column,
        Operator|string $operator,
        array|int|string|null $value,
        ?bool $condition = null,
        bool $caseSensitive = false,
    ): Query {
        if ($condition === true && $value !== null) {
            $this->where($column, $operator, $value, $caseSensitive);
        } elseif ($condition === null && $value !== null && !Arrays::isEmpty($value)) {
            $this->where($column, $operator, $value, $caseSensitive);
        }
        return $this;
    }

    /**
     * Adds a Where expression with a value
     * @param string           $expression
     * @param float|int|string ...$values
     * @return Query
     */
    public function whereExp(string $expression, float|int|string ...$values): Query {
        $this->whereBuilder->whereExp($expression, ...$values);
        return $this;
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
     * @return Query
     */
    public function search(
        array|string $column,
        mixed $value,
        Operator|string $operator = Operator::Like,
        bool $caseInsensitive = true,
        bool $splitValue = false,
        string $splitText = " ",
        bool $matchAny = false,
    ): Query {
        if (Arrays::isEmpty($value)) {
            return $this;
        }

        $this->whereBuilder->search($column, $value, $operator, $caseInsensitive, $splitValue, $splitText, $matchAny);
        return $this;
    }

    /**
     * Adds a param to the Query
     * @param int|string $param
     * @return Query
     */
    public function addParam(int|string $param): Query {
        $this->whereBuilder->addParam($param);
        return $this;
    }


    /**
     * Adds an Open Parenthesis
     * @return Query
     */
    public function startParen(): Query {
        $this->whereBuilder->startParen();
        return $this;
    }

    /**
     * Adds a Close Parenthesis
     * @return Query
     */
    public function endParen(): Query {
        $this->whereBuilder->endParen();
        return $this;
    }

    /**
     * Adds an And
     * @return Query
     */
    public function and(): Query {
        $this->whereBuilder->and();
        return $this;
    }

    /**
     * Starts an And expression
     * @return Query
     */
    public function startAnd(): Query {
        $this->whereBuilder->startAnd();
        return $this;
    }

    /**
     * Ends an And expression
     * @return Query
     */
    public function endAnd(): Query {
        $this->whereBuilder->endAnd();
        return $this;
    }

    /**
     * Adds an Or
     * @return Query
     */
    public function or(): Query {
        $this->whereBuilder->or();
        return $this;
    }

    /**
     * Starts an Or expression
     * @return Query
     */
    public function startOr(): Query {
        $this->whereBuilder->startOr();
        return $this;
    }

    /**
     * Ends an Or expression
     * @return Query
     */
    public function endOr(): Query {
        $this->whereBuilder->endOr();
        return $this;
    }


    /**
     * Adds a Group By
     * @param string ...$columns
     * @return Query
     */
    public function groupBy(string ...$columns): Query {
        $this->whereBuilder->groupBy(...$columns);
        return $this;
    }

    /**
     * Adds an Order By
     * @param string $column
     * @param bool   $isASC
     * @return Query
     */
    public function orderBy(string $column, bool $isASC): Query {
        $this->whereBuilder->orderBy($column, $isASC);
        return $this;
    }

    /**
     * Adds a Limit
     * @param int      $from
     * @param int|null $to   Optional.
     * @return Query
     */
    public function limit(int $from, ?int $to = null): Query {
        $this->whereBuilder->limit($from, $to);
        return $this;
    }

    /**
     * Adds a limit using pagination
     * @param int $page   Optional.
     * @param int $amount Optional.
     * @return Query
     */
    public function paginate(int $page = 0, int $amount = 100): Query {
        $from = $page * $amount;
        $to   = $from + $amount - 1;
        return $this->limit($from, $to);
    }

    /**
     * Adds an If Null expression
     * @param int|string $value
     * @return Query
     */
    public function ifNull(int|string $value): Query {
        $this->queryBuilder->setIfNull($value);
        return $this;
    }



    /**
     * Returns true if the Field exists
     * @param string $field
     * @return bool
     */
    public function hasField(string $field): bool {
        return $this->queryBuilder->hasField($field);
    }

    /**
     * Returns the Fields
     * @return Dictionary
     */
    public function getFields(): Dictionary {
        return $this->queryBuilder->getFields();
    }

    /**
     * Returns true if the Query is empty
     * @return bool
     */
    public function isEmpty(): bool {
        return $this->whereBuilder->isEmpty();
    }

    /**
     * Returns true if the Query is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return !$this->isEmpty();
    }

    /**
     * Returns true if the given Column is in the Query
     * @param string $column
     * @return bool
     */
    public function hasWhereColumn(string $column): bool {
        return $this->whereBuilder->hasColumn($column);
    }

    /**
     * Returns true if there is an Order By
     * @param string|null $order Optional.
     * @return bool
     */
    public function hasOrder(?string $order = null): bool {
        return $this->whereBuilder->hasOrder($order);
    }

    /**
     * Returns true if there is a Group By
     * @param string|null $group Optional.
     * @return bool
     */
    public function hasGroup(?string $group = null): bool {
        return $this->whereBuilder->hasGroup($group);
    }

    /**
     * Returns the Columns
     * @return list<string>
     */
    public function getWhereColumns(): array {
        return $this->whereBuilder->getColumns();
    }

    /**
     * Updates a Column
     * @param string $oldColumn
     * @param string $newColumn
     * @return Query
     */
    public function updateWhereColumn(string $oldColumn, string $newColumn): Query {
        $this->whereBuilder->updateColumn($oldColumn, $newColumn);
        return $this;
    }



    /**
     * Returns the complete Query to use with the Database
     * @param bool $addWhere Optional.
     * @return string
     */
    public function get(bool $addWhere = true): string {
        return $this->whereBuilder->toSQL($addWhere);
    }

    /**
     * Returns the Params
     * @return list<float|int|string>
     */
    public function getParams(): array {
        return $this->whereBuilder->getParams();
    }



    /**
     * Executes a SELECT query returning all the values in a Dictionary
     * @param Database|null $db Optional.
     * @return Dictionary
     */
    public function getAll(?Database $db = null): Dictionary {
        if ($this->mode !== QueryMode::Select) {
            return new Dictionary();
        }

        $db         = $db ?? Framework::getDatabase();
        $expression = $this->queryBuilder->toSelectSQL();
        $bindings   = $this->queryBuilder->getBindings();
        $data       = $db->getData($expression, $bindings);
        return new Dictionary($data);
    }

    /**
     * Executes a SELECT query returning the first value in a Dictionary
     * @param Database|null $db Optional.
     * @return Dictionary
     */
    public function getOne(?Database $db = null): Dictionary {
        return $this->limit(1)->getAll($db)->getFirst();
    }

    /**
     * Executes a SELECT query returning the first value of a Column
     * @param string        $column
     * @param Database|null $db     Optional.
     * @return int
     */
    public function getInt(string $column, ?Database $db = null): int {
        return $this->getOne($db)->getInt($column);
    }

    /**
     * Executes a SELECT query returning the first value of a Column
     * @param string        $column
     * @param Database|null $db     Optional.
     * @return string
     */
    public function getString(string $column, ?Database $db = null): string {
        return $this->getOne($db)->getString($column);
    }

    /**
     * Executes an INSERT, REPLACE or UPDATE query
     * @param Database|null $db Optional.
     * @return int
     */
    public function execute(?Database $db = null): int {
        if ($this->mode === QueryMode::Select) {
            return 0;
        }
        $db = $db ?? Framework::getDatabase();

        $expression = $this->queryBuilder->toSQL();
        $bindings   = $this->queryBuilder->getBindings();
        $result     = $db->execute($expression, $bindings);

        if ($this->mode === QueryMode::Insert) {
            return $result ? $db->getInsertID() : 0;
        }
        return $result ? 1 : 0;
    }

    /**
     * Returns the SQL to add it as an assignment
     * @return Assign
     */
    public function toAssign(): Assign {
        return $this->queryBuilder->toAssign();
    }

    /**
     * Returns the complete SQL expression of the Query
     * @return string
     */
    public function toSQL(): string {
        $expression = $this->queryBuilder->toSQL(forDebug: true);
        $bindings   = $this->queryBuilder->getBindings();

        // Interpolate the expression with the bindings
        foreach ($bindings as $value) {
            if (is_string($value)) {
                $value = "'$value'";
            } else {
                $value = Strings::toString($value);
            }
            $expression = Strings::replacePattern($expression, '/\?/', $value, 1);
        }

        // Add new lines for better readability
        foreach ([ "FROM", "LEFT JOIN", "VALUES", "SET", "WHERE", "ORDER BY", "GROUP BY", "LIMIT" ] as $key) {
            $expression = Strings::replace($expression, "$key ", "\n$key ");
        }

        return $expression;
    }
}
