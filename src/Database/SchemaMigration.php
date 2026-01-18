<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Database\Database;
use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;
use Framework\Core\SettingData;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Migration
 */
class SchemaMigration {

    /**
     * Migrates the Tables
     * @param array{from:string,to:string}[]              $tableRenames
     * @param array{table:string,from:string,to:string}[] $columnRenames
     * @param bool                                        $canDelete     Optional.
     * @return bool
     */
    public static function migrateData(array $tableRenames, array $columnRenames, bool $canDelete = false): bool {
        $db            = Framework::getDatabase();
        $schemaModels  = SchemaFactory::getData();
        $startMovement = SettingData::getCore("movement");
        $startRename   = SettingData::getCore("rename");

        // Rename the Tables
        if (count($tableRenames) > 0) {
            $lastMovement = self::renameTables($db, $startMovement, $tableRenames);
            SettingData::setCore("movement", $lastMovement);
        }

        // Rename the Columns
        if (count($columnRenames) > 0) {
            $lastRename = self::renameColumns($db, $startRename, $columnRenames);
            SettingData::setCore("rename", $lastRename);
        }

        // Migrate the Tables
        self::migrateTables($db, $schemaModels, $canDelete);
        return true;
    }

    /**
     * Renames the Tables
     * @param Database                       $db
     * @param int                            $startRename
     * @param array{from:string,to:string}[] $tableRenames
     * @return int
     */
    private static function renameTables(Database $db, int $startRename, array $tableRenames): int {
        $lastRename = count($tableRenames);
        $didRename  = false;

        for ($i = $startRename; $i < $lastRename; $i += 1) {
            if (!isset($tableRenames[$i])) {
                continue;
            }

            $fromName = SchemaModel::getDbTableName($tableRenames[$i]["from"]);
            $toName   = SchemaModel::getDbTableName($tableRenames[$i]["to"]);

            if ($db->tableExists($fromName) && !$db->tableExists($toName)) {
                $db->renameTable($fromName, $toName);
                print("- Renamed table $fromName -> $toName\n");
                $didRename = true;
            }
        }

        if ($didRename) {
            print("\n");
            return $lastRename;
        }

        print("- No table renames required\n");
        return $lastRename;
    }

    /**
     * Renames the Table Columns
     * @param Database                                    $db
     * @param int                                         $startRename
     * @param array{table:string,from:string,to:string}[] $columnRenames
     * @return int
     */
    private static function renameColumns(Database $db, int $startRename, array $columnRenames): int {
        $lastRename = count($columnRenames);
        $didRename  = false;

        for ($i = $startRename; $i < $lastRename; $i += 1) {
            if (!isset($columnRenames[$i])) {
                continue;
            }

            $table    = SchemaModel::getDbTableName($columnRenames[$i]["table"]);
            $fromName = SchemaModel::getDbFieldName($columnRenames[$i]["from"]);
            $toName   = SchemaModel::getDbFieldName($columnRenames[$i]["to"]);

            if (!$db->tableExists($table)) {
                continue;
            }
            if ($db->columnExists($table, $toName)) {
                $didRename = true;
                continue;
            }

            $type = $db->getColumnType($table, $fromName);
            if ($type === "") {
                continue;
            }

            $db->renameColumn($table, $fromName, $toName, $type);
            print("- Renamed column $fromName -> $toName in $table\n");
            $didRename = true;
        }

        if ($didRename) {
            print("\n");
            return $lastRename;
        }

        print("- No column renames required\n\n");
        return $lastRename;
    }

    /**
     * Migrates the Tables
     * @param Database      $db
     * @param SchemaModel[] $schemaModels
     * @param bool          $canDelete    Optional.
     * @return bool
     */
    private static function migrateTables(Database $db, array $schemaModels, bool $canDelete = false): bool {
        $tableNames = $db->getTables();
        $modelNames = [];
        $didMigrate = false;

        // Create or update the Tables
        foreach ($schemaModels as $schemaModel) {
            $didUpdate    = false;
            $modelNames[] = $schemaModel->tableName;

            if (!Arrays::contains($tableNames, $schemaModel->tableName)) {
                $didUpdate = self::createTable($db, $schemaModel);
            } else {
                $didUpdate = self::updateTable($db, $schemaModel, $canDelete);
            }
            if ($didUpdate) {
                $didMigrate = true;
            }
        }

        // Delete the Tables or show which to delete
        $didDelete = self::deleteTables($db, $tableNames, $modelNames, $canDelete);
        return $didMigrate || $didDelete;
    }

    /**
     * Creates a New Table
     * @param Database    $db
     * @param SchemaModel $schemaModel
     * @return bool
     */
    private static function createTable(Database $db, SchemaModel $schemaModel): bool {
        $fields  = [];
        $primary = [];
        $keys    = [];

        foreach ($schemaModel->fields as $field) {
            $fields[$field->dbName] = $field->getType();
            if ($field->isPrimary) {
                $primary[] = $field->dbName;
            }
            if ($field->isKey) {
                $keys[] = $field->dbName;
            }
        }

        $sql = $db->createTable($schemaModel->tableName, $fields, $primary, $keys);
        print("\n- Created table {$schemaModel->tableName} ... \n");
        print("$sql\n\n");
        return true;
    }

    /**
     * Delete the Tables or show which to delete
     * @param Database $db
     * @param string[] $tableNames
     * @param string[] $modelNames
     * @param bool     $canDelete
     * @return bool
     */
    private static function deleteTables(Database $db, array $tableNames, array $modelNames, bool $canDelete): bool {
        $deleted  = 0;
        $preBreak = "\n";
        foreach ($tableNames as $tableName) {
            if (!Arrays::contains($modelNames, $tableName)) {
                if ($canDelete) {
                    $db->deleteTable($tableName);
                    print("{$preBreak}- Deleted table $tableName\n");
                } else {
                    print("{$preBreak}- Delete table $tableName (manually)\n");
                }
                $deleted += 1;
                $preBreak = "";
            }
        }
        return $deleted > 0;
    }

