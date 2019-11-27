<?php
namespace Framework\Schema;

use Framework\Schema\Database;
use Framework\Schema\Structure;

/**
 * The Schema Migration
 */
class Migration {
    
    /**
     * Migrates the Tables
     * @param Database $db
     * @param array    $schemas
     * @param boolean  $canDelete Optional.
     * @return void
     */
    public static function migrate(Database $db, array $schemas, $canDelete = false) {
        $tableNames  = $db->getTables(null, false);
        $schemaNames = [];
        
        // Create or update the Tables
        foreach ($schemas as $schemaKey => $schemaData) {
            $structure     = new Structure($schemaKey, $schemaData);
            $schemaNames[] = $structure->table;
            
            if (!in_array($structure->table, $tableNames)) {
                self::createTable($db, $structure);
            } else {
                self::updateTable($db, $structure, $canDelete);
            }
        }

        // Delete the Tables or show which to delete
        $prebr = "<br>";
        foreach ($tableNames as $tableName) {
            if (!in_array($tableName, $schemaNames)) {
                if ($canDelete) {
                    $db->deleteTable($tableName);
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
     * @param Database  $db
     * @param Structure $structure
     * @return void
     */
    private static function createTable(Database $db, Structure $structure) {
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
        
        $sql = $db->createTable($structure->table, $fields, $primary, $keys);
        print("<br>Creating table <b>$structure->table</b> ... <br>");
        print(str_replace("\n", "<br>", $sql) . "<br><br>");
    }

    /**
     * Updates the Table
     * @param Database  $db
     * @param Structure $structure
     * @param boolean   $canDelete Optional.
     * @return void
     */
    private static function updateTable(Database $db, Structure $structure, $canDelete = false) {
        $primaryKeys = $db->getPrimaryKeys($structure->table);
        $tableKeys   = $db->getTableKeys($structure->table);
        $tableFields = $db->getTableFields($structure->table);
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
        foreach ($structure->fields as $field) {
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
            foreach ($structure->fields as $field) {
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
        foreach ($structure->fields as $field) {
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
        foreach ($structure->fields as $field) {
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
            $sql = $db->addColumn($structure->table, $add["key"], $add["type"], $add["after"]);
            print("$sql<br>");
        }
        foreach ($renames as $rename) {
            $sql = $db->renameColumn($structure->table, $rename["key"], $rename["new"], $rename["type"]);
            print("$sql<br>");
        }
        foreach ($modifies as $modify) {
            $sql = $db->updateColumn($structure->table, $modify["key"], $modify["type"]);
            print("$sql<br>");
        }
        foreach ($drops as $drop) {
            $sql = $db->deleteColumn($structure->table, $drop, $canDelete);
            print($sql . (!$canDelete ? " (manually)" : "") . "<br>");
        }
        foreach ($keys as $key) {
            $sql = $db->createIndex($structure->table, $key);
            print("$sql<br>");
        }
        if ($addPrimary) {
            $sql = $db->updatePrimary($structure, $primary);
            print("$sql<br>");
        }
        print("<br>");
    }
}
