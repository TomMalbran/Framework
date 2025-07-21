<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Selection
 */
class Selection {

    private SchemaModel $schemaModel;

    private int $index   = 66;

    /** @var string[] */
    private array $tables  = [];

    /** @var string[] */
    private array $keys    = [];

    /** @var string[] */
    private array $selects = [];

    /** @var string[] */
    private array $joins   = [];

    /** @var array<string,mixed>[] */
    private array $request = [];


    /**
     * Creates a new Selection instance
     * @param SchemaModel $schemaModel
     */
    public function __construct(SchemaModel $schemaModel) {
        $this->schemaModel = $schemaModel;
        $this->tables      = [ $schemaModel->tableName ];
    }



    /**
     * Adds the Fields to the Selects
     * @param boolean $decrypted Optional.
     * @return Selection
     */
    public function addFields(bool $decrypted = false): Selection {
        $masterKey = Config::getDbKey();
        $mainKey   = $this->schemaModel->tableName;

        if ($this->schemaModel->hasID) {
            $this->selects[] = "$mainKey.{$this->schemaModel->idDbName} AS id";
        }
        foreach ($this->schemaModel->fields as $field) {
            if ($decrypted && $field->type === FieldType::Encrypt) {
                $this->selects[] = "CAST(AES_DECRYPT($mainKey.$field->name, '$masterKey') AS CHAR(255)) {$field->name}Decrypt";
            } elseif ($field->dbName !== $field->name) {
                $this->selects[] = "$mainKey.$field->dbName AS $field->name";
            } else {
                $this->selects[] = "$mainKey.$field->name";
            }
        }
        return $this;
    }

    /**
     * Adds the Expressions to the Selects
     * @return Selection
     */
    public function addExpressions(): Selection {
        foreach ($this->schemaModel->expressions as $expression) {
            $this->selects[] = "({$expression->expression}) AS {$expression->name}";
        }
        return $this;
    }

    /**
     * Adds extra Selects
     * @param string[]|string $selects
     * @param boolean         $addMainKey Optional.
     * @return Selection
     */
    public function addSelects(array|string $selects, bool $addMainKey = false): Selection {
        $selects = Arrays::toStrings($selects);
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
     * @param string[] $extraJoins  Optional.
     * @param boolean  $withSelects Optional.
     * @return Selection
     */
    public function addJoins(array $extraJoins = [], bool $withSelects = true): Selection {
        foreach ($this->schemaModel->relations as $relation) {
            $tableName     = $relation->getDbTableName(false);
            $ownerTableName = $relation->getOwnerTableName();

            if ($ownerTableName !== "") {
                $asTable = $ownerTableName;
            } elseif (Arrays::contains($this->tables, $tableName)) {
                $asTable = chr($this->index++);
            } else {
                $asTable        = $tableName;
                $this->tables[] = $tableName;
            }

            $this->joins[]               = $relation->getExpression($asTable, $this->schemaModel->tableName);
            $this->keys[$relation->name] = $asTable;

            if ($withSelects) {
                foreach ($relation->fields as $field) {
                    $this->selects[] = "$asTable.{$field->dbName} AS $field->prefixName";
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
     * @return Selection
     */
    public function addCounts(): Selection {
        foreach ($this->schemaModel->counts as $count) {
            $asTable                  = chr($this->index++);
            $this->joins[]            = $count->getExpression($asTable, $this->schemaModel->tableName);
            $this->selects[]          = $count->getSelect($asTable);
            $this->keys[$count->name] = $asTable;
        }
        return $this;
    }



    /**
     * Creates a Request Expression
     * @param Query $query
     * @return string
     */
    public function getExpression(Query $query): string {
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
     * Does a Request to the Query
     * @param Query $query
     * @return array<string,mixed>[]
     */
    public function request(Query $query): array {
        $expression    = $this->getExpression($query);
        $this->request = Framework::getDatabase()->query($expression, $query);
        return $this->request;
    }

    /**
     * Sets the Table Keys to the condition
     * @param Query $query
     * @return Selection
     */
    private function setTableKeys(Query $query): Selection {
        $columns = $query->getColumns();
        $mainKey = $this->schemaModel->tableName;

        foreach ($columns as $column) {
            $found = false;
            foreach ($this->schemaModel->fields as $field) {
                if ($column === $field->dbName) {
                    $query->updateColumn($column, "$mainKey.{$field->dbName}");
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                foreach ($this->schemaModel->relations as $relation) {
                    $relationKey = $this->keys[$relation->name];
                    foreach ($relation->fields as $field) {
                        if ($column === $field->dbName) {
                            $query->updateColumn($column, "$relationKey.{$field->dbName}");
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
                            $query->updateColumn($column, $count->getSelect($joinKey));
                            $found = true;
                            break;
                        }
                    }
                }
            }
        }
        return $this;
    }



    /**
     * Generates the Result from the Request
     * @param string[]|string|null $extras Optional.
     * @return array<string,mixed>[]
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
                    $fields[$extra] = $row[$extra];
                }
            }

            $result[] = $fields;
        }
        return $result;
    }
}
