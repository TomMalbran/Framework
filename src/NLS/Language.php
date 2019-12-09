<?php
namespace Framework\Auth;

use Framework\Framework;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The NLS Languages
 */
class Language {

    private static $loaded = false;
    private static $data   = [];
    
    
    /**
     * Loads the Language Data
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$data   = Framework::loadData(Framework::LanguageData);
        }
    }




    /**
     * Returns the Language Value from a Language Name
     * @param string $langName
     * @return string
     */
    public static function getOne(string $langName): string {
        self::load();
        $name = Strings::toLowerCase($langName);
        if (isset(self::$data[$name])) {
            return self::$data[$name];
        }
        return 0;
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
     * Returns the Language for the NLS considering the root
     * @param string $value
     * @return string
     */
    public function getNLS(string $value) {
        if ($value != "root") {
            return $value;
        }
        self::load();
        foreach (self::$data as $index => $row) {
            if ($row["isRoot"]) {
                return $index;
            }
        }
        return array_keys(self::$data)[0];
    }

    /**
     * Creates a Select of Languages
     * @return array
     */
    public static function getSelect(): array {
        $cache = self::load();
        return Arrays::createSelect(self::$data, "key", "name");
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
