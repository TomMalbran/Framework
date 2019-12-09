<?php
namespace Framework\Schema;

use Framework\Framework;
use Framework\Config\Config;
use Framework\Schema\Database;
use Framework\Schema\Schema;
use Framework\Schema\Structure;
use Framework\Schema\Subrequest;
use Framework\Schema\Migration;
use Framework\Utils\Arrays;

/**
 * The Schema Factory
 */
class Factory {
    
    private static $loaded     = false;
    private static $db         = null;
    private static $data       = [];
    private static $structures = [];
    
    
    /**
     * Loads the Schemas Data
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            $config  = Config::get("db");
            $schemas = Framework::loadData(Framework::SchemaData);
            $frame   = Framework::loadFile("data", Framework::SchemaData, true);

            self::$loaded = true;
            self::$db     = new Database($config);
            
            foreach ($schemas as $key => $data) {
                if (!empty($frame[$key])) {
                    self::$data[$key] = Arrays::extend($frame[$key], $data);
                } else {
                    self::$data[$key] = $data;
                }
            }
        }
    }
    
    
    
    /**
     * Gets the Schema
     * @param string $key
     * @return Schema
     */
    public static function getSchema(string $key): Schema {
        self::load();
        if (empty(self::$data[$key])) {
            return null;
        }
        $structure  = self::getStructure($key);
        $subrequest = self::getSubrequest($key);
        return new Schema(self::$db, $structure, $subrequest);
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
     * @return array
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
     * @param Database $db        Optional.
     * @param boolean  $canDelete Optional.
     * @return void
     */
    public static function migrate(Database $db = null, bool $canDelete = false): void {
        self::load();
        $database = $db !== null ? $db : self::$db;
        Migration::migrate($database, self::$data, $canDelete);
    }
}
