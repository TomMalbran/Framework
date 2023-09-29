<?php
namespace Framework\NLS;

use Framework\Framework;
use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The NLS Languages
 */
class Language {

    private static bool  $loaded = false;

    /** @var array{}[] */
    private static array $data   = [];


    /**
     * Loads the Language Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$data   = Framework::loadData(Framework::LanguageData);
        return true;
    }



    /**
     * Returns the Language Value from a Language Name
     * @param string $langName
     * @return array{}|null
     */
    public static function getOne(string $langName): ?array {
        self::load();
        $name = Strings::toLowerCase($langName);
        if (isset(self::$data[$name])) {
            return self::$data[$name];
        }
        return null;
    }

    /**
     * Returns true if the given Language Value is valid for the given Group
     * @param string $value
     * @return boolean
     */
    public static function isValid(string $value): bool {
        self::load();
        return Arrays::containsKey(self::$data, $value);
    }

    /**
     * Returns a valid Language Code
     * @param string $value
     * @return string
     */
    public static function getCode(string $value): string {
        self::load();
        if ($value != "root" && !empty(self::$data[$value])) {
            return $value;
        }
        foreach (self::$data as $index => $row) {
            if ($row["isRoot"]) {
                return $index;
            }
        }
        return Arrays::getFirstKey(self::$data);
    }

    /**
     * Returns all the Languages
     * @return array{}
     */
    public static function getAll(): array {
        self::load();
        return Arrays::createMap(self::$data, "key", "name");
    }

    /**
     * Creates a Select of Languages
     * @return Select[]
     */
    public static function getSelect(): array {
        self::load();
        return Select::create(self::$data, "key", "name");
    }



    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $function, array $arguments) {
        $value = !empty($arguments[0]) ? $arguments[0] : "";

        // Function "isXxx": isSpanish("es") => true, isSpanish("en") => false
        if (Strings::startsWith($function, "is")) {
            $languageName = Strings::stripStart($function, "is");
            $language     = self::getOne($value);
            return !empty($language) && Strings::isEqual($language["name"], $languageName);
        }

        // Function "xxx": Spanish() => "es"
        foreach (self::$data as $index => $row) {
            if (Strings::isEqual($row["name"], $function)) {
                return $index;
            }
        }

        // Function "xxx": ES() => {}
        return self::getOne($function);
    }
}
