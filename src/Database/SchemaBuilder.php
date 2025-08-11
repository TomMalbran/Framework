<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Database\SchemaFactory;
use Framework\Database\Builder\SchemaCode;
use Framework\Database\Builder\EntityCode;
use Framework\Database\Builder\ColumnCode;
use Framework\Database\Builder\QueryCode;
use Framework\Database\Builder\StatusCode;
use Framework\Database\Builder\MediaCode;
use Framework\File\File;

/**
 * The Schema Builder
 */
class SchemaBuilder {

    /**
     * Generates the Code for the Schemas
     * @param boolean $forFramework
     * @return integer
     */
    public static function generateCode(bool $forFramework): int {
        $schemaModels = SchemaFactory::buildData($forFramework);
        $created      = 0;

        foreach ($schemaModels as $schemaModel) {
            if (!$schemaModel->fromFramework) {
                File::createDir($schemaModel->path);
                File::emptyDir($schemaModel->path);
            }
        }

        foreach ($schemaModels as $schemaModel) {
            $schemaName = "{$schemaModel->name}Schema.php";
            $schemaCode = SchemaCode::getCode($schemaModel);
            File::create($schemaModel->path, $schemaName, $schemaCode);
            $created += 1;

            $entityName = "{$schemaModel->name}Entity.php";
            $entityCode = EntityCode::getCode($schemaModel);
            File::create($schemaModel->path, $entityName, $entityCode);
            $created += 1;

            $columnName = "{$schemaModel->name}Column.php";
            $columnCode = ColumnCode::getCode($schemaModel);
            File::create($schemaModel->path, $columnName, $columnCode);
            $created += 1;

            $queryName = "{$schemaModel->name}Query.php";
            $queryCode = QueryCode::getCode($schemaModel);
            File::create($schemaModel->path, $queryName, $queryCode);
            $created += 1;

            if ($schemaModel->hasStatus) {
                $statusName = "{$schemaModel->name}Status.php";
                $statusCode = StatusCode::getCode($schemaModel);
                File::create($schemaModel->path, $statusName, $statusCode);
                $created += 1;
            }
        }

        if (!$forFramework) {
            $writePath = Discovery::getBuildPath();
            $mediaCode = MediaCode::getCode($schemaModels);
            File::create($writePath, "MediaSchema.php", $mediaCode);
        }

        $name   = $forFramework ? "Framework" : "App";
        $models = count($schemaModels);
        print("- Generated the $name codes -> $models models ($created files)\n");
        return $created;
    }
}
