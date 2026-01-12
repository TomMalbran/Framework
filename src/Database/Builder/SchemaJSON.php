<?php
namespace Framework\Database\Builder;

use Framework\Application;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Database\SchemaFactory;
use Framework\System\Config;
use Framework\Utils\Strings;
use Framework\Utils\JSON;
use Framework\File\File;

/**
 * The Schema JSON
 */
class SchemaJSON implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return integer
     */
    public static function generateCode(): int {
        $schemaFile = Config::getDbSchemaFile();
        if ($schemaFile === "") {
            return 0;
        }

        $frameModels = SchemaFactory::buildData(forFramework: true);
        $appModels   = SchemaFactory::buildData(forFramework: false);

        $schemas = [];
        foreach ($frameModels as $schemaModel) {
            $schemas[$schemaModel->tableName] = $schemaModel->toSchemaJSON();
        }
        foreach ($appModels as $schemaModel) {
            $schemas[$schemaModel->tableName] = $schemaModel->toSchemaJSON();
        }
        ksort($schemas);

        $file = Strings::addSuffix($schemaFile, ".json");
        $path = Application::getAppPath($file);
        JSON::writeFile($path, $schemas);
        return 1;
    }

    /**
     * Destroys the Code
     * @return integer
     */
    public static function destroyCode(): int {
        $schemaFile = Config::getDbSchemaFile();
        if ($schemaFile === "") {
            return 0;
        }

        $file = Strings::addSuffix($schemaFile, ".json");
        $path = Application::getAppPath();
        File::delete($path, $file);
        return 1;
    }
}
