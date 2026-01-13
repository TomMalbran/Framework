<?php
namespace Framework\Intl;

use Framework\Intl\NLSConfig;
use Framework\System\Config;
use Framework\System\Language;
use Framework\Utils\Dictionary;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The Internalization Strings
 */
class NLS {

    /** @var array<string,Dictionary> */
    private static array  $data     = [];
    private static string $language = "root";



    /**
     * Gets the Language
     * @return string
     */
    public static function getLanguage(): string {
        return self::$language;
    }

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
     * @param string $language Optional.
     * @return Dictionary
     */
    private static function load(string $language = ""): Dictionary {
        if ($language === "") {
            $language = self::$language;
        }
        $langCode = Language::getCode($language);

        if (isset(self::$data[$langCode])) {
            return self::$data[$langCode];
        }

        $data = NLSConfig::loadStrings($langCode);
        if (!$data->isEmpty()) {
            self::$data[$langCode] = $data;
            return $data;
        }

        return new Dictionary();
    }

    /**
     * Returns a string from the data
     * @param string $key
     * @param string $language Optional.
     * @return string
     */
    public static function getString(string $key, string $language = ""): string {
        $data = self::load($language);
        return $data->getString($key);
    }

    /**
     * Returns a string from the data at the given index
     * @param string         $key
     * @param integer|string $index
     * @param string         $language Optional.
     * @return string
     */
    public static function getIndex(string $key, int|string $index, string $language = ""): string {
        $data = self::load($language);
        $dict = $data->getDict($key);
        return $dict->getString((string)$index);
    }

    /**
     * Returns a List from the data
     * @param string $key
     * @param string $language Optional.
     * @return string[]
     */
    public static function getList(string $key, string $language = ""): array {
        $data = self::load($language);
        $dict = $data->getDict($key);
        return $dict->toStrings();
    }

    /**
     * Returns a Map from the data
     * @param string $key
     * @param string $language Optional.
     * @return array<string,string>
     */
    public static function getMap(string $key, string $language = ""): array {
        $data = self::load($language);
        $dict = $data->getDict($key);
        return $dict->toMap();
    }

    /**
     * Returns a string from the data
     * @param string $key
     * @param string $language Optional.
     * @return Select[]
     */
    public static function getSelect(string $key, string $language = ""): array {
        $data = self::load($language);
        $dict = $data->getDict($key);
        $map  = $dict->toMap();
        return Select::createFromMap($map);
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
            if ($key !== "") {
                $result[] = self::getString($key, $language);
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
            if (is_string($arg)) {
                $result[] = self::getString($arg, $language);
            } elseif (is_int($arg)) {
                $result[] = $arg;
            }
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
            if (is_string($arg)) {
                $result[] = self::getString($arg, $language);
            } elseif (is_int($arg)) {
                $result[] = $arg;
            }
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
        $subject = self::getString($key, $language);
        return Strings::replaceCallback($subject, "/\{(\d+)\}/", function (array $match) use ($args) {
            if (!isset($match[1])) {
                return "";
            }
            return $args[$match[1]] ?? "";
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
        if (count($strings) === 0) {
            return "";
        }

        $count = count($strings);
        if ($count === 1) {
            return $strings[0];
        }

        $glue   = self::getString($useOr ? "GENERAL_OR" : "GENERAL_AND", $language);
        $result = $strings[0];
        for ($i = 1; $i < $count; $i++) {
            $string  = $strings[$i] ?? "";
            $result .= ($i < $count - 1 ? ", " : " $glue ") . $string;
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
