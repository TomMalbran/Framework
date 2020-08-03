<?php
namespace Framework\Utils;

/**
 * Several Array Utils
 */
class Arrays {

    /**
     * Returns true if the given value is an array
     * @param mixed $array
     * @return boolean
     */
    public static function isArray($array): bool {
        return is_array($array);
    }

    /**
     * Returns true if the given value is a map
     * @param mixed $array
     * @return boolean
     */
    public static function isMap($array): bool {
        return is_array($array) && is_array(array_values($array)[0]);
    }


    
    /**
     * Returns the length of the given array
     * @param array|mixed $array
     * @return integer
     */
    public static function length($array): int {
        return is_array($array) ? count($array) : 0;
    }

    /**
     * Returns true if the array contains the needle
     * @param array|mixed $array
     * @param mixed       $needle
     * @return boolean
     */
    public static function contains($array, $needle): bool {
        return in_array($needle, self::toArray($array));
    }

    /**
     * Returns true if the array contains the needle as a key
     * @param array $array
     * @param mixed $needle
     * @return boolean
     */
    public static function containsKey(array $array, $needle): bool {
        return in_array($needle, array_keys($array));
    }
    


    /**
     * Converts a single value or an array into an array
     * @param array|mixed $array
     * @return array
     */
    public static function toArray($array): array {
        return is_array($array) ? $array : [ $array ];
    }

    /**
     * Converts an empty array into an object or returns the array
     * @param array $array Optional.
     * @return mixed
     */
    public static function toObject(array $array = null) {
        return !empty($array) ? $array : new \stdClass();
    }
    
    /**
     * Returns a random value from the array
     * @param array $array
     * @return mixed
     */
    public static function random(array $array) {
        return $array[array_rand($array)];
    }

    /**
     * Removes the empty entries from the given array
     * @param array $array
     * @return array
     */
    public static function removeEmpty(array $array): array {
        $result = [];
        foreach ($array as $value) {
            if (!empty($value)) {
                $result[] = $value;
            }
        }
        return $result;
    }
    
    /**
     * Returns an array with values in the Base
     * @param array $base
     * @param array $array
     * @return array
     */
    public static function subArray(array $base, array $array): array {
        $result = [];
        foreach ($array as $value) {
            if (in_array($value, $base)) {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Extends the first array replacing values from the second array
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function extend(array &$array1, array &$array2): array {
        $result = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = self::extend($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }



    /**
     * Creates a map using the given array
     * @param array           $array
     * @param string          $key
     * @param string|string[] $value Optional.
     * @return array
     */
    public static function createMap(array $array, string $key, $value = null): array {
        $result  = [];
        foreach ($array as $row) {
            $result[$row[$key]] = !empty($value) ? self::getValue($row, $value) : $row;
        }
        return $result;
    }
    
    /**
     * Creates an sub array using the given array
     * @param array           $array
     * @param string|string[] $value Optional.
     * @return array
     */
    public static function createArray(array $array, $value = null): array {
        $result = [];
        foreach ($array as $row) {
            $result[] = !empty($value) ? self::getValue($row, $value) : $row;
        }
        return $result;
    }
    
    /**
     * Creates a select using the given array
     * @param array           $array
     * @param string          $key
     * @param string|string[] $value
     * @param boolean         $useEmpty Optional.
     * @return array
     */
    public static function createSelect(array $array, string $key, $value, bool $useEmpty = false): array {
        $result = [];
        foreach ($array as $row) {
            $result[] = [
                "key"   => $row[$key],
                "value" => self::getValue($row, $value, " - ", "", $useEmpty),
            ];
        }
        return $result;
    }
    
    /**
     * Creates a select using the given array
     * @param array $array
     * @return array
     */
    public static function createSelectFromMap(array $array): array {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = [
                "key"   => $key,
                "value" => $value,
            ];
        }
        return $result;
    }


    
    /**
     * Returns the key adding the prefix or not
     * @param string $key
     * @param string $prefix Optional.
     * @return string
     */
    public static function getKey(string $key, string $prefix = ""): string {
        return !empty($prefix) ? $prefix . ucfirst($key) : $key;
    }

    /**
     * Returns one or multiple values as a string
     * @param mixed           $array
     * @param string|string[] $key
     * @param string          $glue     Optional.
     * @param string          $prefix   Optional.
     * @param boolean         $useEmpty Optional.
     * @return mixed
     */
    public static function getValue($array, $key, string $glue = " - ", string $prefix = "", bool $useEmpty = false) {
        $result = "";
        if (is_array($key)) {
            $values = [];
            foreach ($key as $id) {
                $fullKey = self::getKey($id, $prefix);
                if ($useEmpty && isset($array[$fullKey])) {
                    $values[] = $array[$fullKey];
                } elseif (!$useEmpty && !empty($array[$fullKey])) {
                    $values[] = $array[$fullKey];
                }
            }
            $result = implode($glue, $values);
        } else {
            $fullKey = self::getKey($key, $prefix);
            if ($useEmpty && isset($array[$fullKey])) {
                $result = $array[$fullKey];
            } elseif (!$useEmpty && !empty($array[$fullKey])) {
                $result = $array[$fullKey];
            }
        }
        return $result;
    }
}
