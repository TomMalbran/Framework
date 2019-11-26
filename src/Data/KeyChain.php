<?php
namespace Framework\Data;

use Framework\Framework;
use Framework\Utils\Utils;

/**
 * The Keys Data
 */
class KeyChain {
    
    private static $loaded = false;
    private static $data   = [];
    
    
    /**
     * Loads the Keys Data
     * @return void
     */
    public static function load() {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$data   = Framework::loadData(Framework::KeyData);
        }
    }
    
    /**
     * Returns the Master Key with the given key
     * @param string $key
     * @return string
     */
    public static function get($key) {
        self::load();
        if (!empty(self::$data[$key])) {
            return base64_encode(hash("sha256", self::$data[$key], true));
        }
        return "";
    }


    
    /**
     * Recreates all the Master Keys
     * @return object
     */
    public static function recreate() {
        self::load();
        $data = [];
        foreach (array_keys(self::$data) as $key) {
            $data[$key] = Utils::generatePassword(64, "luds");
        }
        self::$data = $data;
        return (object)$data;
    }
    
    /**
     * Saves all the Master Keys
     * @param mixed $data
     * @return void
     */
    public static function save($data) {
        Framework::saveData(Framework::Key, $data);
        self::$data = $data;
    }
}
