<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\Database\Structure;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

/**
 * The Schema Factory
 */
class Factory {

    private static ?Dictionary $data = null;

    /** @var Structure[] */
    private static array $structures = [];



    /**
     * Gets the Schema data
     * @return Dictionary
     */
    public static function getData(): Dictionary {
        if (self::$data !== null) {
            return self::$data;
        }
        self::$data = new Dictionary();

        /** @var array<string,mixed> */
        $appSchemas   = Discovery::loadData(DataFile::Schemas);

        /** @var array<string,mixed> */
        $frameSchemas = Discovery::loadFrameData(DataFile::Schemas);

        foreach ($appSchemas as $key => $data) {
            if (empty($frameSchemas[$key])) {
                self::$data->set($key, $data);
            }
        }

        foreach ($frameSchemas as $key => $data) {
            if (!is_array($data)) {
                continue;
            }

            $schemaData = [];
            if (isset($appSchemas[$key]) && is_array($appSchemas[$key])) {
                $schemaData = Arrays::extend($data, $appSchemas[$key]);
            } else {
                $schemaData = $data;
            }
            $schemaData["fromFramework"] = true;

            self::$data->set($key, $schemaData);
        }

        return self::$data;
    }



    /**
     * Creates and Returns the Structure for the given Key
     * @param string $schema
     * @return Structure
     */
    public static function getStructure(string $schema): Structure {
        if (!isset(self::$structures[$schema])) {
            $data = self::getData()->getDict($schema);
            self::$structures[$schema] = new Structure($schema, $data);
        }
        return self::$structures[$schema];
    }

    /**
     * Gets the Table Name for the Schema
     * @param string $schema
     * @return string
     */
    public static function getTableName(string $schema): string {
        return Strings::pascalCaseToSnakeCase($schema);
    }
}
