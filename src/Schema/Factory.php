<?php
namespace Framework\Schema;

use Framework\Framework;
use Framework\Data\Config;
use Framework\Schema\Database;
use Framework\Schema\Schema;
use Framework\Schema\Structure;
use Framework\Schema\Subrequest;
use Framework\Schema\Migration;

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
    public static function load() {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$db     = new Database(Config::get("db"));
            self::$data   = Framework::loadData(Framework::Schema);
        }
    }
    
    
    
    /**
     * Gets the Schema
     * @param string $key
     * @return Schema
     */
    public static function getSchema($key) {
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
    public static function getStructure($key) {
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
    public static function getSubrequest($key) {
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
     * @param Database $db Optional.
     * @return void
     */
    public static function migrate(Database $db = null) {
        self ::load();
        $database  = $db !== null ? $db : self::$db;
        $migration = new Migration($database, self::$data);
        $migration->migrate();
    }
}
