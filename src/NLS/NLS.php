<?php
namespace Framework\NLS;

use Framework\Framework;
use Framework\NLS\Language;
use Framework\Utils\Arrays;

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
     * @param string $language
     * @return array{}
     */
    public static function load(string $language): array {
        if (empty(self::$loaded[$language])) {
            self::$loaded[$language] = true;
            self::$data[$language]   = Framework::loadJSON(Framework::NLSDir, $language);
        }
        return self::$data[$language];
    }

    /**
     * Returns a string from the data
     * @param string  $key
     * @param string  $language    Optional.
     * @param boolean $withDefault Optional.
     * @return mixed
     */
    public static function get(string $key, string $language = "root", bool $withDefault = true): mixed {
        $nls  = Language::getNLS($language);
        $data = self::load($nls);

        if (!empty($data[$key])) {
            return $data[$key];
        }
        if ($withDefault && $nls != Language::getNLS("root")) {
            return self::get($key);
        }
        return $key;
    }

    /**
     * Returns a string from the data at the given index
     * @param string         $key
     * @param integer|string $index
     * @param string         $language    Optional.
     * @param boolean        $withDefault Optional.
     * @return string
     */
    public static function getIndex(string $key, int|string $index, string $language = "root", bool $withDefault = true): string {
        $result = self::get($key, $language, $withDefault);
        if (!empty($result[$index])) {
            return $result[$index];
        }
        return "";
    }

    /**
     * Returns all the strings from the$key$keydata
     * @param string[] $keys
     * @param string   $language    Optional.
     * @param boolean  $withDefault Optional.
     * @return string[]
     */
    public static function getAll(array $keys, string $language = "root", bool $withDefault = true): array {
        $result = [];
        foreach ($keys as $key) {
            if (!empty($key)) {
                $result[] = self::get($key, $language, $withDefault);
            }
        }
        return $result;
    }



    /**
     * Returns a formatted string
     * @param string  $key
     * @param mixed[] $args
     * @param string  $language    Optional.
     * @param boolean $withDefault Optional.
     * @return string
     */
    public static function format(string $key, array $args, string $language = "root", bool $withDefault = true): string {
        $subject = self::get($key, $language, $withDefault);
        return preg_replace_callback("/\{(\d+)\}/", function ($match) use ($args) {
            return $args[$match[1]] ?: "";
        }, $subject);
    }

    /**
     * Format and Joins the given strings to form a sentence
     * @param string   $key
     * @param string[] $strings
     * @param boolean  $useOr       Optional.
     * @param string   $language    Optional.
     * @param boolean  $withDefault Optional.
     * @return string
     */
    public static function formatJoin(string $key, array $strings, bool $useOr = false, string $language = "root", bool $withDefault = true): string {
        $args = [ self::join($strings, $useOr, $language, $withDefault) ];
        return self::format($key, $args, $language, $withDefault);
    }

    /**
     * Returns a formatted string using the correct plural string
     * @param string  $key
     * @param integer $count
     * @param mixed[] $args        Optional.
     * @param string  $language    Optional.
     * @param boolean $withDefault Optional.
     * @return string
     */
    public static function pluralize(string $key, int $count, array $args = [], string $language = "root", bool $withDefault = true): string {
        $suffix = $count === 1 ? "_SINGULAR" : "_PLURAL";
        $args   = array_merge([ $count ], $args);
        return self::format($key . $suffix, $args, $language, $withDefault);
    }

    /**
     * Returns a formated string using the correct plural string
     * @param string   $key
     * @param string[] $strings
     * @param boolean  $useOr       Optional.
     * @param string   $language    Optional.
     * @param boolean  $withDefault Optional.
     * @return string
     */
    public static function pluralizeList(string $key, array $strings, bool $useOr = false, string $language = "root", bool $withDefault = true): string {
        $suffix = count($strings) === 1 ? "_SINGULAR" : "_PLURAL";
        $args   = [ self::join($strings, $useOr, $language, $withDefault) ];
        return self::format($key . $suffix, $args, $language, $withDefault);
    }

    /**
     * Joins the given strings to form a sentence
     * @param string[] $strings
     * @param boolean  $useOr       Optional.
     * @param string   $language    Optional.
     * @param boolean  $withDefault Optional.
     * @return string
     */
    public static function join(array $strings, bool $useOr = false, string $language = "root", bool $withDefault = true): string {
        $strings = array_values($strings);
        $count   = count($strings);
        if ($count === 1) {
            return $strings[0];
        }
        $glue   = self::get($useOr ? "GENERAL_OR" : "GENERAL_AND", $language, $withDefault);
        $result = $strings[0];
        for ($i = 1; $i < $count; $i++) {
            $result .= ($i < $count - 1 ? ", " : " $glue ") . $strings[$i];
        }
        return $result;
    }
}
