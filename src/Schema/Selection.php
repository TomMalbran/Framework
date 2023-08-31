<?php
namespace Framework\Schema;

use Framework\Schema\Database;
use Framework\Schema\Structure;
use Framework\Schema\Field;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Selection wrapper
 */
class Selection {

    private Database  $db;
    private Structure $structure;

    private int $index   = 66;

    /** @var string[] */
    private array $tables  = [];

    /** @var string[] */
    private array $keys    = [];

    /** @var string[] */
    private array $selects = [];

    /** @var string[] */
    private array $joins   = [];

    /** @var array{}[] */
    private array $request = [];


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
     * @return Selection
     */
    public function addFields(bool $decrypted = false): Selection {
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
        return $this;
    }

    /**
     * Adds the Expressions to the Selects
     * @return Selection
     */
    public function addExpressions(): Selection {
        foreach ($this->structure->expressions as $expression => $field) {
            $this->selects[] = "($expression) AS $field->key";
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
        $selects = Arrays::toArray($selects);
        foreach ($selects as $select) {
            if ($addMainKey) {
                $this->selects[] = $this->structure->getKey($select);
            } else {
                $this->selects[] = $select;
            }
        }
        return $this;
    }

    /**
     * Adds the Joins
     * @param boolean $withSelects Optional.
     * @return Selection
     */
    public function addJoins(bool $withSelects = true): Selection {
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

            $this->joins[]          = $join->getExpression($asTable, $mainKey);
            $this->keys[$join->key] = $asTable;

            if ($withSelects) {
                foreach ($join->fields as $field) {
                    $this->selects[] = "$asTable.{$field->key} AS $field->prefixName";
                }
            }
        }
        return $this;
    }

    /**
     * Adds the Counts
     * @return Selection
     */
    public function addCounts(): Selection {
        foreach ($this->structure->counts as $count) {
            $joinKey    = chr($this->index++);
            $key        = $count->key;
            $what       = $count->isSum ? "SUM($count->mult * $count->value)" : "COUNT(*)";
            $table      = $count->table;
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
        return $this;
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
        $expression = "SELECT $selects FROM $mainKey AS $mainKey $joins $where";

        foreach ([ "FROM", "LEFT JOIN", "WHERE", "ORDER BY", "LIMIT" ] as $key) {
            $expression = Strings::replace($expression, $key, "\n$key");
        }
        return $expression;
    }

    /**
     * Does a Request to the Query
     * @param Query $query
     * @return array{}[]
     */
    public function request(Query $query): array {
        $expression    = $this->getExpression($query);
        $this->request = $this->db->query($expression, $query);
        return $this->request;
    }

    /**
     * Sets the Table Keys to the condition
     * @param Query $query
     * @return Selection
     */
    private function setTableKeys(Query $query): Selection {
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
        return $this;
    }



    /**
     * Generates the Result from the Request
     * @param string[]|string|null $extras Optional.
     * @return array{}[]
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
