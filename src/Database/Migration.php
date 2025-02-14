<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\Core\Settings;
use Framework\File\File;
use Framework\Database\Factory;
use Framework\Database\Database;
use Framework\Database\Structure;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Database Migration
 */
class Migration {

    /**
     * Migrates the Tables
     * @param boolean $canDelete Optional.
     * @return boolean
     */
    public static function migrateData(bool $canDelete = false): bool {
        $db         = Framework::getDatabase();
        $migrations = Discovery::loadData(DataFile::Migrations);
        $schemas    = Factory::getData();

        $moved      = self::moveTables($db, $migrations["movements"]);
        $renamed    = self::renameColumns($db, $migrations["renames"]);

        $migrated   = self::migrateTables($db, $schemas, $canDelete);
        $extras     = self::extraMigrations($db);

        return $moved || $renamed || $migrated || $extras;
    }

    /**
     * Moves the Tables
     * @param Database  $db
     * @param array{}[] $movements
     * @return boolean
     */
    private static function moveTables(Database $db, array $movements): bool {
        $startMovement = Settings::getCore("movement");
        $lastMovement  = count($movements);
        $didMove       = false;

        for ($i = $startMovement; $i < $lastMovement; $i++) {
            $fromName = Factory::getTableName($movements[$i]["from"]);
            $toName   = Factory::getTableName($movements[$i]["to"]);

            if ($db->tableExists($fromName)) {
                $db->renameTable($fromName, $toName);
                print("Renamed table <i>$fromName</i> to <b>$toName</b><br>");
                $didMove = true;
            }
        }

        if ($didMove) {
            Settings::setCore("movement", $lastMovement);
            print("<br>");
        } else {
            print("No <i>movements</i> required<br>");
        }
        return $didMove;
    }

    /**
     * Renames the Table Columns
     * @param Database  $db
     * @param array{}[] $renames
     * @return boolean
     */
    private static function renameColumns(Database $db, array $renames): bool {
        $startRename = Settings::getCore("rename");
        $lastRename  = count($renames);
        $didRename   = false;

        for ($i = $startRename; $i < $lastRename; $i++) {
            $table    = Factory::getTableName($renames[$i]["schema"]);
            $fromName = $renames[$i]["from"];
            $toName   = $renames[$i]["to"];

            if (!$db->tableExists($table)) {
                continue;
            }

            $type = $db->getColumnType($table, $fromName);
            if (empty($type)) {
                continue;
            }

            $db->renameColumn($table, $fromName, $toName, $type);
            print("Renamed column <i>$fromName</i> to <b>$toName</b> in table <u>$table</u><br>");
            $didRename = true;
        }

        if ($didRename) {
            Settings::setCore("rename", $lastRename);
            print("<br>");
        } else {
            print("No <i>column renames</i> required<br><br>");
        }
        return $didRename;
    }

    /**
     * Migrates the Tables
     * @param Database  $db
     * @param array{}[] $schemas
     * @param boolean   $canDelete Optional.
     * @return boolean
     */
    private static function migrateTables(Database $db, array $schemas, bool $canDelete = false): bool {
        $tableNames  = $db->getTables();
        $schemaNames = [];
        $didMigrate  = false;

        // Create or update the Tables
        foreach ($schemas as $schemaKey => $schemaData) {
            $didUpdate     = false;
            $structure     = new Structure($schemaKey, $schemaData);
            $schemaNames[] = $structure->table;

            if (!Arrays::contains($tableNames, $structure->table)) {
                $didUpdate = self::createTable($db, $structure);
            } else {
                $didUpdate = self::updateTable($db, $structure, $canDelete);
            }
            if ($didUpdate) {
                $didMigrate = true;
            }
        }

        // Delete the Tables or show which to delete
        $didDelete = self::deleteTables($db, $tableNames, $schemaNames, $canDelete);
        return $didMigrate || $didDelete;
    }

    /**
     * Creates a New Table
     * @param Database  $db
     * @param Structure $structure
     * @return boolean
     */
    private static function createTable(Database $db, Structure $structure): bool {
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
        return true;
    }

    /**
     * Delete the Tables or show which to delete
     * @param Database $db
     * @param string[] $tableNames
     * @param string[] $schemaNames
     * @param boolean  $canDelete
     * @return boolean
     */
    private static function deleteTables(Database $db, array $tableNames, array $schemaNames, bool $canDelete): bool {
        $deleted  = 0;
        $preBreak = "<br>";
        foreach ($tableNames as $tableName) {
            if (!Arrays::contains($schemaNames, $tableName)) {
                if ($canDelete) {
                    $db->deleteTable($tableName);
                    print("{$preBreak}Deleted table <i>$tableName</i><br>");
                } else {
                    print("{$preBreak}Delete table <i>$tableName</i> (manually)<br>");
                }
                $deleted += 1;
                $preBreak = "";
            }
        }
        return $deleted > 0;
    }

    /**
     * Updates the Table
     * @param Database  $db
     * @param Structure $structure
     * @param boolean   $canDelete
     * @return boolean
     */
    private static function updateTable(Database $db, Structure $structure, bool $canDelete): bool {
        $autoKey     = $db->getAutoIncrement($structure->table);
        $primaryKeys = $db->getPrimaryKeys($structure->table);
        $tableKeys   = $db->getTableKeys($structure->table);
        $tableFields = $db->getTableFields($structure->table);
        $tableFields = Arrays::createMap($tableFields, "Field");
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
            foreach (array_keys($tableFields) as $tableKey) {
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
                $found     = true;
                $renames[] = [
                    "key"  => $tableKey,
                    "new"  => $field->key,
                    "type" => $type,
                ];
            } elseif (!$found) {
                $update = true;
                if ($field->isID && !empty($autoKey)) {
                    $found         = true;
                    $primaryKeys[] = $field->key;
                    $renamed[]     = $autoKey;
                    $renames[]     = [
                        "key"  => $autoKey,
                        "new"  => $field->key,
                        "type" => $type,
                    ];
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

        // Modify Columns
        $newPrev = "";
        foreach ($structure->fields as $field) {
            $oldPrev = "";
            foreach ($tableFields as $tableKey => $tableField) {
                if ($field->key === $tableKey) {
                    $oldData   = $db->parseColumnType($tableField);
                    $hasLength = Strings::contains($oldData, "(");
                    $newData   = $field->getType($hasLength);

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

        // Remove Columns
        foreach ($tableFields as $tableKey => $tableField) {
            $found = false;
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
            return false;
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
        return true;
    }



    /**
     * Runs extra Migrations
     * @param Database $db
     * @return boolean
     */
    private static function extraMigrations(Database $db): bool {
        $migration = Settings::getCore("migration");
        $path      = Discovery::getMigrationsPath();

        Settings::setCore("migration", $migration);
        if (!File::exists($path)) {
            print("<br>No <i>migrations</i> required<br>");
            return false;
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
            return false;
        }

        print("<br>Running <b>migrations $first to $last</b><br>");
        foreach ($names as $name) {
            if ($name >= $first) {
                include_once("$path/$name.php");
                call_user_func("migration$name", $db);
            }
        }

        Settings::setCore("migration", $last);
        return true;
    }
}
