<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Database\Schema;
use Framework\Database\Structure;
use Framework\Database\SubRequest;
use Framework\Database\Database;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Factory
 */
class Factory {

    private static bool      $loaded     = false;
    private static ?Database $db         = null;

    /** @var array{}[] */
    private static array     $data       = [];

    /** @var Structure[] */
    private static array     $structures = [];

    /** @var Schema[] */
    private static array     $schemas    = [];


    /**
     * Loads the Schemas Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        self::$loaded = true;
        self::$db     = Framework::getDatabase();

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
     * Gets the Schema
     * @return array{}
     */
    public static function getData(): array {
        self::load();
        return self::$data;
    }

    /**
     * Gets the Schema
     * @param string $key
     * @return Schema|null
     */
    public static function getSchema(string $key): ?Schema {
        self::load();
        if (empty(self::$data[$key])) {
            return null;
        }
        if (!empty(self::$schemas[$key])) {
            return self::$schemas[$key];
        }

        $structure  = self::getStructure($key);
        $subRequest = self::getSubRequest($key);
        self::$schemas[$key] = new Schema(self::$db, $structure, $subRequest);
        return self::$schemas[$key];
    }

    /**
     * Creates and Returns the Structure for the given Key
     * @param string $key
     * @return Structure
     */
    public static function getStructure(string $key): Structure {
        if (empty(self::$structures[$key])) {
            self::$structures[$key] = new Structure($key, self::$data[$key]);
        }
        return self::$structures[$key];
    }

    /**
     * Creates and Returns the SubRequests for the given Key
     * @param string $key
     * @return SubRequest[]
     */
    public static function getSubRequest(string $key): array {
        $data   = self::$data[$key];
        $result = [];

        if (!empty($data["subrequests"])) {
            foreach ($data["subrequests"] as $subKey => $subData) {
                $structure    = self::getStructure($key);
                $subStructure = self::getStructure($subKey);
                $subSchema    = new Schema(self::$db, $subStructure);
                $result[]     = new SubRequest($subSchema, $structure, $subData);
            }
        }
        return $result;
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
