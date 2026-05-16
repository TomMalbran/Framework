<?php
namespace Framework\Database;

use Framework\Database\SchemaFactory;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Database\Builder\SchemaCode;
use Framework\Database\Builder\EntityCode;
use Framework\Database\Builder\ColumnCode;
use Framework\Database\Builder\QueryCode;
use Framework\Database\Builder\StatusCode;
use Framework\Database\Builder\RequestCode;
use Framework\File\File;

/**
 * The Schema Builder
 */
class SchemaBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        print("\n");
        $created  = self::generateSchemaCode(forFramework: true);
        $created += self::generateSchemaCode(forFramework: false);
        print("\n");
        return $created;
    }

    /**
     * Generates the Code for the Schemas
     * @param bool $forFramework
     * @return int
     */
    private static function generateSchemaCode(bool $forFramework): int {
        $schemaModels = SchemaFactory::buildData($forFramework);
        $created      = 0;

        foreach ($schemaModels as $schemaModel) {
            if (!$schemaModel->fromFramework) {
                File::createDir($schemaModel->path);
                File::emptyDir($schemaModel->path);
            }
        }

        foreach ($schemaModels as $schemaModel) {
            // Build the main Schema file
            if (!$schemaModel->isEmpty) {
                $schemaName = "{$schemaModel->name}Schema.php";
                $schemaCode = SchemaCode::getCode($schemaModel);
                File::create($schemaModel->path, $schemaName, $schemaCode);
                $created += 1;
            }

            // Build the Entity file
            if (!$schemaModel->isEmpty) {
                $entityName = "{$schemaModel->entityClass}.php";
                $entityCode = EntityCode::getCode($schemaModel);
                File::create($schemaModel->path, $entityName, $entityCode);
                $created += 1;
            }

            // Build the Column file
            if (!$schemaModel->isEmpty) {
                $columnName = "{$schemaModel->columnClass}.php";
                $columnCode = ColumnCode::getCode($schemaModel);
                File::create($schemaModel->path, $columnName, $columnCode);
                $created += 1;
            }

            // Build the Query file
            if (!$schemaModel->isEmpty) {
                $queryName = "{$schemaModel->queryClass}.php";
                $queryCode = QueryCode::getCode($schemaModel);
                File::create($schemaModel->path, $queryName, $queryCode);
                $created += 1;
            }

            // Build the Status files
            if ($schemaModel->hasStatus) {
                $statusName = "{$schemaModel->statusClass}.php";
                $statusCode = StatusCode::getCode($schemaModel);
                File::create($schemaModel->path, $statusName, $statusCode);
                $created += 1;

                $statusQueryName = "{$schemaModel->statusClass}Where.php";
                $statusQueryCode = StatusCode::getWhereCode($schemaModel);
                File::create($schemaModel->path, $statusQueryName, $statusQueryCode);
                $created += 1;
            }

            // Build the Request file
            if (count($schemaModel->requestedFields) > 0) {
                $requestName = "{$schemaModel->requestClass}.php";
                $requestCode = RequestCode::getCode($schemaModel);
                File::create($schemaModel->path, $requestName, $requestCode);
                $created += 1;
            }
        }

        $name   = $forFramework ? "Framework" : "App";
        $models = count($schemaModels);
        print("- $name Schema codes -> $models models ($created files)\n");
        return $created;
    }



    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        $deleted  = self::destroySchemaCode(forFramework: true);
        $deleted += self::destroySchemaCode(forFramework: false);
        return $deleted;
    }

    /**
     * Destroys the Code for the Schemas
     * @param bool $forFramework
     * @return int
     */
    private static function destroySchemaCode(bool $forFramework): int {
        $schemaModels = SchemaFactory::buildData($forFramework);
        $deletedFiles = 0;

        foreach ($schemaModels as $schemaModel) {
            if (!$schemaModel->fromFramework) {
                File::deleteDir($schemaModel->path, $deletedFiles);
            }
        }
        return $deletedFiles;
    }
}
