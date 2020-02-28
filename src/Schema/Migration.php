<?php
namespace Framework\Schema;

use Framework\Framework;
use Framework\Schema\Database;
use Framework\Schema\Structure;
use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

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
    public static function migrate(Database $db, array $schemas, bool $canDelete = false): void {
        $tableNames  = $db->getTables(null, false);
        $schemaNames = [];
        
        // Create or update the Tables
        foreach ($schemas as $schemaKey => $schemaData) {
            $structure     = new Structure($schemaKey, $schemaData);
            $schemaNames[] = $structure->table;
            
            if (!Arrays::contains($tableNames, $structure->table)) {
                self::createTable($db, $structure);
            } else {
                self::updateTable($db, $structure, $canDelete);
            }
        }

        // Delete the Tables or show which to delete
        self::deleteTables($db, $tableNames, $schemaNames, $canDelete);

        // Run extra migrations created in files
        self::extraMigrations($db);
    }

    /**
     * Creates a New Table
     * @param Database  $db
     * @param Structure $structure
     * @return void
     */
    private static function createTable(Database $db, Structure $structure): void {
        $fields  = [];
        $primary = [];
        $keys    = [];
        
        foreach ($structure->fields as $field) {
            $fields[$field->key] = $field->getType();
            if ($field->isPrimary) {
                $primary[] = $field->key;
            }
            if ($field->isKey) {
                $keys[] = $field->key;
            }
        }
        
        $sql = $db->createTable($structure->table, $fields, $primary, $keys);
        print("<br>Creating table <b>$structure->table</b> ... <br>");
        print(Strings::toHtml($sql) . "<br><br>");
    }

    /**
     * Delete the Tables or show which to delete
     * @param Database $db
     * @param array    $tableNames
     * @param array    $schemaNames
     * @param boolean  $canDelete
     * @return void
     */
    private static function deleteTables(Database $db, array $tableNames, array $schemaNames, bool $canDelete): void {
        $prebr = "<br>";
        foreach ($tableNames as $tableName) {
            if (!Arrays::contains($schemaNames, $tableName)) {
                if ($canDelete) {
                    $db->deleteTable($tableName);
                    print("{$prebr}Deleted table <i>$tableName</i><br>");
                } else {
                    print("{$prebr}Delete table <i>$tableName</i> (manually)<br>");
                }
                $prebr = "";
            }
        }
    }

    /**
     * Updates the Table
     * @param Database  $db
     * @param Structure $structure
     * @param boolean   $canDelete
     * @return void
     */
    private static function updateTable(Database $db, Structure $structure, bool $canDelete): void {
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
                if (Strings::isEqual($field->key, $tableKey) && $field->key !== $tableKey) {
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
                    "key"   => $field->key,
                    "type"  => $type,
                    "after" => $prev,
                ];
            }
            $prev = $field->key;
        }
        
        // Remove Columns
        foreach ($tableFields as $tableField) {
            $tableKey = $tableField["Field"];
            $found    = false;
            foreach ($structure->fields as $field) {
                if (Strings::isEqual($field->key, $tableKey)) {
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
                    if ($tableField["Default"] !== NULL) {
                        $oldData .= " DEFAULT '{$tableField["Default"]}'";
                    }
                    if (!empty($tableField["Extra"])) {
                        $oldData .= " " . Strings::toUpperCase($tableField["Extra"]);
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
            if ($field->isPrimary) {
                $primary[] = $field->key;
                if (!Arrays::contains($primaryKeys, $field->key)) {
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
            $sql = $db->updatePrimary($structure->table, $primary);
            print("$sql<br>");
        }
        print("<br>");
    }



    /**
     * Runs extra Migrations
     * @param Database $db
     * @return void
     */
    private static function extraMigrations(Database $db) {
        $query     = Query::create("section", "=", "general")->add("variable", "=", "migration");
        $migration = $db->getValue("settings", "value", $query);

        $path  = Framework::getPath(Framework::MigrationsDir);
        if (!File::exists($path)) {
            print("<br>No <i>migrations</i> required<br>");
            return;
        }

        $files = File::getFilesInDir($path);
        $names = [];
        foreach ($files as $file) {
            $names[] = (int)File::getName($file);
        }

        sort($names);
        $first = !empty($migration) ? $migration + 1 : 1;
        $last  = end($names);
        if (empty($names) || $first > $last) {
            print("<br>No <i>migrations</i> required<br>");
            return;
        }

        print("<br>Running <b>migrations $first to $last</b><br>");
        foreach ($names as $name) {
            if ($name >= $first) {
                include_once("$path/$name.php");
                call_user_func("migration$name", $db);
            }
        }

        $db->insert("settings", [
            "section"  => "general",
            "variable" => "migration",
            "value"    => $last,
        ], "REPLACE");
    }
}
