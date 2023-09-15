<?php
namespace Framework\Schema;

use Framework\Framework;
use Framework\Utils\Strings;

/**
 * The Schema Keys Data
 */
class KeyChain {

    private static bool  $loaded = false;

    /** @var string[] */
    private static array $data   = [];


    /**
     * Loads the Keys Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$data   = Framework::loadData(Framework::KeyData);
        return true;
    }

    /**
     * Returns the Master Key with the given key
     * @param string $key
     * @return string
     */
    public static function get(string $key): string {
        self::load();
        if (!empty(self::$data[$key])) {
            return base64_encode(hash("sha256", self::$data[$key], true));
        }
        return "";
    }



    /**
     * Recreates all the Master Keys
     * @return string[]
     */
    public static function recreate(): array {
        self::load();
        $data = [];
        foreach (array_keys(self::$data) as $key) {
            $data[$key] = Strings::randomCode(64, "luds");
        }
        self::$data = $data;
        return $data;
    }

    /**
     * Saves all the Master Keys
     * @param string[] $data
     * @return boolean
     */
    public static function save(array $data): bool {
        if (Framework::saveData(Framework::KeyData, $data)) {
            self::$data = $data;
            return true;
        }
        return false;
    }
}
