<?php
namespace Framework\Database\Query;

use Framework\Database\Query\QueryMode;
use Framework\Database\Query\Assign;
use Framework\Date\Date;
use Framework\Enum\Enum;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Query Builder
 */
class QueryBuilder {

    private QueryMode $mode;

    private WhereBuilder $whereBuilder;

    private string $tableName;

    /** @var list<string> */
    private array $joins = [];

    /** @var list<string> */
    private array $selects = [];

    /** @var array<string,Assign|float|int|string> */
    private array $fields = [];

    private int|string|null $ifNullValue = null;



    /**
     * Creates a new Query Builder instance
     * @param QueryMode    $mode
     * @param string       $tableName
     * @param WhereBuilder $whereBuilder
     */
    public function __construct(QueryMode $mode, string $tableName, WhereBuilder $whereBuilder) {
        $this->mode         = $mode;
        $this->tableName    = $tableName;
        $this->whereBuilder = $whereBuilder;
    }


    /**
     * Adds a join to the Query Builder
     * @param string $table
     * @param string $condition
     * @param string $type      Optional.
     * @return void
     */
    public function addJoin(string $table, string $condition, string $type = "LEFT"): void {
        $this->joins[] = "$type JOIN $table ON ($condition)";
    }

    /**
     * Adds a select to the Select Builder
     * @param string $select
     * @return void
     */
    public function addSelect(string $select): void {
        $this->selects[] = $select;
    }

    /**
     * Sets a field to be Inserted or Updated
     * @param string $field
     * @param mixed  $value
     * @return void
     */
    public function setField(string $field, mixed $value): void {
        // Convert a Query to SQL
        if ($value instanceof Query) {
            $this->fields[$field] = $value->toAssign();

        // Convert any Enum to a string
        } elseif ($value instanceof Enum) {
            $this->fields[$field] = $value->toString();

        // Encode any Dictionary to JSON
        } elseif ($value instanceof Dictionary) {
            $this->fields[$field] = $value->toJSON();

        // Convert any Date to a timestamp
        } elseif ($value instanceof Date) {
            $this->fields[$field] = $value->toTime();

        // Encode any array to JSON
        } elseif (is_array($value)) {
            $this->fields[$field] = JSON::encode($value);

        // Convert any boolean to an integer
        } elseif (is_bool($value)) {
            $this->fields[$field] = $value ? 1 : 0;

        // Set any Assign, float, int or string as is
        } elseif ($value instanceof Assign || is_float($value) || is_int($value) || is_string($value)) {
            $this->fields[$field] = $value;
        }
    }

    /**
     * Sets multiple fields to be Inserted or Updated
     * @param array<string,mixed> $fields
     * @return void
     */
    public function setFields(array $fields): void {
        foreach ($fields as $field => $value) {
            $this->setField($field, $value);
        }
    }

    /**
     * Sets the If Null value
     * @param int|string $value
     * @return void
     */
    public function setIfNull(int|string $value): void {
        $this->ifNullValue = $value;
    }



    /**
     * Returns true if the Field exists
     * @param string $field
     * @return bool
     */
    public function hasField(string $field): bool {
        return !Arrays::isEmpty($this->fields, $field);
    }

    /**
     * Returns the Fields
     * @return Dictionary
     */
    public function getFields(): Dictionary {
        return new Dictionary($this->fields);
    }



    /**
     * Returns the complete SQL expression of the Query
     * @param bool $forDebug Optional.
     * @return string
     */
    public function toSQL(bool $forDebug = false): string {
        return match ($this->mode) {
            QueryMode::Select   => $this->toSelectSQL($forDebug),
            QueryMode::Insert   => $this->toInsertSQL(),
            QueryMode::Replace  => $this->toReplaceSQL(),
            QueryMode::Update   => $this->toUpdateSQL(),
            QueryMode::Delete   => $this->toDeleteSQL(),
            QueryMode::Truncate => $this->toTruncateSQL(),
        };
    }

