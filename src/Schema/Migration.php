<?php
namespace Framework\Schema;

use Framework\Framework;
use Framework\Config\Settings;
use Framework\File\File;
use Framework\Schema\Database;
use Framework\Schema\Structure;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Migration
 */
class Migration {

    /**
     * Migrates the Tables
     * @param Database  $db
     * @param array{}[] $schemas
     * @param boolean   $canDelete Optional.
     * @return void
     */
    public static function migrate(Database $db, array $schemas, bool $canDelete = false): void {
        $migrations = Framework::loadData("migrations/migrations");

        self::moveTables($db, $migrations["movements"]);
        self::migrateTables($db, $schemas, $migrations["updates"], $canDelete);
        self::extraMigrations($db);
    }

    /**
     * Moves the Tables
     * @param Database  $db
     * @param array{}[] $movements
     * @return void
     */
    private static function moveTables(Database $db, array $movements): void {
        $movement = Settings::getCore($db, "movement");
        $last     = count($movements);
        $didMove  = false;

        for ($i = $movement; $i < $last; $i++) {
            $oldName = $movements[$i]["old"];
            $newName = $movements[$i]["new"];

            if ($db->tableExists($oldName)) {
                $db->renameTable($oldName, $newName);
                print("Renamed table <i>$oldName</i> to <b>$newName</b><br>");
                $didMove = true;
            }
        }

        if ($didMove) {
            print("<br>");
        } else {
            print("No <i>movements</i> required<br><br>");
        }
        Settings::setCore($db, "movement", $last);
    }

    /**
     * Migrates the Tables
     * @param Database  $db
     * @param array{}[] $schemas
     * @param array{}[] $updates
     * @param boolean   $canDelete Optional.
     * @return void
     */
    private static function migrateTables(Database $db, array $schemas, array $updates, bool $canDelete = false): void {
        $tableNames  = $db->getTables(null, false);
        $schemaNames = [];

        // Create or update the Tables
        foreach ($schemas as $schemaKey => $schemaData) {
            $structure     = new Structure($schemaKey, $schemaData);
            $schemaNames[] = $structure->table;

            if (!Arrays::contains($tableNames, $structure->table)) {
                self::createTable($db, $structure);
            } else {
                $schemaUpdates = !empty($updates[$structure->table]) ? $updates[$structure->table] : [];
                self::updateTable($db, $structure, $schemaUpdates, $canDelete);
            }
        }

        // Delete the Tables or show which to delete
        self::deleteTables($db, $tableNames, $schemaNames, $canDelete);
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
        print("<br>Created table <b>$structure->table</b> ... <br>");
        print(Strings::toHtml($sql) . "<br><br>");
    }

    /**
     * Delete the Tables or show which to delete
     * @param Database $db
     * @param string[] $tableNames
     * @param string[] $schemaNames
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
     * @param array{}[] $updates
     * @param boolean   $canDelete
     * @return void
     */
    private static function updateTable(Database $db, Structure $structure, array $updates, bool $canDelete): void {
        $autoKey     = $db->getAutoIncrement($structure->table);
        $primaryKeys = $db->getPrimaryKeys($structure->table);
        $tableKeys   = $db->getTableKeys($structure->table);
        $tableFields = $db->getTableFields($structure->table);
        $update      = false;
        $adds        = [];
        $drops       = [];
        $modifies    = [];
        $renames     = [];
        $renamed     = [];
        $primary     = [];
        $addPrimary  = false;
        $dropPrimary = false;
        $canDrop     = !empty($primaryKeys);
        $keys        = [];

        // Add new Columns
        $prev = "";
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
                if ($field->isID && !empty($autoKey)) {
                    $primaryKeys[] = $field->key;
                    $renames[]     = [
                        "key"  => $autoKey,
                        "new"  => $field->key,
                        "type" => $type,
                    ];
                } else {
                    foreach ($updates as $update) {
                        if ($update["new"] == $field->key) {
                            $primaryKeys[] = $field->key;
                            $renamed[]     = $update["old"];
                            $renames[]     = [
                                "key"  => $update["old"],
                                "new"  => $field->key,
                                "type" => $type,
                            ];
                            $prev  = $update["new"];
                            $found = true;
                            break;
                        }
                    }
                }
            }
            if (!$found) {
                if ($field->isID) {
                    $dropPrimary   = true;
                    $primaryKeys[] = $field->key;
                    $type         .= " PRIMARY KEY";
                }
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
                if (Strings::isEqual($field->key, $tableKey) || Arrays::contains($renamed, $tableKey)) {
                    $found = true;
                }
            }
            if (!$found) {
                $update  = true;
                $drops[] = $tableKey;
            }
        }

        // Modify Columns
        $newPrev = "";
        foreach ($structure->fields as $field) {
            $oldPrev = "";
            foreach ($tableFields as $tableField) {
                if ($field->key === $tableField["Field"]) {
                    $oldData = $tableField["Type"];
                    if ($tableField["Null"] === "NO") {
                        $oldData .= " NOT NULL";
                    } else {
                        $oldData .= " NULL";
                    }
                    if ($tableField["Default"] !== NULL) {
                        $oldData .= " DEFAULT '{$tableField["Default"]}'";
                    }
                    if (!empty($tableField["Extra"])) {
                        $oldData .= " " . Strings::toUpperCase($tableField["Extra"]);
                    }
                    $newData = $field->getType();
                    if ($newData !== $oldData || $newPrev !== $oldPrev) {
                        $update     = true;
                        $modifies[] = [
                            "key"   => $field->key,
                            "type"  => $newData,
                            "after" => $newPrev,
                        ];
                    }
                    break;
                }
                $oldPrev = $tableField["Field"];
            }
            $newPrev = $field->key;
        }

        // Update the Table Primary Keys and Index Keys
        foreach ($structure->fields as $field) {
            if ($field->isPrimary) {
                $primary[] = $field->key;
                if (!Arrays::contains($primaryKeys, $field->key)) {
                    $update     = true;
                    $addPrimary = true;
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
                    $update = true;
                    $keys[] = $field->key;
                }
            }
        }

        // Nothing to change
        if (!$update) {
            print("No changes for <i>$structure->table</i><br>");
            return;
        }

        // Update the Table
        print("<br>Updated table <b>$structure->table</b> ... <br>");
        if ($dropPrimary && $canDrop) {
            $sql = $db->dropPrimary($structure->table);
            print("$sql<br>");
        }
        foreach ($renames as $rename) {
            $sql = $db->renameColumn($structure->table, $rename["key"], $rename["new"], $rename["type"]);
            print("$sql<br>");
        }
        foreach ($adds as $add) {
            $sql = $db->addColumn($structure->table, $add["key"], $add["type"], $add["after"]);
            print("$sql<br>");
        }
        foreach ($modifies as $modify) {
            $sql = $db->updateColumn($structure->table, $modify["key"], $modify["type"], $modify["after"]);
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
    private static function extraMigrations(Database $db): void {
        $migration = Settings::getCore($db, "migration");
        $path      = Framework::getPath(Framework::MigrationsDir);

        if (!File::exists($path)) {
            print("<br>No <i>migrations</i> required<br>");
            return;
        }

        $files = File::getFilesInDir($path);
        $names = [];
        foreach ($files as $file) {
            if (File::hasExtension($file, "php")) {
                $names[] = (int)File::getName($file);
            }
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

        Settings::setCore($db, "migration", $last);
    }
}
