<?php
namespace Framework\Schema;

use Framework\Schema\Field;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Selection wrapper
 */
class Selection {

    private $db;
    private $structure;

    private $index   = 66;
    private $tables  = [];
    private $keys    = [];

    private $selects = [];
    private $joins   = [];
    private $request = [];


    /**
     * Creates a new Selection instance
     * @param Database  $db
     * @param Structure $structure
     */
    public function __construct(Database $db, Structure $structure) {
        $this->db        = $db;
        $this->structure = $structure;
    }



    /**
     * Adds the Fields to the Selects
     * @param boolean $decrypted Optional.
     * @return void
     */
    public function addFields(bool $decrypted = false): void {
        $masterKey = $this->structure->masterKey;
        $mainKey   = $this->structure->table;

        if ($this->structure->hasID) {
            $this->selects[] = "$mainKey.{$this->structure->idKey} AS id";
        }
        foreach ($this->structure->fields as $field) {
            if ($decrypted && $field->type == Field::Encrypt) {
                $this->selects[] = "CAST(AES_DECRYPT($mainKey.$field->key, '$masterKey') AS CHAR(255)) {$field->key}Decrypt";
            } elseif ($field->hasName) {
                $this->selects[] = "$mainKey.$field->key AS $field->name";
            } else {
                $this->selects[] = "$mainKey.$field->key";
            }
        }
    }

    /**
     * Adds the Expressions to the Selects
     * @return void
     */
    public function addExpressions(): void {
        foreach ($this->structure->expressions as $expression => $field) {
            $this->selects[] = "($expression) AS $field->key";
        }
    }

    /**
     * Adds extra Selects
     * @param string[]|string $selects
     * @param boolean         $addMainKey Optional.
     * @return void
     */
    public function addSelects(array|string $selects, bool $addMainKey = false): void {
        $selects = Arrays::toArray($selects);
        foreach ($selects as $select) {
            if ($addMainKey) {
                $this->selects[] = $this->structure->getKey($select);
            } else {
                $this->selects[] = $select;
            }
        }
    }

    /**
     * Adds the Joins
     * @param boolean $withSelects Optional.
     * @return void
     */
    public function addJoins(bool $withSelects = true): void {
        $mainKey = $this->structure->table;

        foreach ($this->structure->joins as $join) {
            if ($join->asTable) {
                $asTable = $join->asTable;
            } elseif (Arrays::contains($this->tables, $join->table)) {
                $asTable = chr($this->index++);
            } else {
                $asTable        = $join->table;
                $this->tables[] = $join->table;
            }

            $table      = "{dbPrefix}{$join->table}";
            $onTable    = $join->onTable ?: $mainKey;
            $leftKey    = $join->leftKey;
            $rightKey   = $join->rightKey;
            $and        = $join->getAnd($asTable);
            $expression = "LEFT JOIN $table AS $asTable ON ($asTable.$leftKey = $onTable.$rightKey $and)";

            $this->joins[]          = $expression;
            $this->keys[$join->key] = $asTable;

            if ($withSelects) {
                foreach ($join->fields as $field) {
                    $this->selects[] = "$asTable.{$field->key} AS $field->prefixName";
                }
            }
        }
    }

    /**
     * Adds the Counts
     * @return void
     */
    public function addCounts(): void {
        foreach ($this->structure->counts as $count) {
            $joinKey    = chr($this->index++);
            $key        = $count->key;
            $what       = $count->isSum ? "SUM($count->mult * $count->value)" : "COUNT(*)";
            $table      = "{dbPrefix}{$count->table}";
            $groupKey   = "$table.{$count->key}";
            $asKey      = $count->asKey;
            $onTable    = $count->onTable ?: $this->structure->table;
            $leftKey    = $count->leftKey;
            $where      = $count->getWhere();
            $select     = "SELECT $groupKey, $what AS $asKey FROM $table $where GROUP BY $groupKey";
            $expression = "LEFT JOIN ($select) AS $joinKey ON ($joinKey.$leftKey = $onTable.$key)";

            $this->joins[]             = $expression;
            $this->selects[]           = "$joinKey.$asKey";
            $this->keys[$count->index] = $joinKey;
        }
    }



    /**
     * Creates a Request Expression
     * @param Query $query
     * @return string
     */
    public function getExpression(Query $query): string {
        $this->setTableKeys($query);

        $mainKey    = $this->structure->table;
        $selects    = Strings::join($this->selects, ", ");
        $joins      = Strings::join($this->joins, " ");
        $where      = $query->get();
        $expression = "SELECT $selects FROM {dbPrefix}$mainKey AS $mainKey $joins $where";

        foreach ([ "FROM", "LEFT JOIN", "WHERE", "ORDER BY", "LIMIT" ] as $key) {
            $expression = Strings::replace($expression, $key, "\n$key");
        }
        return $expression;
    }

    /**
     * Does a Request to the Query
     * @param Query $query
     * @return array
     */
    public function request(Query $query): array {
        $expression    = $this->getExpression($query);
        $this->request = $this->db->query($expression, $query);
        return $this->request;
    }

    /**
     * Sets the Table Keys to the condition
     * @param Query $query
     * @return void
     */
    private function setTableKeys(Query $query): void {
        $columns = $query->getColumns();
        $mainKey = $this->structure->table;

        foreach ($columns as $column) {
            $found = false;
            foreach ($this->structure->fields as $field) {
                if ($column === $field->key) {
                    $query->updateColumn($column, "$mainKey.{$field->key}");
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                foreach ($this->structure->joins as $join) {
                    $joinKey = $this->keys[$join->key];
                    foreach ($join->fields as $field) {
                        if ($column === $field->key) {
                            $query->updateColumn($column, "$joinKey.{$field->key}");
                            $found = true;
                            break;
                        }
                    }
                }
            }

            if (!$found) {
                foreach ($this->structure->counts as $count) {
                    if (!empty($this->keys[$count->index])) {
                        $joinKey = $this->keys[$count->index];
                        $field   = $count->asKey;
                        if ($column === $field) {
                            $query->updateColumn($column, "$joinKey.{$field}");
                            $found = true;
                            break;
                        }
                    }
                }
            }
        }
    }



    /**
     * Generates the Result from the Request
     * @param string[]|string $extras Optional.
     * @return array
     */
    public function resolve(array|string $extras = null): array {
        $result = [];

        foreach ($this->request as $row) {
            $fields = [];
            if ($this->structure->hasID) {
                if (!empty($row["id"])) {
                    $fields["id"] = $row["id"];
                } elseif (!empty($row[$this->structure->idKey])) {
                    $fields["id"] = $row[$this->structure->idKey];
                } elseif (!empty($row[$this->structure->idName])) {
                    $fields["id"] = $row[$this->structure->idName];
                }
            }

            // Parse the Fields
            foreach ($this->structure->fields as $field) {
                $values = $field->toValues($row);
                $fields = array_merge($fields, $values);
            }

            // Parse the Expressions
            foreach ($this->structure->expressions as $field) {
                $values = $field->toValues($row);
                $fields = array_merge($fields, $values);
            }

            // Parse the Joins
            foreach ($this->structure->joins as $join) {
                $values = $join->toValues($row);
                $fields = array_merge($fields, $values);
            }

            // Parse the Counts
            foreach ($this->structure->counts as $count) {
                $fields[$count->asKey] = $count->getValue($row);
            }

            // Parse the Extras
            if (!empty($extras)) {
                $extras = Arrays::toArray($extras);
                foreach ($extras as $extra) {
                    $fields[$extra] = $row[$extra];
                }
            }

            $result[] = $fields;
        }
        return $result;
    }
}