    /**
     * Updates the Table
     * @param Database    $db
     * @param SchemaModel $schemaModel
     * @param bool        $canDelete
     * @return bool
     */
    private static function updateTable(Database $db, SchemaModel $schemaModel, bool $canDelete): bool {
        $autoKey     = $db->getAutoIncrement($schemaModel->tableName);
        $primaryKeys = $db->getPrimaryKeys($schemaModel->tableName);
        $tableKeys   = $db->getTableKeys($schemaModel->tableName);
        $tableFields = $db->getTableFields($schemaModel->tableName);
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
        $canDrop     = count($primaryKeys) > 0;
        $keys        = [];

        // Add new Columns
        $prev = "";
        foreach ($schemaModel->fields as $field) {
            $found       = false;
            $isRename    = false;
            $renameTable = "";

            foreach ($tableNames as $tableKey) {
                if (Strings::isEqual($field->dbName, $tableKey) && $field->dbName !== $tableKey) {
                    $isRename    = true;
                    $renameTable = $tableKey;
                    break;
                }
                if ($field->dbName === $tableKey) {
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
                    "new"  => $field->dbName,
                    "type" => $type,
                ];
            } elseif (!$found) {
                $update = true;
                if ($field->isID && $autoKey !== "") {
                    $found         = true;
                    $primaryKeys[] = $field->dbName;
                    $renamed[]     = $autoKey;
                    $renames[]     = [
                        "key"  => $autoKey,
                        "new"  => $field->dbName,
                        "type" => $type,
                    ];
                }
            }

            if (!$found) {
                if ($field->isID) {
                    $dropPrimary   = true;
                    $primaryKeys[] = $field->dbName;
                    $type         .= " PRIMARY KEY";
                }
                $adds[] = [
                    "key"   => $field->dbName,
                    "type"  => $type,
                    "after" => $prev,
                ];
            }
            $prev = $field->dbName;
        }

        // Modify Columns
        $newPrev = "";
        foreach ($schemaModel->fields as $field) {
            $oldPrev = "";
            foreach ($tableFields as $tableKey => $oldData) {
                if ($field->dbName === $tableKey) {
                    $hasLength = Strings::contains($oldData, "(");
                    $newData   = $field->getType($hasLength);

                    if ($newData !== $oldData || $newPrev !== $oldPrev) {
                        $update     = true;
                        $modifies[] = [
                            "key"    => $field->dbName,
                            "type"   => $newData,
                            "after"  => $newPrev,
                            "toInts" => Strings::contains($newData, "int") && Strings::contains($oldData, "varchar"),
                        ];
                    }
                    break;
                }
                $oldPrev = $tableKey;
            }
            $newPrev = $field->dbName;
        }

        // Remove Columns
        foreach ($tableFields as $tableKey => $tableField) {
            $found = false;
            foreach ($schemaModel->fields as $field) {
                if (Strings::isEqual($field->dbName, $tableKey) || Arrays::contains($renamed, $tableKey)) {
                    $found = true;
                }
            }
            if (!$found) {
                $update  = true;
                $drops[] = $tableKey;
            }
        }

        // Update the Table Primary Keys and Index Keys
        foreach ($schemaModel->fields as $field) {
            if ($field->isPrimary) {
                $primary[] = $field->dbName;
                if (!Arrays::contains($primaryKeys, $field->dbName)) {
                    $update     = true;
                    $addPrimary = true;
                }
            }
            if ($field->isKey) {
                $found = false;
                foreach ($tableKeys as $tableKey) {
                    if (isset($tableKey["Key_name"]) && $tableKey["Key_name"] === $field->dbName) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $update = true;
                    $keys[] = $field->dbName;
                }
            }
        }

        // Nothing to change
        if (!$update) {
            print("- No changes for {$schemaModel->tableName}\n");
            return false;
        }

        // Update the Table
        print("\n- Updated table {$schemaModel->tableName} ... \n");
        if ($dropPrimary && $canDrop) {
            $sql = $db->dropPrimary($schemaModel->tableName);
            print("$sql\n");
        }
        foreach ($renames as $rename) {
            $sql = $db->renameColumn($schemaModel->tableName, $rename["key"], $rename["new"], $rename["type"]);
            print("$sql\n");
        }
        foreach ($adds as $add) {
            $sql = $db->addColumn($schemaModel->tableName, $add["key"], $add["type"], $add["after"]);
            print("$sql\n");
        }
        foreach ($modifies as $modify) {
            if ($modify["toInts"]) {
                $db->execute("UPDATE `{$schemaModel->tableName}` SET `{$modify["key"]}` = '0' WHERE `{$modify["key"]}` = ''");
            }
            $sql = $db->updateColumn($schemaModel->tableName, $modify["key"], $modify["type"], $modify["after"]);
            print("$sql\n");
        }
        foreach ($drops as $drop) {
            $sql = $db->deleteColumn($schemaModel->tableName, $drop, $canDelete);
            print("$sql" . (!$canDelete ? " (manually)" : "") . "\n");
        }
        foreach ($keys as $key) {
            $sql = $db->createIndex($schemaModel->tableName, $key);
            print("$sql\n");
        }
        if ($addPrimary) {
            $sql = $db->updatePrimary($schemaModel->tableName, $primary);
            print("$sql\n");
        }
        print("\n");
        return true;
    }
}
