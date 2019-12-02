<?php
namespace Framework\Auth;

use Framework\Framework;
use Framework\Utils\Strings;

/**
 * The Auth Access
 */
class Access {

    private static $loaded = false;
    private static $data   = [];
    private static $groups = [];
    private static $levels = [];
    
    
    /**
     * Loads the Access Data
     * @return void
     */
    public static function load() {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$data   = Framework::loadData(Framework::AccessData);

            // Parse the Data
            foreach (self::$data as $groupName => $accessData) {
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
    public static function getOne($accessName) {
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
    public static function getGroup($groupName) {
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
    public static function grant($granted, $requested) {
        if (self::isAPI($granted)) {
            return self::isAPI($requested) || self::isGeneral($requested);
        }
        return $granted >= $requested;
    }



    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic($function, array $arguments) {
        $level = !empty($arguments[0]) ? (int)$arguments[0] : 0;

        // Function "getXxx(s)": Get the group xxx
        if (Strings::startsWith($function, "get")) {
            $groupName = Strings::removeFromStart($function, "get");
            $groupName = Strings::removeFromEnd($groupName, "s");
            return self::getGroup($groupName);
        }

        // Function "inXxxOrYyy": Check if the given level is in the group xxx or yyy
        if (Strings::startsWith($function, "in") && Strings::contains($function, "Or")) {
            $groupNames = Strings::removeFromStart($function, "in");
            $groupParts = Strings::split($groupNames, "Or");
            $groupX     = self::getGroup($groupParts[0]);
            $groupY     = self::getGroup($groupParts[1]);
            return in_array($level, $groupX) || in_array($level, $groupY);
        }

        // Function "inXxx(s)" or "isValidXxx": Check if the given level is in the group xxx
        if (Strings::startsWith($function, "in") || Strings::startsWith($function, "isValid")) {
            $groupName = Strings::removeFromStart($function, "in");
            $groupName = Strings::removeFromStart($function, "isValid");
            $groupName = Strings::removeFromEnd($groupName, "s");
            $group     = self::getGroup($groupName);
            return in_array($level, $group);
        }

        // Function "isXxxOrHigher": Check if the given level is equal or higher than xxx
        if (Strings::startsWith($function, "is") && Strings::endsWith($function, "OrHigher")) {
            $accessName  = Strings::removeFromStart($function, "is");
            $accessName  = Strings::removeFromEnd($accessName, "OrHigher");
            $accessLevel = self::getOne($accessName);
            return $level >= $accessLevel;
        }

        // Function "isXxxOrYyy": Check if the given level is equal to xxx or yyy
        if (Strings::startsWith($function, "is") && Strings::contains($function, "Or")) {
            $accessNames  = Strings::removeFromStart($function, "is");
            $accessParts  = Strings::split($accessNames, "Or");
            $accessLevelX = self::getOne($accessParts[0]);
            $accessLevelY = self::getOne($accessParts[1]);
            return $level == $accessLevelX || $level == $accessLevelY;
        }

        // Function "isXxx": Check if the given level is equal to xxx
        if (Strings::startsWith($function, "is")) {
            $accessName  = Strings::removeFromStart($function, "is");
            $accessLevel = self::getOne($accessName);
            return $level == $accessLevel;
        }

        // Function "xxx": Return the access level with that name
        return self::getOne($function);
    }
}
