<?php
namespace Framework\Database\Query;

use Framework\Framework;
use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Database\Query\Query;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Selection Builder
 */
class SelectionBuilder {

    private SchemaModel $schemaModel;

    private int $index = 66;

    /** @var array<string,string> */
    private array $keys = [];

    /** @var list<string> */
    private array $selects = [];

    /** @var list<string> */
    private array $joins = [];

    /** @var list<array<string,mixed>> */
    private array $request = [];


    /**
     * Creates a new Selection instance
     * @param SchemaModel $schemaModel
     */
    private function __construct(SchemaModel $schemaModel) {
        $this->schemaModel = $schemaModel;
    }

    /**
     * Creates a new Selection instance
     * @param SchemaModel $schemaModel
     * @return SelectionBuilder
     */
    public static function create(SchemaModel $schemaModel): SelectionBuilder {
        return new SelectionBuilder($schemaModel);
    }



    /**
     * Adds the Fields to the Selects
     * @param bool $decrypted Optional.
     * @return SelectionBuilder
     */
    public function addFields(bool $decrypted = false): SelectionBuilder {
        $masterKey = Config::getDbKey();
        $mainKey   = $this->schemaModel->tableName;

        if ($this->schemaModel->hasID) {
            $this->selects[] = "$mainKey.{$this->schemaModel->idDbName} AS id";
        }
        foreach ($this->schemaModel->fields as $field) {
            $fieldName = "$mainKey.{$field->name}";
            if ($decrypted && $field->type === FieldType::Encrypt) {
                $this->selects[] = "CAST(AES_DECRYPT($fieldName, '$masterKey') AS CHAR(255)) {$field->name}Decrypt";
            } elseif ($field->dbName !== $field->name) {
                $this->selects[] = "$mainKey.$field->dbName AS $field->name";
            } else {
                $this->selects[] = $fieldName;
            }
        }
        return $this;
    }

    /**
     * Adds the Expressions to the Selects
     * @return SelectionBuilder
     */
    public function addExpressions(): SelectionBuilder {
        foreach ($this->schemaModel->expressions as $expression) {
            $this->selects[] = "({$expression->expression}) AS {$expression->name}";
        }
        return $this;
    }

    /**
     * Adds extra Selects
     * @param list<string>|string $selects
     * @param bool                $addMainKey Optional.
     * @return SelectionBuilder
     */
    public function addSelects(array|string $selects, bool $addMainKey = false): SelectionBuilder {
        $selects = Arrays::toStrings($selects, withoutEmpty: true);
        if (Arrays::isEmpty($selects)) {
            return $this;
        }

        foreach ($selects as $select) {
            if ($addMainKey) {
                $this->selects[] = $this->schemaModel->getKey($select);
            } else {
                $this->selects[] = $select;
            }
        }
        return $this;
    }

    /**
     * Adds the Joins
     * @param list<string> $extraJoins  Optional.
     * @param bool         $withSelects Optional.
     * @return SelectionBuilder
     */
    public function addJoins(array $extraJoins = [], bool $withSelects = true): SelectionBuilder {
        foreach ($this->schemaModel->relations as $relation) {
            $this->joins[] = $relation->getExpression();

            if ($withSelects) {
                $tableName = $relation->getDbTableName();
                foreach ($relation->fields as $field) {
                    $this->selects[] = "$tableName.{$field->dbName} AS {$field->prefixName}";
                }
            }
        }
        foreach ($extraJoins as $extraJoin) {
            $this->joins[] = $extraJoin;
        }
        return $this;
    }

    /**
     * Adds the Counts
     * @return SelectionBuilder
     */
    public function addCounts(): SelectionBuilder {
        foreach ($this->schemaModel->counts as $count) {
            $asTable                  = chr($this->index);
            $this->joins[]            = $count->getExpression($asTable, $this->schemaModel->tableName);
            $this->selects[]          = $count->getSelect($asTable);
            $this->keys[$count->name] = $asTable;
            $this->index            += 1;
        }
        return $this;
    }



