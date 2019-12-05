<?php
namespace Framework\Auth;

use Framework\Framework;
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
    public static function load() {
        if (!self::$loaded) {
            self::$loaded = true;
            $data = Framework::loadData(Framework::StatusData);
            
            // Store the Values
            foreach ($data["values"] as $statusName => $statusValue) {
                $name = Strings::toLowerCase($statusName);
                self::$values[$name] = $statusValue;
            }
            // Store the Groups
            foreach ($data["groups"] as $groupName => $statusNames) {
                $gName = Strings::toLowerCase($groupName);
                self::$groups[$gName] = [];
                foreach ($statusNames as $statusName) {
                    $sName = Strings::toLowerCase($statusName);
                    if (isset(self::$values[$sName])) {
                        self::$groups[$gName][] = self::$values[$sName];
                    }
                }
            }
        }
    }




    /**
     * Returns the Status Value from a Status Name
     * @param string $statusName
     * @return integer
     */
    public static function getOne($statusName) {
        self::load();
        $name = Strings::toLowerCase($statusName);
        if (isset(self::$data[$name])) {
            return self::$data[$name];
        }
        return 0;
    }

    /**
     * Returns true if the given Status Value is valid for the given Group
     * @param integer $value
     * @return boolean
     */
    public static function isValid($value) {
        self::load();
        return in_array(array_keys(self::$data), $value);
    }

    /**
     * Returns the Language for the NLS considering the root
     * @param string $value
     * @return string
     */
    public function getNLS($value) {
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
    public static function getSelect() {
        $cache = self::load();
        return Utils::createSelect(self::$data, "key", "name");
    }



    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic($function, array $arguments) {
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
