<?php
namespace Framework\Utils;

use Framework\Utils\Strings;
use Framework\Utils\Utils;
use ReflectionClass;

/**
 * The Base AccessEnum
 */
class AccessEnum {

    /**
     * Store existing constants in a static cache per object
     * @var array
     */
    protected static $cache = [];



    /**
     * Loads the Data from the Cache
     * @return object
     */
    public static function load() {
        $class = get_called_class();
        if (empty(self::$cache[$class])) {
            $reflection = new ReflectionClass($class);
            $properties = $reflection->getStaticProperties();
            $data       = !empty($properties["data"]) ? $properties["data"] : [];
            $groups     = [];

            // Create the Groups
            foreach ($data as $key => $groupName) {
                if (empty($groups[$groupName])) {
                    $groups[$groupName] = [];
                }
                $groups[$groupName][] = $key;
            }

            // Store the Data
            self::$cache[$class] = (object)[
                "levels" => $reflection->getConstants(),
                "data"   => $data,
                "groups" => $groups,
            ];
        }
        return self::$cache[$class];
    }

    /**
     * Returns the Access Level from an Access Name
     * @param string $value
     * @return integer
     */
    public static function fromName($value) {
        $cache = self::load();
        if (isset($cache->levels[$value])) {
            return $cache->levels[$value];
        }
        return null;
    }

    /**
     * Returns the Access Levels inside the given group
     * @param string $value
     * @return integer[]
     */
    public static function getGroup($value) {
        $cache = self::load();
        if (isset($cache->groups[$value])) {
            return $cache->groups[$value];
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
     * @return mixed|null
     */
    public static function __callStatic($function, array $arguments) {
        $level = (int)$arguments[0];

        // Check the given level in the format "isValidXxx"
        if (Strings::startsWith($function, "isValid")) {
            $groupName = Strings::removeFromStart($function, "isValid");
            $groupName = Strings::toLowerCase($groupName);
            $group     = self::getGroup($groupName);
            return in_array($level, $group);
        }

        // Check the given level in the format "isXxxOrHigher"
        if (Strings::startsWith($function, "is") && Strings::endsWith($function, "OrHigher")) {
            $accessName  = Strings::removeFromStart($function, "is");
            $accessName  = Strings::removeFromEnd($accessName, "OrHigher");
            $accessLevel = self::fromName($accessName);
            return $level >= $accessLevel;
        }

        // Check the given level in the format "isXxx"
        if (Strings::startsWith($function, "is")) {
            $accessName  = Strings::removeFromStart($function, "is");
            $accessLevel = self::fromName($accessName);

            // If "Xxx" is an Access Name check the value
            if (!empty($accessLevel)) {
                return $level == $accessLevel;
            }
            // If "Xxx" is an Access Group check if the level is in the group
            $group = self::getGroup($accessName);
            return in_array($level, $group);
        }

        return null;
    }
}