    /**
     * Returns the Selection as an SQL Expression
     * @param Query $query
     * @return string
     */
    public function toSQL(Query $query): string {
        $this->setTableKeys($query);

        $mainKey    = $this->schemaModel->tableName;
        $selects    = Strings::join($this->selects, ", ");
        $joins      = Strings::join($this->joins, " ");
        $where      = $query->get();
        $expression = "SELECT $selects FROM `$mainKey` $joins $where";

        foreach ([ "FROM", "LEFT JOIN", "WHERE", "ORDER BY", "LIMIT" ] as $key) {
            $expression = Strings::replace($expression, $key, "\n$key");
        }
        return $expression;
    }

    /**
     * Sets the Table Keys to the condition
     * @param Query $query
     * @return void
     */
    private function setTableKeys(Query $query): void {
        $columns = $query->getWhereColumns();
        $mainKey = $this->schemaModel->tableName;

        foreach ($columns as $column) {
            $found = false;
            foreach ($this->schemaModel->fields as $field) {
                if ($column === $field->dbName) {
                    $query->updateWhereColumn($column, "$mainKey.{$field->dbName}");
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                foreach ($this->schemaModel->relations as $relation) {
                    foreach ($relation->fields as $field) {
                        if ($column === $field->dbName) {
                            $tableName = $relation->getDbTableName();
                            $query->updateWhereColumn($column, "$tableName.{$field->dbName}");
                            $found = true;
                            break;
                        }
                    }
                }
            }

            if (!$found) {
                foreach ($this->schemaModel->counts as $count) {
                    if (isset($this->keys[$count->name]) && $this->keys[$count->name] !== "") {
                        $joinKey = $this->keys[$count->name];
                        if ($column === $count->name) {
                            $query->updateWhereColumn($column, $count->getSelect($joinKey));
                            $found = true;
                            break;
                        }
                    }
                }
            }

            if (!$found) {
                foreach ($this->schemaModel->expressions as $expression) {
                    if ($column === $expression->name) {
                        $query->updateWhereColumn($column, "({$expression->expression})");
                        $found = true;
                        break;
                    }
                }
            }
        }
    }



    /**
     * Does a Request to the Query
     * @param Query $query
     * @return SelectionBuilder
     */
    public function request(Query $query): SelectionBuilder {
        $expression    = $this->toSQL($query);
        $this->request = Framework::getDatabase()->queryData($expression, $query);
        return $this;
    }

    /**
     * Returns the Request Result
     * @return list<array<string,mixed>>
     */
    public function getResult(): array {
        return $this->request;
    }

    /**
     * Generates the Result from the Request
     * @param list<string>|string|null $extras Optional.
     * @return list<array<string,mixed>>
     */
    public function resolve(array|string|null $extras = null): array {
        $result = [];

        foreach ($this->request as $row) {
            $fields = [];
            if ($this->schemaModel->hasID) {
                if (isset($row["id"])) {
                    $fields["id"] = $row["id"];
                } elseif (isset($row[$this->schemaModel->idDbName])) {
                    $fields["id"] = $row[$this->schemaModel->idDbName];
                } elseif (isset($row[$this->schemaModel->idName])) {
                    $fields["id"] = $row[$this->schemaModel->idName];
                }
            }

            // Parse the Fields
            foreach ($this->schemaModel->fields as $field) {
                $values = $field->toValues($row);
                $fields = array_merge($fields, $values);
            }

            // Parse the Expressions
            foreach ($this->schemaModel->expressions as $expression) {
                $values = $expression->toValues($row);
                $fields = array_merge($fields, $values);
            }

            // Parse the Relations
            foreach ($this->schemaModel->relations as $relation) {
                $values = $relation->toValues($row);
                $fields = array_merge($fields, $values);
            }

            // Parse the Counts
            foreach ($this->schemaModel->counts as $count) {
                $fields[$count->name] = $count->getValue($row);
            }

            // Parse the Extras
            if (!Arrays::isEmpty($extras)) {
                $extras = Arrays::toStrings($extras);
                foreach ($extras as $extra) {
                    if (isset($row[$extra])) {
                        $fields[$extra] = $row[$extra];
                    }
                }
            }

            $result[] = $fields;
        }
        return $result;
    }
}
