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
        return $key;
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



    /**
     * Returns a formatted string
     * @param string $key
     * @param array  $args
     * @param string $lang Optional.
     * @return string
     */
    public static function format(string $key, array $args, string $lang = "root"): string {
        $subject = self::get($key, $lang);
        return preg_replace_callback("/\{(\d+)\}/", function ($match) use ($args) {
            return $args[$match[1]] ?: "";
        }, $subject);
    }

    /**
     * Returns a formatted string using the correct plural string
     * @param string  $key
     * @param integer $count
     * @param array   $args  Optional.
     * @param string  $lang  Optional.
     * @return string
     */
    public static function pluralize(string $key, int $count, array $args = [], string $lang = "root"): string {
        $suffix = $count === 1 ? "_SINGULAR" : "_PLURAL";
        return self::format($key . $suffix, array_merge([ $count ], $args), $lang);
    }

    /**
     * Joins the given strings to form a sentence
     * @param string[] $strings
     * @param string   $lang    Optional.
     * @return string
     */
    public static function join(array $strings, string $lang = "root"): string {
        $count = count($strings);
        if ($count === 1) {
            return $strings[0];
        }
        $and    = self::get("GENERAL_AND", $lang);
        $result = $strings[0];
        for ($i = 1; $i < $count; $i++) {
            $result .= ($i < $count - 1 ? ", " : " $and ") . $strings[$i];
        }
        return $result;
    }
}
