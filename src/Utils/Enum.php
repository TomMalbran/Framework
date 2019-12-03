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
                "isArray"    => $type == "array",
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
     * Creates a Select for the Enum
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
            // Function "isXxx": Check if the given value is equal to xxx
            if (Strings::startsWith($function, "is")) {
                $key = Strings::removeFromStart($function, "is");
                if (!empty($cache->constants[$key])) {
                    return $value == $cache->constants[$key];
                }
                return false;
            }

            // Function "xxx": Return the value of xxx
            return self::getOne($function);
        }


        // For ARRAY
        if ($cache->isArray) {
            // Function "getKey" or "getIndex"
            // Return the index where the value is equalto the given one
            if ($function == "getKey" || $function == "getIndex") {
                foreach ($cache->data as $index => $name) {
                    if (Strings::isEqual($name, $value)) {
                        return $index;
                    }
                }
                return "";
            }
            
            // Function "getName" or "getValue"
            // Return the value of the data at the given index
            if ($function == "getName" || $function == "getValue") {
                return !empty($cache->data[$value]) ? $cache->data[$value] : "";
            }

            // Function "isXxx": Check if the data is equal to xxx
            if (Strings::startsWith($function, "is")) {
                $key = Strings::removeFromStart($function, "is");
                if (!empty($cache->constants[$key])) {
                    return $value == $cache->constants[$key];
                }
                return false;
            }

            // Function "inXxx(s)": Check if the data at the given index is equal to xxx
            if (Strings::startsWith($function, "in")) {
                if (!empty($cache->data[$value])) {
                    $key = Strings::removeFromStart($function, "in");
                    $key = Strings::removeFromEnd($function, "s");
                    return Strings::isEqual($cache->data[$value], $key);
                }
                return false;
            }
        }


        // For MAP
        if ($cache->isMap) {
            // Function "fromXxx"
            // Return the index where the value is the name
            if (Strings::startsWith($function, "from")) {
                $key = Strings::removeFromStart($function, "from");
                foreach ($cache->data as $index => $row) {
                    if (isset($row[$key]) && Strings::isEqual($row[$key], $value)) {
                        return $index;
                    }
                }
                return 0;
            }

            // Get the value at the given key depending on the function
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
