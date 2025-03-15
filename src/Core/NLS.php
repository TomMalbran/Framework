<?php
namespace Framework\Core;

use Framework\Discovery\Discovery;
use Framework\System\Package;
use Framework\System\Config;
use Framework\System\Language;
use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The Internalization Strings
 */
class NLS {

    /** @var array<string,array<string,mixed>> */
    private static array  $data     = [];
    private static string $language = "root";



    /**
     * Sets the Language
     * @param string $language
     * @return boolean
     */
    public static function setLanguage(string $language): bool {
        self::$language = $language;
        return true;
    }

    /**
     * Loads an NLS Language
     * @param string $language
     * @return array{}
     */
    private static function load(string $language): array {
        $langCode = Language::getCode($language);

        if (!empty(self::$data[$langCode])) {
            return self::$data[$langCode];
        }

        $data = Discovery::loadJSON(Package::StringsDir, $langCode);
        if (!empty($data)) {
            self::$data[$langCode] = $data;
            return self::$data[$langCode];
        }

        return [];
    }

    /**
     * Returns a value from the data
     * @param string $key
     * @param string $language Optional.
     * @return mixed
     */
    public static function get(string $key, string $language = ""): mixed {
        if (empty($language)) {
            $language = self::$language;
        }

        $data = self::load($language);
        if (!empty($data[$key])) {
            return $data[$key];
        }
        return $key;
    }

    /**
     * Returns a string from the data
     * @param string $key
     * @param string $language Optional.
     * @return string
     */
    public static function getString(string $key, string $language = ""): string {
        $result = self::get($key, $language);
        if (Strings::isString($result)) {
            return (string)$result;
        }
        return "";
    }

    /**
     * Returns a string from the data at the given index
     * @param string         $key
     * @param integer|string $index
     * @param string         $language Optional.
     * @return string
     */
    public static function getIndex(string $key, int|string $index, string $language = ""): string {
        $result = self::get($key, $language);
        if (!empty($result[$index])) {
            return $result[$index];
        }
        return "";
    }

    /**
     * Returns a List from the data
     * @param string $key
     * @param string $language Optional.
     * @return string[]
     */
    public static function getList(string $key, string $language = ""): array {
        $result = self::get($key, $language);
        if (Arrays::isList($result)) {
            return $result;
        }
        if (Arrays::isDict($result)) {
            return array_values($result);
        }
        return [];
    }

    /**
     * Returns a Map from the data
     * @param string $key
     * @param string $language Optional.
     * @return array<string,string>
     */
    public static function getMap(string $key, string $language = ""): array {
        $result = self::get($key, $language);
        if (Arrays::isDict($result)) {
            return $result;
        }
        return [];
    }

    /**
     * Returns a string from the data
     * @param string $key
     * @param string $language Optional.
     * @return Select[]
     */
    public static function getSelect(string $key, string $language = ""): array {
        $result = self::get($key, $language);
        if (Arrays::isArray($result)) {
            return Select::createFromMap($result);
        }
        return [];
    }

    /**
     * Returns all the strings from the data
     * @param string[] $keys
     * @param string   $language Optional.
     * @return string[]
     */
    public static function getAll(array $keys, string $language = ""): array {
        $result = [];
        foreach ($keys as $key) {
            if (!empty($key)) {
                $result[] = self::get($key, $language);
            }
        }
        return $result;
    }



    /**
     * Creates an url from the given arguments
     * @param mixed[] $args
     * @param string  $language Optional.
     * @return string
     */
    public static function url(array $args, string $language = ""): string {
        $result = [];
        foreach ($args as $arg) {
            $result[] = self::get($arg, $language);
        }
        return Config::getUrl(...$result);
    }

    /**
     * Creates an url from the given arguments
     * @param string  $urlKey
     * @param mixed[] $args
     * @param string  $language Optional.
     * @return string
     */
    public static function urlPath(string $urlKey, array $args, string $language = ""): string {
        $result = [];
        foreach ($args as $arg) {
            $result[] = self::get($arg, $language);
        }
        return Config::getUrlWithKey($urlKey, ...$result);
    }

    /**
     * Returns a formatted string
     * @param string  $key
     * @param mixed[] $args
     * @param string  $language Optional.
     * @return string
     */
    public static function format(string $key, array $args, string $language = ""): string {
        $subject = self::get($key, $language);
        return Strings::replaceCallback($subject, "/\{(\d+)\}/", function ($match) use ($args) {
            return $args[$match[1]] ?: "";
        });
    }

    /**
     * Format and Joins the given strings to form a sentence
     * @param string   $key
     * @param string[] $strings
     * @param boolean  $useOr    Optional.
     * @param string   $language Optional.
     * @return string
     */
    public static function formatJoin(string $key, array $strings, bool $useOr = false, string $language = ""): string {
        $args = [ self::join($strings, $useOr, $language) ];
        return self::format($key, $args, $language);
    }

    /**
     * Returns a formatted string using the correct plural string
     * @param string  $key
     * @param integer $count
     * @param mixed[] $args     Optional.
     * @param string  $language Optional.
     * @return string
     */
    public static function pluralize(string $key, int $count, array $args = [], string $language = ""): string {
        $suffix = $count === 1 ? "_SINGULAR" : "_PLURAL";
        $args   = array_merge([ $count ], $args);
        return self::format($key . $suffix, $args, $language);
    }

    /**
     * Returns a formatted string using the correct plural string
     * @param string   $key
     * @param string[] $strings
     * @param boolean  $useOr    Optional.
     * @param string   $language Optional.
     * @return string
     */
    public static function pluralizeList(string $key, array $strings, bool $useOr = false, string $language = ""): string {
        $suffix = count($strings) === 1 ? "_SINGULAR" : "_PLURAL";
        $args   = [ self::join($strings, $useOr, $language) ];
        return self::format($key . $suffix, $args, $language);
    }

    /**
     * Joins the given strings to form a sentence
     * @param string[] $strings
     * @param boolean  $useOr    Optional.
     * @param string   $language Optional.
     * @return string
     */
    public static function join(array $strings, bool $useOr = false, string $language = ""): string {
        $strings = array_values($strings);
        if (empty($strings)) {
            return "";
        }

        $count = count($strings);
        if ($count === 1) {
            return $strings[0];
        }

        $glue   = self::get($useOr ? "GENERAL_OR" : "GENERAL_AND", $language);
        $result = $strings[0];
        for ($i = 1; $i < $count; $i++) {
            $result .= ($i < $count - 1 ? ", " : " $glue ") . $strings[$i];
        }
        return $result;
    }

    /**
     * Returns the Yes/No string
     * @param boolean $value
     * @param string  $language Optional.
     * @return string
     */
    public static function toYesNo(bool $value, string $language = ""): string {
        return self::getIndex("SELECT_YES_NO", $value ? 1 : 0, $language);
    }
}
