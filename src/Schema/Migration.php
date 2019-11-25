<?php
namespace Framework\Schema;

use Framework\Schema\Database;
use Framework\Schema\Structure;

/**
 * The Schema Migration
 */
class Migration {
    
    private $db;
    private $schemas;
    
    
    /**
     * Creates a Migration instance
     * @param Database $db
     * @param array    $schemas
     */
    public function __construct(Database $db, array $schemas) {
        $this->db      = $db;
        $this->schemas = $schemas;
    }



    /**
     * Migrates the Tables
     * @param boolean $canDelete Optional.
     * @return void
     */
    public function migrate($canDelete = false) {
        $tableNames  = $this->db->getTables(null, false);
        $schemaNames = [];
        
        // Create or update the Tables
        foreach ($this->schemas as $schemaKey => $schemaData) {
            $structure     = new Structure($schemaKey, $schemaData);
            $schemaNames[] = $structure->table;
            
            if (!in_array($structure->table, $tableNames)) {
                $this->createTable($structure);
            } else {
                $this->updateTable($structure, $canDelete);
            }
        }

        // Delete the Tables or show which to delete
        $prebr = "<br>";
        foreach ($tableNames as $tableName) {
            if (!in_array($tableName, $schemaNames)) {
                if ($canDelete) {
                    $this->db->deleteTable($tableName);
                    print("{$prebr}Deleteing table <i>$tableName</i><br>");
                } else {
                    print("{$prebr}Delete table <i>$tableName</i> (manually)<br>");
                }
                $prebr = "";
            }
        }
    }

    /**
     * Creates a New Table
     * @param Structure $structure
     * @return void
     */
    private function createTable(Structure $structure) {
        $fields  = [];
        $primary = [];
        $keys    = [];
        
        foreach ($structure->fields as $field) {
            $fields[$field->key] = $field->getType();
            if ($field->isID || $field->isPrimary) {
                $primary[] = $field->key;
            }
            if ($field->isKey) {
                $keys[] = $field->key;
            }
        }
        
        $sql = $this->db->createTable($structure->table, $fields, $primary, $keys);
        print("<br>Creating table <b>$structure->table</b> ... <br>");
        print(str_replace("\n", "<br>", $sql) . "<br><br>");
    }

    /**
     * Updates the Table
     * @param Structure $structure
     * @param boolean   $canDelete Optional.
     * @return void
     */
    private function updateTable(Structure $structure, $canDelete = false) {
        $primaryKeys = $this->db->getPrimaryKeys($structure->table);
        $tableKeys   = $this->db->getTableKeys($structure->table);
        $tableFields = $this->db->getTableFields($structure->table);
        $update      = false;
        $adds        = [];
        $drops       = [];
        $modifies    = [];
        $renames     = [];
        $primary     = [];
        $addPrimary  = false;
        $keys        = [];
        $prev        = "";
        
        // Add new Columns
        foreach ($fields as $field) {
            $found  = false;
            $rename = false;
            foreach ($tableFields as $tableField) {
                $tableKey = $tableField["Field"];
                if (strtolower($field->key) === strtolower($tableKey) && $field->key !== $tableKey) {
                    $rename = true;
                    break;
                }
                if ($field->key === $tableKey) {
                    $found = true;
                    break;
                }
            }

            $type = $field->getType();
            if ($rename) {
                $update    = true;
                $renames[] = [
                    "key"  => $tableKey,
                    "new"  => $field->key,
                    "type" => $type,
                ];
            } elseif (!$found) {
                $update = true;
                $adds[] = [
                    "key"  => $field->key,
                    "type" => $type,
                    "afer" => $prev,
                ];
            }
            $prev = $field->key;
        }
        
        // Remove Columns
        foreach ($tableFields as $tableField) {
            $tableKey = $tableField["Field"];
            $found    = false;
            foreach ($fields as $field) {
                if (strtolower($field->key) === strtolower($tableKey)) {
                    $found = true;
                }
            }
            if (!$found) {
                $drops[] = $tableKey;
                $update  = true;
            }
        }

        // Modify Columns
        foreach ($fields as $field) {
            foreach ($tableFields as $tableField) {
                if ($field->key === $tableField["Field"]) {
                    $oldData = $tableField["Type"];
                    if ($tableField["Null"] === "NO") {
                        $oldData .= " NOT NULL";
                    }
                    if (!empty($tableField["Extra"])) {
                        $oldData .= " " . strtoupper($tableField["Extra"]);
                    }
                    $newData = $field->getType();
                    if ($newData !== $oldData) {
                        $update     = true;
                        $modifies[] = [
                            "key"  => $field->key,
                            "type" => $newData,
                        ];
                    }
                    break;
                }
            }
        }

        // Update the Table Primary Keys and Index Keys
        foreach ($fields as $field) {
            if ($field->isID || $field->isPrimary) {
                $primary[] = $field->key;
                if (!in_array($field->key, $primaryKeys)) {
                    $addPrimary = true;
                    $update     = true;
                }
            }
            if ($field->isKey) {
                $found = false;
                foreach ($tableKeys as $tableKey) {
                    if ($tableKey["Key_name"] === $field->key) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $keys[] = $field->key;
                    $update = true;
                }
            }
        }
        
        // Nothing to change
        if (!$update) {
            print("No changes for <i>$structure->table</i><br>");
            return;
        }
        
        // Update the Table
        print("<br>Updating table <b>$structure->table</b> ... <br>");
        foreach ($adds as $add) {
            $sql = $this->db->addColumn($structure->table, $add["key"], $add["type"], $add["after"]);
            print("$sql<br>");
        }
        foreach ($renames as $rename) {
            $sql = $this->db->renameColumn($structure->table, $rename["key"], $rename["new"], $rename["type"]);
            print("$sql<br>");
        }
        foreach ($modifies as $modify) {
            $sql = $this->db->updateColumn($structure->table, $modify["key"], $modify["type"]);
            print("$sql<br>");
        }
        foreach ($drops as $drop) {
            $sql = $this->db->deleteColumn($structure->table, $drop, $canDelete);
            print($sql . (!$canDelete ? " (manually)" : "") . "<br>");
        }
        foreach ($keys as $key) {
            $sql = $this->db->createIndex($structure->table, $key);
            print("$sql<br>");
        }
        if ($addPrimary) {
            $sql = $this->db->updatePrimary($structure, $primary);
            print("$sql<br>");
        }
        print("<br>");
    }
}
