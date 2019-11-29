<?php
namespace Framework\Utils;

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
            $type       = "constant";
            $data       = [];

            if (!empty($properties["data"])) {
                $data = $properties["data"];
                $type = is_array(array_values($data)[0]) ? "map" : "array";
            }
            self::$cache[$class] = (object)[
                "constants"  => $reflection->getConstants(),
                "data"       => $data,
                "type"       => $type,
                "isConstant" => $type == "constant",
                "isArray"    => $type == "map",
                "isMap"      => $type == "map",
            ];
        }
        return self::$cache[$class];
    }



    /**
     * Check if is valid enum value
     * @param mixed $value
     * @return boolean
     */
    public static function isValid($value) {
        $cache = self::load();
        if ($cache->isConstant) {
            return in_array($value, array_values($cache->constants));
        }
        return in_array($value, array_keys($cache->data));
    }

    /**
     * Returns a single Value
     * @param mixed $value
     * @return mixed
     */
    public static function getOne($value) {
        $cache = self::load();
        if ($cache->isConstant) {
            return $cache->constants[$value];
        }
        return (object)$cache->data[$value];
    }

    /**
     * Creates a list for the templates
     * @return array
     */
    public static function getSelect() {
        $cache = self::load();
        if ($cache->isConstant) {
            return Utils::createSelectFromMap($cache->constants);
        }
        if ($cache->isArray) {
            return Utils::createSelectFromMap($cache->data);
        }
        if ($cache->isMap) {
            return Utils::createSelect($cache->data, "key", "name");
        }
    }



    /**
     * Returns a value from the Data
     * @param string $function
     * @param array  $arguments
     * @return mixed|null
     */
    public static function __callStatic($function, array $arguments) {
        $cache = self::load();
        $value = $arguments[0];

        // For CONSTANT
        if ($cache->isConstant) {
            if (Strings::startsWith($function, "is")) {
                $key = Strings::removeFromStart($function, "is");
                return $key == $value;
            }
            return null;
        }

        // For ARRAY
        if ($cache->isArray) {
            // If the name is the "getKey" or "getIndex" return the key of the data
            if ($function == "getKey" || $function == "getIndex") {
                foreach ($cache->data as $key => $name) {
                    if (Strings::isEqual($name, $value)) {
                        return $index;
                    }
                }
                return "";
            }
            // If the name is the "getName" or "getValue" return the value of the data
            if ($function == "getName" || $function == "getValue") {
                return !empty($cache->data[$value]) ? $cache->data[$value] : "";
            }
        }

        // For MAP
        if ($cache->isMap) {
            // Get the Key depending on the $name
            $key = $function;
            if (Strings::startsWith($function, "get")) {
                $key    = Strings::removeFromStart($function, "get", "");
                $key[0] = Strings::toLowerCase($key[0]);
            }
            
            // If the values of each element is an array try to get the "key" there
            if (!empty($cache->data[$value])) {
                return $cache->data[$value][$key];
            }
        }

        return null;
    }
}
