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
        $db             = Framework::getDatabase();
        $migrations     = Discovery::loadData(DataFile::Migrations);
        $schemas        = Factory::getData();
        $startMovement  = Settings::getCore("movement");
        $startRename    = Settings::getCore("rename");
        $startMigration = Settings::getCore("migration");

        $lastMovement   = self::moveTables($db, $startMovement, $migrations["movements"]);
        $lastRename     = self::renameColumns($db, $startRename, $migrations["renames"]);

        $migrated       = self::migrateTables($db, $schemas, $canDelete);
        $lastMigration  = self::extraMigrations($db, $startMigration);

        if ($lastMovement > 0) {
            Settings::setCore("movement", $lastMovement);
        }
        if ($lastRename > 0) {
            Settings::setCore("rename", $lastRename);
        }
        if ($lastMigration > 0) {
            Settings::setCore("migration", $lastMigration);
        }

        return $lastMovement > 0 || $lastRename > 0 || $migrated || $lastMigration > 0;
    }

    /**
     * Moves the Tables
     * @param Database                       $db
     * @param integer                        $startMovement
     * @param array{from:string,to:string}[] $movements
     * @return integer
     */
    private static function moveTables(Database $db, int $startMovement, array $movements): int {
        $lastMovement = count($movements);
        $didMove      = false;

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
            print("<br>");
        } else {
            print("No <i>movements</i> required<br>");
        }
        return $didMove ? $lastMovement : 0;
    }

    /**
     * Renames the Table Columns
     * @param Database                                     $db
     * @param integer                                      $startRename
     * @param array{schema:string,from:string,to:string}[] $renames
     * @return integer
     */
    private static function renameColumns(Database $db, int $startRename, array $renames): int {
        $lastRename = count($renames);
        $didRename  = false;

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
            print("<br>");
        } else {
            print("No <i>column renames</i> required<br><br>");
        }
        return $didRename ? $lastRename : 0;
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
        $tableNames  = Arrays::toStrings(array_keys($tableFields));
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
            $found       = false;
            $isRename    = false;
            $renameTable = "";

            foreach ($tableNames as $tableKey) {
                if (Strings::isEqual($field->key, $tableKey) && $field->key !== $tableKey) {
                    $isRename    = true;
                    $renameTable = $tableKey;
                    break;
                }
                if ($field->key === $tableKey) {
                    $found = true;
                    break;
                }
            }

            $type = $field->getType();
            if ($isRename) {
                $update    = true;
                $found     = true;
                $renames[] = [
                    "key"  => $renameTable,
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
                            "key"    => $field->key,
                            "type"   => $newData,
                            "after"  => $newPrev,
                            "toInts" => Strings::contains($newData, "int") && Strings::contains($oldData, "varchar"),
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
                $drops[] = (string)$tableKey;
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
            if ($modify["toInts"]) {
                $db->query("UPDATE `$structure->table` SET `{$modify["key"]}` = '0' WHERE `{$modify["key"]}` = ''");
            }
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
     * @param integer  $startMigration
     * @return integer
     */
    private static function extraMigrations(Database $db, int $startMigration): int {
        $path = Discovery::getMigrationsPath();

        if (!File::exists($path)) {
            print("<br>No <i>migrations</i> required<br>");
            return 0;
        }

        /** @var integer[] */
        $names = [];
        $files = File::getFilesInDir($path);

        foreach ($files as $file) {
            if (File::hasExtension($file, "php")) {
                $names[] = (int)File::getName($file);
            }
        }
        sort($names);

        $firstMigration = $startMigration + 1;
        $lastMigration  = (int)end($names);
        if (count($names) === 0 || $firstMigration > $lastMigration) {
            print("<br>No <i>migrations</i> required<br>");
            return 0;
        }

        print("<br>Running <b>migrations $firstMigration to $lastMigration</b><br>");
        foreach ($names as $name) {
            if ($name >= $firstMigration) {
                include_once "$path/$name.php";
                $functionName = "migration$name";
                if (function_exists($functionName)) {
                    $functionName($db);
                }
            }
        }

        return $lastMigration;
    }
}
