<?php
namespace Framework\Schema;

use Framework\Framework;
use Framework\Schema\Schema;
use Framework\Schema\Structure;
use Framework\Schema\SubRequest;
use Framework\Schema\Database;
use Framework\Utils\Arrays;

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

        $schemas = Framework::loadData(Framework::SchemaData);
        $frame   = Framework::loadJSON(Framework::DataDir, Framework::SchemaData, true);

        self::$loaded = true;
        self::$db     = Framework::getDatabase();

        foreach ($schemas as $key => $data) {
            if (!empty($frame[$key])) {
                self::$data[$key] = Arrays::extend($frame[$key], $data);
                self::$data[$key]["fromFramework"] = true;
            } else {
                self::$data[$key] = $data;
            }
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
}
