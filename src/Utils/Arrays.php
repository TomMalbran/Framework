<?php
namespace Framework\Utils;

use ArrayAccess;

/**
 * Several Array Utils
 */
class Arrays {

    /**
     * Returns true if the given value is an Array
     * @param mixed $value
     * @return boolean
     */
    public static function isArray(mixed $value): bool {
        return is_array($value);
    }

    /**
     * Returns true if the given value is an Array or ArrayAccess
     * @param mixed $value
     * @return boolean
     */
    public static function isArrayLike(mixed $value): bool {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Returns true if the given value is an Object
     * @param mixed $value
     * @return boolean
     */
    public static function isObject(mixed $value): bool {
        return is_object($value);
    }

    /**
     * Returns true if the given value is an list
     * @param mixed $array
     * @return boolean
     */
    public static function isList(mixed $array): bool {
        return self::isArray($array) && array_is_list($array);
    }

    /**
     * Returns true if the given value is a map
     * @param mixed $array
     * @return boolean
     */
    public static function isMap(mixed $array): bool {
        return self::isArray($array) && self::isArray(array_values($array)[0]);
    }



    /**
     * Returns the length of the given array
     * @param mixed $array
     * @return integer
     */
    public static function length(mixed $array): int {
        return self::isArray($array) ? count($array) : 0;
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
     * @param boolean $caseInsensitive Optional.
     * @return boolean
     */
    private static function isEqualContains(mixed $key, mixed $row, mixed $value, bool $caseInsensitive = true): bool {
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
        return self::isArray($array) ? $array : [ $array ];
    }

    /**
     * Converts an empty array into an object or returns the array
     * @param mixed[]|null $array Optional.
     * @return mixed
     */
    public static function toObject(?array $array = null): mixed {
        return !empty($array) ? $array : (object)[];
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
     * Removes the first value from the array
     * @param mixed[] $array
     * @return mixed[]
     */
    public static function removeFirst(array $array): array {
        array_shift($array);
        return $array;
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
     * Removes the duplicate entries from the given array
     * @param mixed[] $array
     * @return mixed[]
     */
    public static function removeDuplicates(array $array): array {
        $result = [];
        foreach ($array as $value) {
            if (!self::contains($result, $value)) {
                $result[] = $value;
            }
        }
        return $result;
    }



    /**
     * Merges the given Arrays
     * @param mixed[] ...$arrays
     * @return mixed[]
     */
    public static function merge(array ...$arrays): array {
        return array_merge(...$arrays);
    }

    /**
     * Adds the given element to the start of the Array
     * @param mixed[] $array
     * @param mixed   ...$elem
     * @return mixed[]
     */
    public static function addFirst(array $array, mixed ...$elem): array {
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
            if (self::isArray($value) && isset($result[$key]) && self::isArray($result[$key])) {
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
        } elseif (self::isList($array)) {
            usort($array, $callback);
        } elseif (self::isMap($array)) {
            uasort($array, $callback);
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
            if (!empty($value[$field]) && self::isArray($value[$field])) {
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
     * Returns the given array reversed
     * @param mixed[] $array
     * @return mixed[]
     */
    public static function reverse(array $array): array {
        return array_reverse($array);
    }



    /**
     * Creates a Map using the given Array
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
     * Creates a Map of Arrays using the given Array
     * @param mixed[]              $array
     * @param string               $key
     * @param string[]|string|null $value    Optional.
     * @param boolean              $useEmpty Optional.
     * @return mixed[]
     */
    public static function createMapArray(array $array, string $key, array|string $value = null, bool $useEmpty = false): array {
        $result = [];
        foreach ($array as $row) {
            if (empty($result[$row[$key]])) {
                $result[$row[$key]] = [];
            }
            $result[$row[$key]][] = !empty($value) ? self::getValue($row, $value, " - ", "", $useEmpty) : $row;
        }
        return $result;
    }

    /**
     * Creates an Array using the given Array
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
     * Creates an Sub-Array using the given Array
     * @param mixed[] $array
     * @param string  $idKey
     * @param mixed   $idValue
     * @param string  $key
     * @param boolean $caseInsensitive Optional.
     * @return mixed[]
     */
    public static function createSubArray(array $array, string $idKey, mixed $idValue, string $key, bool $caseInsensitive = true): array {
        $result = [];
        foreach ($array as $row) {
            if (Strings::isEqual($row[$idKey], $idValue, $caseInsensitive)) {
                $result[] = $row[$key];
            }
        }
        return $result;
    }

    /**
     * Returns only the requested Fields of the Map
     * @param array{} $array
     * @param string  ...$fields
     * @return array{}
     */
    public static function reduceMap(array $array, string ...$fields): array {
        $result = [];
        foreach ($fields as $field) {
            if (isset($array[$field])) {
                $result[$field] = $array[$field];
            }
        }
        return $result;
    }



    /**
     * Returns the first Value of the given array
     * @param mixed[] $array
     * @param string  $key   Optional.
     * @return mixed
     */
    public static function getFirst(array $array, string $key = ""): mixed {
        if (empty($array)) {
            return null;
        }
        $firstKey = array_key_first($array);
        $value    = $array[$firstKey];
        return !empty($key) ? self::getValue($value, $key) : $value;
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
     * Returns the last Value of the given array
     * @param mixed[] $array
     * @param string  $key   Optional.
     * @return mixed
     */
    public static function getLast(array $array, string $key = ""): mixed {
        if (empty($array)) {
            return null;
        }
        $lastKey = array_key_last($array);
        $value   = $array[$lastKey];
        return !empty($key) ? self::getValue($value, $key) : $value;
    }

    /**
     * Returns the index of the given needle
     * @param mixed[] $array
     * @param mixed   $needle
     * @param boolean $caseInsensitive
     * @return integer
     */
    public static function getIndex(array $array, mixed $needle, bool $caseInsensitive = false): int {
        foreach ($array as $index => $elem) {
            if (Strings::isEqual($elem, $needle, $caseInsensitive)) {
                return $index;
            }
        }
        return -1;
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
            if (self::isObject($elem)) {
                if ($elem->$idKey == $idValue) {
                    return $index;
                }
            } elseif (self::isArrayLike($elem)) {
                if ($elem[$idKey] == $idValue) {
                    return $index;
                }
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
            if (self::isObject($elem)) {
                if (!empty($elem->$idKey) && $elem->$idKey == $idValue) {
                    return $key ? $elem->$key : $elem;
                }
            } elseif (self::isArrayLike($elem)) {
                if (!empty($elem[$idKey]) && $elem[$idKey] == $idValue) {
                    return $key ? $elem[$key] : $elem;
                }
            }
        }
        return $key ? "" : [];
    }

    /**
     * Returns the Values at the given id with the given key
     * @param mixed[] $array
     * @param string  $idKey
     * @param mixed   $idValue
     * @param string  $key     Optional.
     * @param string  $glue    Optional.
     * @return mixed
     */
    public static function findValues(array $array, string $idKey, mixed $idValue, string $key = "", string $glue = ""): mixed {
        $result = [];
        foreach ($array as $elem) {
            if (self::isObject($elem)) {
                if (!empty($elem->$idKey) && $elem->$idKey == $idValue) {
                    $result[] = $key ? $elem->$key : $elem;
                }
            } elseif (self::isArrayLike($elem)) {
                if (!empty($elem[$idKey]) && $elem[$idKey] == $idValue) {
                    $result[] = $key ? $elem[$key] : $elem;
                }
            } else {
                $result[] = $elem;
            }
        }
        if (!empty($key) && !empty($glue)) {
            return implode($glue, $result);
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
     * @param ArrayAccess|array{} $array
     * @param string[]|string     $key
     * @param string              $glue     Optional.
     * @param string              $prefix   Optional.
     * @param boolean             $useEmpty Optional.
     * @param mixed|string        $default  Optional.
     * @return mixed
     */
    public static function getValue(ArrayAccess|array $array, array|string $key, string $glue = " - ", string $prefix = "", bool $useEmpty = false, mixed $default = ""): mixed {
        $result = $default;
        if (self::isArray($key)) {
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
     * Returns one value as an array
     * @param mixed  $array
     * @param string $key
     * @return array{}|mixed[]
     */
    public static function getValueArray(mixed $array, string $key): array {
        $value = self::getValue($array, $key);
        if (empty($value) || !self::isArray($value)) {
            return [];
        }
        return $value;
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

    /**
     * Returns the Field Value using the given Columns and Key
     * @param array{} $fields
     * @param array{} $columns
     * @param string  $key
     * @param boolean $splitResult Optional.
     * @return string[]|string
     */
    public static function getImportValue(array $fields, array $columns, string $key, bool $splitResult = false): array|string {
        $result = "";
        if (!isset($columns[$key]) || !isset($fields[$columns[$key]])) {
            $result = "";
        } elseif (!empty($fields[$columns[$key]]) || $fields[$columns[$key]] === "0") {
            $result = $fields[$columns[$key]];
        }
        return $splitResult ? Strings::split($result, ",", true, true) : $result;
    }
}
