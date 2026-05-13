<?php
namespace Framework\Database\Query;

use Framework\IO\Value\Value;
use Framework\Database\Query\QueryMode;
use Framework\Database\Query\Assign;
use Framework\Database\Type\Column;
use Framework\Date\Date;
use Framework\Enum\Enum;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Query Builder
 * phpcs:ignore Generic.Files.LineLength.TooLong
 * @phpstan-type QueryValue Query|Value|Enum|Dictionary|Date|JsonSerializable|array<int|string,mixed>|bool|Assign|float|int|string
 * @phpstan-type SelectValue Query|Column|string
 */
class QueryBuilder {

    private QueryMode $mode;

    private WhereBuilder $whereBuilder;

    private string $tableName;

    private string $asTable;

    private ?Query $fromQuery = null;

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
     * @param string       $asTable
     * @param WhereBuilder $whereBuilder
     */
    public function __construct(QueryMode $mode, string $tableName, string $asTable, WhereBuilder $whereBuilder) {
        $this->mode         = $mode;
        $this->tableName    = $tableName;
        $this->asTable      = $asTable;
        $this->whereBuilder = $whereBuilder;
    }


    /**
     * Sets the From in the Query
     * @param Query|string $queryOrTable
     * @param string       $as           Optional.
     * @return void
     */
    public function setFrom(Query|string $queryOrTable, string $as = ""): void {
        if ($queryOrTable instanceof Query) {
            $this->fromQuery = $queryOrTable;
        } else {
            $this->tableName = $queryOrTable;
        }
        $this->asTable = $as;
    }

    /**
     * Adds a join to the Query Builder
     * @param string $join
     * @return void
     */
    public function addJoin(string $join): void {
        $this->joins[] = $join;
    }

    /**
     * Adds a select to the Select Builder
     * @param SelectValue $select
     * @param string      $as     Optional.
     * @return void
     */
    public function addSelect(mixed $select, string $as = ""): void {
        if ($select instanceof Query) {
            $select = "({$select->toSQL()})";
        } elseif ($select instanceof Column) {
            $select = $select->name();
        }
        if ($as !== "") {
            $select .= " AS $as";
        }
        $this->selects[] = $select;
    }

    /**
     * Sets a field to be Inserted or Updated
     * @param string     $field
     * @param QueryValue $value
     * @return void
     */
    public function setField(string $field, mixed $value): void {
        // Convert a Query to SQL
        if ($value instanceof Query) {
            $this->fields[$field] = $value->toAssign();

        // Convert a Value to an integer or string
        } elseif ($value instanceof Value) {
            if ($value->exists()) {
                $this->fields[$field] = $value->toDatabase();
            }

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
        } elseif (is_array($value) || $value instanceof JsonSerializable) {
            $this->fields[$field] = JSON::encode($value);

        // Convert any boolean to an integer
        } elseif (is_bool($value)) {
            $this->fields[$field] = $value ? 1 : 0;

        // Set any Assign, float, int or string as is
        } else {
            $this->fields[$field] = $value;
        }
    }

    /**
     * Sets multiple fields to be Inserted or Updated
     * @param array<string,QueryValue> $fields
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
     * Returns the Table Name
     * @return string
     */
    public function getTableName(): string {
        return $this->tableName;
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
        $result = match ($this->mode) {
            QueryMode::Select   => $this->toSelectSQL($forDebug),
            QueryMode::Insert   => $this->toInsertSQL(),
            QueryMode::Replace  => $this->toReplaceSQL(),
            QueryMode::Update   => $this->toUpdateSQL(),
            QueryMode::Delete   => $this->toDeleteSQL(),
            QueryMode::Truncate => $this->toTruncateSQL(),
        };

        $result = Strings::replace($result, "\n", " ");
        $result = Strings::replacePattern($result, "!\s+!", " ");
        return $result;
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
    private function toSelectSQL(bool $forDebug = false): string {
        $glue = $forDebug ? ",\n    " : ", ";

        $expression = [ "SELECT" ];
        if (count($this->selects) === 0) {
            $expression[] = "*";
        } else {
            $expression[] = Strings::join($this->selects, $glue);
        }

        if ($this->fromQuery !== null) {
            $expression[] = "FROM ({$this->fromQuery->toSQL()})";
        } else {
            $expression[] = "FROM `{$this->tableName}`";
        }
        if ($this->asTable !== "") {
            $expression[] = "AS {$this->asTable}";
        }
        foreach ($this->joins as $join) {
            $expression[] = $join;
        }

        $expression[] = $this->whereBuilder->toSQL(addWhere: true);
        return Strings::join($expression, " ");
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
        $expression[] = "UPDATE `{$this->tableName}`";
        if ($this->asTable !== "") {
            $expression[] = "AS {$this->asTable}";
        }
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
        $expression = [ "DELETE" ];
        if (count($this->joins) > 0) {
            $expression[] = "`{$this->tableName}`";
        }

        $expression[] = "FROM `{$this->tableName}`";
        foreach ($this->joins as $join) {
            $expression[] = $join;
        }

        $expression[] = $this->whereBuilder->toSQL(addWhere: true);
        return Strings::join($expression, " ");
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
        if ($this->fromQuery !== null) {
            $bindings = $this->fromQuery->getBindings();
        }
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

    /**
     * Interpolates the Params in the given SQL expression
     * @param string                 $expression
     * @param list<float|int|string> $bindings   Optional.
     * @return string
     */
    public function interpolateParams(string $expression, array $bindings): string {
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
