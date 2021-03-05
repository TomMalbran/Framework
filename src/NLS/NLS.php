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
    public static function load(string $lang): array {
        if (empty(self::$loaded[$lang])) {
            self::$loaded[$lang] = true;
            self::$data[$lang]   = Framework::loadJSON(Framework::NLSDir, $lang);
        }
        return self::$data[$lang];
    }

    /**
     * Returns a string from the data
     * @param string $key
     * @param string $lang Optional.
     * @return mixed
     */
    public static function get(string $key, string $lang = "root") {
        $nls  = Language::getNLS($lang);
        $data = self::load($nls);

        if (!empty($data[$key])) {
            return $data[$key];
        }
        if ($nls != Language::getNLS("root")) {
            return self::get($key);
        }
        return "";
    }

    /**
     * Returns a formated string using the correct plural string
     * @param string  $key
     * @param integer $count
     * @param string  $lang  Optional.
     * @return string
     */
    public static function pluralize(string $key, int $count, string $lang = "root"): string {
        $suffix = $count === 1 ? "_SINGULAR" : "_PLURAL";
        $result = self::get($key . $suffix, $lang);
        return $result;
    }

    /**
     * Returns a string from the data at the given index
     * @param string  $key
     * @param integer $index
     * @param string  $lang  Optional.
     * @return string
     */
    public static function getIndex(string $key, int $index, string $lang = "root"): string {
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
    public static function getAll(array $keys, string $lang = "root"): array {
        $result = [];
        foreach ($keys as $key) {
            if (!empty($key)) {
                $result[] = self::get($key, $lang);
            }
        }
        return $result;
    }
}
