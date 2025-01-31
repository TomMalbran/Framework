<?php
namespace Framework\Database;

use Framework\Database\Database;
use Framework\Database\Structure;
use Framework\Database\Field;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Selection
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
        $this->tables    = [ $structure->table ];
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
     * @param string[] $extraJoins  Optional.
     * @param boolean  $withSelects Optional.
     * @return Selection
     */
    public function addJoins(array $extraJoins = [], bool $withSelects = true): Selection {
        $mainKey = $this->structure->table;

        foreach ($this->structure->joins as $join) {
            if (!empty($join->asTable)) {
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
        $mainKey = $this->structure->table;

        foreach ($this->structure->counts as $count) {
            $asTable                 = chr($this->index++);
            $this->joins[]           = $count->getExpression($asTable, $mainKey);
            $this->selects[]         = $count->getSelect($asTable);
            $this->keys[$count->key] = $asTable;
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
        $expression = "SELECT $selects FROM `$mainKey` $joins $where";

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
                    if (!empty($this->keys[$count->key])) {
                        $joinKey = $this->keys[$count->key];
                        if ($column === $count->key) {
                            $query->updateColumn($column, "$joinKey.{$count->key}");
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
    public function resolve(array|string|null $extras = null): array {
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
                $fields[$count->key] = $count->getValue($row);
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
