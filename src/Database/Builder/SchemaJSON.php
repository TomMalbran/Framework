<?php
namespace Framework\Database\Builder;

use Framework\Application;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;
use Framework\System\Config;
use Framework\File\Storage;
use Framework\Utils\Strings;
use Framework\Utils\JSON;

/**
 * The Schema JSON
 */
class SchemaJSON implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $schemaFile = Config::getDbSchemaFile();
        if ($schemaFile === "") {
            return 0;
        }

        $schemas = self::buildSchema(SchemaFactory::getData());
        $file    = Strings::addSuffix($schemaFile, ".json");
        $path = Application::getBasePath($file);
        JSON::writeFile($path, $schemas);
        return 1;
    }

    /**
     * Builds the Schema of the given Models, keyed and sorted by table name
     * @param list<SchemaModel> $schemaModels
     * @return array<string,array<string,mixed>>
     */
    public static function buildSchema(array $schemaModels): array {
        $result = [];
        foreach ($schemaModels as $schemaModel) {
            $result[$schemaModel->tableName] = $schemaModel->toSchemaJSON();
        }
        ksort($result);
        return $result;
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        $schemaFile = Config::getDbSchemaFile();
        if ($schemaFile === "") {
            return 0;
        }

        $file = Strings::addSuffix($schemaFile, ".json");
        $path = Application::getBasePath();
        Storage::deleteFile($path, $file);
        return 1;
    }
}
