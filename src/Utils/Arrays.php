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
    public static function isArray(mixed $array): bool {
        return is_array($array);
    }

    /**
     * Returns true if the given value is a map
     * @param mixed $array
     * @return boolean
     */
    public static function isMap(mixed $array): bool {
        return is_array($array) && is_array(array_values($array)[0]);
    }



    /**
     * Returns the length of the given array
     * @param mixed $array
     * @return integer
     */
    public static function length(mixed $array): int {
        return is_array($array) ? count($array) : 0;
    }

    /**
     * Returns true if the array contains the needle
     * @param mixed      $array
     * @param mixed      $needle
     * @param mixed|null $key             Optional.
     * @param boolean    $caseInsensitive Optional.
     * @param boolean    $atLeastOne      Optional.
     * @return boolean
     */
    public static function contains(mixed $array, mixed $needle, mixed $key = null, bool $caseInsensitive = true, bool $atLeastOne = false): bool {
        $array = self::toArray($array);

        if (self::isArray($needle)) {
            $count = 0;
            foreach ($array as $row) {
                foreach ($needle as $value) {
                    if (self::isEqualContains($key, $row, $value, $caseInsensitive)) {
                        $count++;
                        break;
                    }
                }
            }
            if ($atLeastOne) {
                return $count > 0;
            }
            return $count === count($needle);
        }

        foreach ($array as $row) {
            if (self::isEqualContains($key, $row, $needle, $caseInsensitive)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does the Contains compare
     * @param mixed   $key
     * @param mixed   $row
     * @param mixed   $value
     * @param boolean $caseInsensitive
     * @return boolean
     */
    private static function isEqualContains(mixed $key, mixed $row, mixed $value, bool $caseInsensitive): bool {
        if ($key === null) {
            return Strings::isEqual($row, $value, $caseInsensitive);
        }
        return isset($row[$key]) && Strings::isEqual($row[$key], $value, $caseInsensitive);
    }

    /**
     * Returns true if the array contains the needle as a key
     * @param mixed[] $array
     * @param mixed   $needle
     * @return boolean
     */
    public static function containsKey(array $array, mixed $needle): bool {
        return in_array($needle, array_keys($array));
    }

    /**
     * Returns true if the arrays are Equal
     * @param mixed[] $array
     * @param mixed[] $other
     * @param string  $key   Optional.
     * @return boolean
     */
    public static function isEqual(array $array, array $other, string $key = ""): bool {
        if (self::length($array) !== self::length($other)) {
            return false;
        }
        foreach ($array as $index => $value) {
            if (!empty($key)) {
                if (!isset($value[$key]) || !self::contains($other, $value[$key], $key)) {
                    return false;
                }
            } elseif ($value != $other[$index]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns true if the arrays intersect
     * @param mixed[] $array
     * @param mixed[] $other
     * @return boolean
     */
    public static function intersects(array $array, array $other): bool {
        foreach ($array as $mine) {
            foreach ($other as $yours) {
                if ($mine == $yours) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns the elements from the array that are not in the other array
     * @param mixed[]     $array
     * @param mixed[]     $other
     * @param string      $checkKey
     * @param string|null $getKey   Optional.
     * @return mixed[]
     */
    public static function getDiff(array $array, array $other, string $checkKey, ?string $getKey = null): array {
        $result = [];
        foreach ($array as $row) {
            if (!isset($row[$checkKey]) || !self::contains($other, $row[$checkKey], $checkKey)) {
                if ($getKey != null) {
                    $result[] = $row[$getKey];
                } else {
                    $result[] = $row;
                }
            }
        }
        return $result;
    }



    /**
     * Converts a single value or an array into an array
     * @param mixed $array
     * @return mixed[]
     */
    public static function toArray(mixed $array): array {
        return is_array($array) ? $array : [ $array ];
    }

    /**
     * Converts an empty array into an object or returns the array
     * @param mixed[]|null $array Optional.
     * @return mixed
     */
    public static function toObject(?array $array = null): mixed {
        return !empty($array) ? $array : new \stdClass();
    }

    /**
     * Returns a random value from the array
     * @param mixed[] $array
     * @return mixed
     */
    public static function random(array $array): mixed {
        return $array[array_rand($array)];
    }

    /**
     * Removes the given value from the array
     * @param mixed[] $array
     * @param mixed   $key
     * @return mixed[]
     */
    public static function removeValue(array $array, mixed $key): array {
        $result = [];
        foreach ($array as $value) {
            if ($value != $key) {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Removes the empty entries from the given array
     * @param mixed[] $array
     * @return mixed[]
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
     * Adds the given element to the start of the Array
     * @param mixed[] $array
     * @param mixed   ...$elem
     * @return mixed[]
     */
    public static function unshift(array $array, mixed ...$elem): array {
        array_unshift($array, ...$elem);
        return $array;
    }

    /**
     * Slices an Array from the index the amount of items
     * @param mixed[]      $array
     * @param integer      $from
     * @param integer|null $amount Optional.
     * @return mixed[]
     */
    public static function slice(array $array, int $from, ?int $amount = null): array {
        return array_slice($array, $from, $amount);
    }

    /**
     * Paginates an Array from the page to the amount of items
     * @param mixed[] $array
     * @param integer $page
     * @param integer $amount
     * @return mixed[]
     */
    public static function paginate(array $array, int $page, int $amount): array {
        $from = $page * $amount;
        return array_slice($array, $from, $amount - 1);
    }

    /**
     * Returns an array with values in the Base
     * @param mixed[] $base
     * @param mixed[] $array
     * @return mixed[]
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
     * @param mixed[] $array1
     * @param mixed[] $array2
     * @return mixed[]
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
     * Sorts an array using the given callback
     * @param mixed[]       $array
     * @param callable|null $callback Optional.
     * @return mixed[]
     */
    public static function sort(array &$array, ?callable $callback = null): array {
        if (empty($callback)) {
            sort($array);
        } else {
            usort($array, $callback);
        }
        return $array;
    }

    /**
     * Sorts the arrays at the given key of the given array using the given callback
     * @param mixed[]  $array
     * @param string   $field
     * @param callable $callback
     * @return mixed[]
     */
    public static function sortArray(array &$array, string $field, callable $callback): array {
        foreach ($array as $value) {
            if (!empty($value[$field]) && is_array($value[$field])) {
                usort($value[$field], $callback);
            }
        }
        return $array;
    }

    /**
     * Returns the sum of the elements of the given array
     * @param mixed[]     $array
     * @param string|null $key   Optional.
     * @return mixed|integer|float
     */
    public static function sum(array $array, ?string $key = null): mixed {
        $result = 0;
        foreach ($array as $value) {
            if (!empty($key)) {
                $result += $value[$key];
            } else {
                $result += $value;
            }
        }
        return $result;
    }



    /**
     * Creates a map using the given array
     * @param mixed[]              $array
     * @param string               $key
     * @param string[]|string|null $value    Optional.
     * @param boolean              $useEmpty Optional.
     * @return mixed[]
     */
    public static function createMap(array $array, string $key, array|string $value = null, bool $useEmpty = false): array {
        $result = [];
        foreach ($array as $row) {
            $result[$row[$key]] = !empty($value) ? self::getValue($row, $value, " - ", "", $useEmpty) : $row;
        }
        return $result;
    }

    /**
     * Creates an sub array using the given array
     * @param mixed[]              $array
     * @param string[]|string|null $value     Optional.
     * @param boolean              $skipEmpty Optional.
     * @param boolean              $distinct  Optional.
     * @return mixed[]
     */
    public static function createArray(array $array, array|string $value = null, bool $skipEmpty = false, bool $distinct = false): array {
        $result = [];
        foreach ($array as $row) {
            $elem = !empty($value) ? self::getValue($row, $value) : $row;
            if (($distinct && in_array($elem, $result)) || ($skipEmpty && empty($value))) {
                continue;
            }
            $result[] = $elem;
        }
        return $result;
    }

    /**
     * Creates a select using the given array
     * @param mixed[]         $array
     * @param string          $keyName
     * @param string[]|string $valName
     * @param boolean         $useEmpty Optional.
     * @param string|null     $extra    Optional.
     * @param boolean         $distinct Optional.
     * @return mixed[]
     */
    public static function createSelect(array $array, string $keyName, array|string $valName, bool $useEmpty = false, ?string $extra = null, bool $distinct = false): array {
        $result = [];
        $keys   = [];

        foreach ($array as $row) {
            $key   = $row[$keyName];
            $value = self::getValue($row, $valName, " - ", "", $useEmpty);
            if (($distinct && in_array($key, $keys)) || (!$useEmpty && empty($value))) {
                continue;
            }
            $fields = [ "key" => $key, "value" => $value ];
            if ($extra) {
                $fields[$extra] = self::getValue($row, $extra);
            }
            $result[] = $fields;
            $keys[]   = $key;
        }
        return $result;
    }

    /**
     * Creates a select using the given array
     * @param mixed[] $array
     * @return mixed[]
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
     * Returns the first Key of the given array
     * @param mixed[] $array
     * @return mixed
     */
    public static function getFirst(array $array): mixed {
        return !empty($array) ? $array[array_keys($array)[0]] : null;
    }

    /**
     * Returns the first Key of the given array
     * @param mixed[] $array
     * @return string|integer|mixed|null
     */
    public static function getFirstKey(array $array): mixed {
        $keys = array_keys($array);
        if (!empty($keys)) {
            return $keys[0];
        }
        return null;
    }

    /**
     * Returns the index at the given id key with the given is value
     * @param mixed[] $array
     * @param string  $idKey
     * @param mixed   $idValue
     * @return string|integer|mixed
     */
    public static function findIndex(array $array, string $idKey, mixed $idValue): mixed {
        foreach ($array as $index => $elem) {
            if ($elem[$idKey] == $idValue) {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Returns the Value at the given id with the given key
     * @param mixed[] $array
     * @param string  $idKey
     * @param mixed   $idValue
     * @param string  $key     Optional.
     * @return mixed
     */
    public static function findValue(array $array, string $idKey, mixed $idValue, string $key = ""): mixed {
        foreach ($array as $elem) {
            if ($elem[$idKey] == $idValue) {
                return $key ? $elem[$key] : $elem;
            }
        }
        return $key ? "" : [];
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
     * @param string[]|string $key
     * @param string          $glue     Optional.
     * @param string          $prefix   Optional.
     * @param boolean         $useEmpty Optional.
     * @param mixed|string    $default  Optional.
     * @return mixed
     */
    public static function getValue(mixed $array, array|string $key, string $glue = " - ", string $prefix = "", bool $useEmpty = false, mixed $default = ""): mixed {
        $result = $default;
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

    /**
     * Returns the first Value that is not empty in the given keys
     * @param mixed      $array
     * @param string[]   $keys
     * @param mixed|null $default Optional.
     * @return mixed
     */
    public static function getAnyValue(mixed $array, array $keys, mixed $default = null): mixed {
        foreach ($keys as $key) {
            if (!empty($array[$key])) {
                return $array[$key];
            }
        }
        return $default;
    }
}
