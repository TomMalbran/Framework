<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Database\Structure;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Factory
 */
class Factory {

    private static bool  $loaded     = false;

    /** @var array{}[] */
    private static array $data       = [];

    /** @var Structure[] */
    private static array $structures = [];



    /**
     * Loads the Schemas Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        self::$loaded = true;

        $schemas      = Framework::loadData(Framework::SchemaData);
        $frameSchemas = Framework::loadJSON(Framework::DataDir, Framework::SchemaData, true);

        foreach ($schemas as $key => $data) {
            if (empty($frameSchemas[$key])) {
                self::$data[$key] = $data;
            }
        }

        foreach ($frameSchemas as $key => $data) {
            if (!empty($schemas[$key])) {
                self::$data[$key] = Arrays::extend($data, $schemas[$key]);
            } else {
                self::$data[$key] = $data;
            }
            self::$data[$key]["fromFramework"] = true;
        }
        return true;
    }



    /**
     * Gets the Schema data
     * @return array{}
     */
    public static function getData(): array {
        self::load();
        return self::$data;
    }

    /**
     * Creates and Returns the Structure for the given Key
     * @param string $schema
     * @return Structure
     */
    public static function getStructure(string $schema): Structure {
        self::load();
        if (empty(self::$structures[$schema])) {
            self::$structures[$schema] = new Structure($schema, self::$data[$schema]);
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