    /**
     * Returns the SQL to add it as an assignment
     * @return Assign
     */
    public function toAssign(): Assign {
        $expression = $this->toSQL();
        $bindings   = $this->getBindings();

        $expression = "({$expression})";
        if ($this->ifNullValue !== null) {
            $expression = "IFNULL($expression, {$this->ifNullValue})";
        }
        return Assign::exp($expression, $bindings);
    }

    /**
     * Converts the Select Builder to an SQL string
     * @param bool $forDebug Optional.
     * @return string
     */
    public function toSelectSQL(bool $forDebug = false): string {
        $glue = $forDebug ? ",\n    " : ", ";

        $expression = "SELECT ";
        if (count($this->selects) === 0) {
            $expression .= "*";
        } else {
            $expression .= Strings::join($this->selects, $glue);
        }

        $expression .= " FROM {$this->tableName} ";
        foreach ($this->joins as $join) {
            $expression .= " $join ";
        }

        $expression .= $this->whereBuilder->toSQL(addWhere: true);
        return $expression;
    }

    /**
     * Converts the Query Builder to an Insert SQL string
     * @return string
     */
    private function toInsertSQL(): string {
        $values = [];
        foreach ($this->fields as $field => $value) {
            $values[] = $value instanceof Assign ? $value->toSQL($field) : "?";
        }

        $expression  = "INSERT INTO `{$this->tableName}` ";
        $expression .= "(`" . Strings::joinKeys($this->fields, "`, `") . "`) ";
        $expression .= "VALUES (" . Strings::join($values, ", ") . ")";

        return $expression;
    }

    /**
     * Converts the Query Builder to a Replace SQL string
     * @return string
     */
    private function toReplaceSQL(): string {
        $values = [];
        foreach ($this->fields as $field => $value) {
            $values[] = $value instanceof Assign ? $value->toSQL($field) : "?";
        }

        $expression  = "REPLACE INTO `{$this->tableName}` ";
        $expression .= "(`" . Strings::joinKeys($this->fields, "`, `") . "`) ";
        $expression .= "VALUES (" . Strings::join($values, ", ") . ")";

        return $expression;
    }

    /**
     * Converts the Query Builder to an Update SQL string
     * @return string
     */
    private function toUpdateSQL(): string {
        $assigns = [];
        foreach ($this->fields as $field => $value) {
            if ($value instanceof Assign) {
                $assigns[] = "`$field` = " . $value->toSQL($field);
            } else {
                $assigns[] = "`$field` = ?";
            }
        }

        $expression   = [];
        $expression[] = "UPDATE {$this->tableName}";
        foreach ($this->joins as $join) {
            $expression[] = $join;
        }

        $expression[] = "SET " . Strings::join($assigns, ", ");
        $expression[] = $this->whereBuilder->toSQL(addWhere: true);

        return Strings::join($expression, " ");
    }

    /**
     * Converts the Query Builder to a Delete SQL string
     * @return string
     */
    private function toDeleteSQL(): string {
        $expression = "DELETE ";
        if (count($this->joins) > 0) {
            $expression .= "`{$this->tableName}` ";
        }

        $expression .= "FROM `{$this->tableName}`";
        foreach ($this->joins as $join) {
            $expression .= " $join ";
        }

        $expression .= " " . $this->whereBuilder->toSQL(addWhere: true);
        return $expression;
    }

    /**
     * Converts the Query Builder to a Truncate SQL string
     * @return string
     */
    private function toTruncateSQL(): string {
        return "TRUNCATE TABLE `{$this->tableName}`";
    }



    /**
     * Returns the bindings for the Query Builder
     * @return list<float|int|string>
     */
    public function getBindings(): array {
        $bindings = [];
        foreach ($this->fields as $value) {
            if ($value instanceof Assign) {
                foreach ($value->getParams() as $param) {
                    $bindings[] = $param;
                }
            } else {
                $bindings[] = $value;
            }
        }

        $bindings = Arrays::mergeLists($bindings, $this->whereBuilder->getParams());
        return $bindings;
    }
}
