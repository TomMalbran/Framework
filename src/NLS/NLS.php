<?php
namespace Framework\NLS;

use Framework\Framework;
use Framework\NLS\Language;

/**
 * The Internalization Strings
 */
class NLS {
    
    private static $loaded = [];
    private static $data   = [];
    
    
    /**
     * Loads an NLS Language
     * @param string $lang
     * @return array
     */
    public static function load($lang) {
        if (empty(self::$loaded[$lang])) {
            self::$loaded[$lang] = true;
            self::$data[$lang]   = Framework::loadFile(Framework::NLSDir, $lang);
        }
        return self::$data[$lang];
    }

    /**
     * Returns a string from the data
     * @param string $key
     * @param string $lang Optional.
     * @return string
     */
    public static function get($key, $lang = "root") {
        $nls  = Language::getNLS($lang);
        $data = self::load($nls);

        if (!empty($data[$key])) {
            return $data[$key];
        }
        if ($nls != Language::getNLS("root")) {
            return get($key);
        }
        return "";
    }

    /**
     * Returns a string from the data at the given index
     * @param string $key
     * @param string $index
     * @param string $lang  Optional.
     * @return string
     */
    public static function getIndex($key, $index, $lang = "root") {
        $result = self::get($key, $lang);
        if (!empty($result[$index])) {
            return $result[$index];
        }
        return "";
    }
    
    /**
     * Returns all the strings from the$key$keydata
     * @param string[] $keys
     * @param string   $lang Optional.
     * @return string[]
     */
    public static function getAll(array $keys, $lang = "root") {
        $result = [];
        foreach ($keys as $key) {
            if (!empty($key)) {
                $result[] = self::get($key, $lang);
            }
        }
        return $result;
    }
}