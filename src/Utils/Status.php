<?php
namespace Framework\Utils;

use Framework\Framework;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Status
 */
class Status {

    private static bool  $loaded = false;

    /** @var array{} */
    private static array $groups = [];

    /** @var array{} */
    private static array $values = [];


    /**
     * Loads the Status Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
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
        return true;
    }




    /**
     * Returns the Status Value from a Status Name
     * @param string $statusName
     * @return integer
     */
    public static function getOne(string $statusName): int {
        self::load();
        $name = Strings::toLowerCase($statusName);
        if (isset(self::$values[$name])) {
            return self::$values[$name];
        }
        return 0;
    }

    /**
     * Returns the Status Values inside the given Group
     * @param string $groupName
     * @return integer[]
     */
    public static function getGroup(string $groupName): array {
        self::load();
        $name = Strings::toLowerCase($groupName);
        if (isset(self::$groups[$name])) {
            return self::$groups[$name];
        }
        return [];
    }

    /**
     * Returns true if the given Status Value is valid for the given Group
     * @param integer $value
     * @param string  $groupName Optional.
     * @return boolean
     */
    public static function isValid(int $value, string $groupName = "general"): bool {
        $group = self::getGroup($groupName);
        return Arrays::contains($group, $value);
    }



    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $function, array $arguments) {
        $value = !empty($arguments[0]) ? (int)$arguments[0] : 0;

        // Function "getXxx(s)": Get the group xxx
        if (Strings::startsWith($function, "get")) {
            $groupName = Strings::stripStart($function, "get");
            $groupName = Strings::stripEnd($groupName, "s");
            return self::getGroup($groupName);
        }

        // Function "inXxx(s)" or "isValidXxx": Check if the given value is in the group xxx
        if (Strings::startsWith($function, "in") || Strings::startsWith($function, "isValid")) {
            $groupName = Strings::stripStart($function, "in");
            $groupName = Strings::stripStart($function, "isValid");
            $groupName = Strings::stripEnd($groupName, "s");
            $group     = self::getGroup($groupName);
            return Arrays::contains($group, $value);
        }

        // Function "isXxxOrYyy": Check if the given value is equal to xxx or yyy
        if (Strings::startsWith($function, "is") && Strings::contains($function, "Or")) {
            $statusNames  = Strings::stripStart($function, "is");
            $statusParts  = Strings::split($statusNames, "Or");
            $statusValueX = self::getOne($statusParts[0]);
            $statusValueY = self::getOne($statusParts[1]);
            return $value == $statusValueX || $value == $statusValueY;
        }

        // Function "isXxx": Check if the given value is equal to xxx
        if (Strings::startsWith($function, "is")) {
            $statusName  = Strings::stripStart($function, "is");
            $statusValue = self::getOne($statusName);
            return $value == $statusValue;
        }

        // Function "xxx": Return the status value with the name xxx
        return self::getOne($function);
    }
}
