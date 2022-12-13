<?php
namespace Framework\Schema;

use Framework\Framework;
use Framework\Schema\Schema;
use Framework\Schema\Structure;
use Framework\Schema\Subrequest;
use Framework\Schema\Migration;
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
        $frame   = Framework::loadJSON("data", Framework::SchemaData, true);

        self::$loaded = true;
        self::$db     = Framework::getDatabase();

        foreach ($schemas as $key => $data) {
            if (!empty($frame[$key])) {
                self::$data[$key] = Arrays::extend($frame[$key], $data);
            } else {
                self::$data[$key] = $data;
            }
        }
        return true;
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
        $subrequest = self::getSubrequest($key);
        self::$schemas[$key] = new Schema(self::$db, $structure, $subrequest);
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
     * Creates and Returns the Subrequests for the given Key
     * @param string $key
     * @return Subrequest[]
     */
    public static function getSubrequest(string $key): array {
        $data   = self::$data[$key];
        $result = [];

        if (!empty($data["subrequests"])) {
            foreach ($data["subrequests"] as $subKey => $subData) {
                $structure    = self::getStructure($key);
                $subStructure = self::getStructure($subKey);
                $subSchema    = new Schema(self::$db, $subStructure);
                $result[]     = new Subrequest($subSchema, $structure, $subData);
            }
        }
        return $result;
    }



    /**
     * Performs a Migration on the Schema
     * @param Database|null $db        Optional.
     * @param boolean       $canDelete Optional.
     * @return boolean
     */
    public static function migrate(?Database $db = null, bool $canDelete = false): bool {
        self::load();
        $database = $db !== null ? $db : self::$db;
        return Migration::migrate($database, self::$data, $canDelete);
    }
}
