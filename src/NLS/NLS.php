<?php
namespace Framework\NLS;

use Framework\Framework;
use Framework\NLS\Language;

/**
 * The Internalization Strings
 */
class NLS {

    /** @var boolean[] */
    private static array $loaded = [];

    /** @var array{}{} */
    private static array $data   = [];


    /**
     * Loads an NLS Language
     * @param string $lang
     * @return array{}
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
    public static function get(string $key, string $lang = "root"): mixed {
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
     * @param string         $key
     * @param integer|string $index
     * @param string         $lang  Optional.
     * @return string
     */
    public static function getIndex(string $key, int|string $index, string $lang = "root"): string {
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
     * @param string  $key
     * @param mixed[] $args
     * @param string  $lang Optional.
     * @return string
     */
    public static function format(string $key, array $args, string $lang = "root"): string {
        $subject = self::get($key, $lang);
        return preg_replace_callback("/\{(\d+)\}/", function ($match) use ($args) {
            return $args[$match[1]] ?: "";
        }, $subject);
    }

    /**
     * Format and Joins the given strings to form a sentence
     * @param string   $key
     * @param string[] $strings
     * @param boolean  $useOr   Optional.
     * @param string   $lang    Optional.
     * @return string
     */
    public static function formatJoin(string $key, array $strings, bool $useOr = false, string $lang = "root"): string {
        return self::format($key, [ self::join($strings, $useOr, $lang) ], $lang);
    }

    /**
     * Returns a formatted string using the correct plural string
     * @param string  $key
     * @param integer $count
     * @param mixed[] $args  Optional.
     * @param string  $lang  Optional.
     * @return string
     */
    public static function pluralize(string $key, int $count, array $args = [], string $lang = "root"): string {
        $suffix = $count === 1 ? "_SINGULAR" : "_PLURAL";
        return self::format($key . $suffix, array_merge([ $count ], $args), $lang);
    }

    /**
     * Returns a formated string using the correct plural string
     * @param string   $key
     * @param string[] $strings
     * @param boolean  $useOr   Optional.
     * @param string   $lang    Optional.
     * @return string
     */
    public static function pluralizeList(string $key, array $strings, bool $useOr = false, string $lang = "root"): string {
        $suffix = count($strings) === 1 ? "_SINGULAR" : "_PLURAL";
        return self::format($key . $suffix, [ self::join($strings, $useOr, $lang) ], $lang);
    }

    /**
     * Joins the given strings to form a sentence
     * @param string[] $strings
     * @param boolean  $useOr   Optional.
     * @param string   $lang    Optional.
     * @return string
     */
    public static function join(array $strings, bool $useOr = false, string $lang = "root"): string {
        $strings = array_values($strings);
        $count   = count($strings);
        if ($count === 1) {
            return $strings[0];
        }
        $glue   = self::get($useOr ? "GENERAL_OR" : "GENERAL_AND", $lang);
        $result = $strings[0];
        for ($i = 1; $i < $count; $i++) {
            $result .= ($i < $count - 1 ? ", " : " $glue ") . $strings[$i];
        }
        return $result;
    }
}
