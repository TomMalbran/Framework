<?php
namespace Framework\Auth;

use Framework\Framework;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Auth Access
 */
class Access {

    private static $loaded = false;
    private static $groups = [];
    private static $levels = [];
    
    
    /**
     * Loads the Access Data
     * @return void
     */
    public static function load() {
        if (!self::$loaded) {
            self::$loaded = true;
            $data = Framework::loadData(Framework::AccessData);
            
            // Store the groups and levels
            foreach ($data as $groupName => $accessData) {
                $gName = Strings::toLowerCase($groupName);
                self::$groups[$gName] = [];
                foreach ($accessData as $accessName => $accessLevel) {
                    $aName = Strings::toLowerCase($accessName);
                    self::$groups[$gName][] = $accessLevel;
                    self::$levels[$aName]   = $accessLevel;
                }
            }
        }
    }




    /**
     * Returns the Access Level from an Access Name
     * @param string $accessName
     * @return integer
     */
    public static function getOne(string $accessName): int {
        self::load();
        $name = Strings::toLowerCase($accessName);
        if (isset(self::$levels[$name])) {
            return self::$levels[$name];
        }
        return 0;
    }

    /**
     * Returns the Access Levels inside the given Group
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
     * Returns true if the user has access
     * @param integer $granted
     * @param integer $requested
     * @return boolean
     */
    public static function grant(int $granted, int $requested): bool {
        if (self::inAPI($granted)) {
            return self::inAPI($requested) || self::inGeneral($requested);
        }
        return $granted >= $requested;
    }



    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $function, array $arguments) {
        $level = !empty($arguments[0]) ? (int)$arguments[0] : 0;

        // Function "getXxx(s)": Get the group xxx
        if (Strings::startsWith($function, "get")) {
            $groupName = Strings::stripStart($function, "get");
            $groupName = Strings::stripEnd($groupName, "s");
            return self::getGroup($groupName);
        }

        // Function "inXxxOrYyy": Check if the given level is in the group xxx or yyy
        if (Strings::startsWith($function, "in") && Strings::contains($function, "Or")) {
            $groupNames = Strings::stripStart($function, "in");
            $groupParts = Strings::split($groupNames, "Or");
            $groupX     = self::getGroup($groupParts[0]);
            $groupY     = self::getGroup($groupParts[1]);
            return Arrays::contains($groupX, $level) || Arrays::contains($groupY, $level);
        }

        // Function "inXxx(s)": Check if the given level is in the group xxx
        if (Strings::startsWith($function, "in")) {
            $groupName = Strings::stripStart($function, "in");
            $groupName = Strings::stripEnd($groupName, "s");
            $group     = self::getGroup($groupName);
            return Arrays::contains($group, $level);
        }

        // Function "isValidXxx": Check if the given level is in the group xxx
        if (Strings::startsWith($function, "isValid")) {
            $groupName = Strings::stripStart($function, "isValid");
            $group     = self::getGroup($groupName);
            return Arrays::contains($group, $level);
        }

        // Function "isXxxOrHigher": Check if the given level is equal or higher than xxx
        if (Strings::startsWith($function, "is") && Strings::endsWith($function, "OrHigher")) {
            $accessName  = Strings::stripStart($function, "is");
            $accessName  = Strings::stripEnd($accessName, "OrHigher");
            $accessLevel = self::getOne($accessName);
            return $level >= $accessLevel;
        }

        // Function "isXxxOrYyy": Check if the given level is equal to xxx or yyy
        if (Strings::startsWith($function, "is") && Strings::contains($function, "Or")) {
            $accessNames  = Strings::stripStart($function, "is");
            $accessParts  = Strings::split($accessNames, "Or");
            $accessLevelX = self::getOne($accessParts[0]);
            $accessLevelY = self::getOne($accessParts[1]);
            return $level == $accessLevelX || $level == $accessLevelY;
        }

        // Function "isXxx": Check if the given level is equal to xxx
        if (Strings::startsWith($function, "is")) {
            $accessName  = Strings::stripStart($function, "is");
            $accessLevel = self::getOne($accessName);
            return $level == $accessLevel;
        }

        // Function "xxxs": Get the group xxx
        if (Strings::endsWith($function, "s")) {
            $groupName = Strings::stripEnd($function, "s");
            $group     = self::getGroup($groupName);
            if (!empty($group)) {
                return $group;
            }
        }

        // Function "xxx": Return the access level with the name xxx
        return self::getOne($function);
    }
}
