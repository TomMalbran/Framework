<?php
namespace App\Utils;

use Framework\Utils\Strings;
use Framework\Utils\Utils;
use ReflectionClass;

/**
 * The Base Enum
 */
class Enum {

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
            self::$cache[$class] = (object)[
                "constants" => $reflection->getConstants(),
                "data"      => !empty($properties["data"]) ? $properties["data"] : [],
            ];
        }
        return self::$cache[$class];
    }

    /**
     * Returns the Data of the Enum
     * @return array
     */
    public static function getData() {
        $cache = self::load();
        return $cache->data;
    }

    /**
     * Returns all the Constants
     * @return array
     */
    public static function toArray() {
        $cache = self::load();
        return $cache->constants;
    }

    /**
     * Check if is valid enum value
     * @param mixed $value
     * @return boolean
     */
    public static function isValid($value) {
        $cache = self::load();
        if (!empty($cache->data)) {
            return in_array($value, array_keys($cache->data));
        }
        return in_array($value, $cache->constants);
    }



    /**
     * Returns a value from the Data
     * @param string $name
     * @param array  $arguments
     * @return mixed|null
     */
    public static function __callStatic($name, array $arguments) {
        $data = self::getData();
        if (empty($data)) {
            return null;
        }

        // Get the Key depending on the $name
        $value = $arguments[0];
        $key   = $name;
        if (Strings::startsWith($name, "get")) {
            $key    = Strings::replace($name, "get", "");
            $key[0] = Strings::toLowerCase($key[0]);
        }
        
        // Return the Value
        if (!empty($data[$value])) {
            if (is_array($data[$value])) {
                return $data[$value][$key];
            }
            if ($key == "name") {
                return $data[$value];
            }
            if ($key == "key") {
                return $value;
            }
        }
        return null;
    }

    /**
     * Returns the Data from the Data property
     * @param integer $value
     * @return object
     */
    public static function getOne($value) {
        $data = self::getData();
        if (!empty($data[$value])) {
            return (object)$data[$value];
        }
        return null;
    }

    /**
     * Creates a list for the templates
     * @return array
     */
    public static function getSelect() {
        $data = self::getData();
        if (empty($data)) {
            return [];
        }
        $values = array_values($data);
        if (is_array($values[0])) {
            return Utils::createSelect($data, "key", "name");
        }
        return Utils::createSelectFromMap($data);
    }
}
