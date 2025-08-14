<?php
namespace Framework\Utils;

use Framework\Utils\Numbers;

/**
 * Several Array Utils
 */
class Arrays {

    /**
     * Returns true if the given value is an list
     * @param mixed $array
     * @return boolean
     */
    public static function isList(mixed $array): bool {
        return is_array($array) && array_is_list($array);
    }

    /**
     * Returns true if the given value is a dictionary
     * @param mixed $array
     * @return boolean
     */
    public static function isDict(mixed $array): bool {
        return is_array($array) && !array_is_list($array);
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
     * Converts a single value or an array into an array
     * @param mixed $array
     * @return mixed[]
     */
    public static function toArray(mixed $array): array {
        return is_array($array) ? $array : [ $array ];
    }

    /**
     * Converts an array into an object or returns the array
     * @param mixed[]|null $array Optional.
     * @return mixed
     */
    public static function toObject(?array $array = null): mixed {
        return !self::isEmpty($array) ? $array : (object)[];
    }

    /**
     * Converts an array into a list of integers
     * @param mixed   $array
     * @param string  $key          Optional.
     * @param boolean $withoutEmpty Optional.
     * @return integer[]
     */
    public static function toInts(mixed $array, string $key = "", bool $withoutEmpty = false): array {
        if (is_int($array)) {
            if ($withoutEmpty && $array === 0) {
                return [];
            }
            return [ $array ];
        }

        if (!is_array($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $row) {
            $value = self::getValue($row, $key);
            if (!Numbers::isValid($value, null)) {
                continue;
            }
            if ($withoutEmpty && self::isEmpty($value)) {
                continue;
            }
            $result[] = Numbers::toInt($value);
        }
        return $result;
    }

    /**
     * Converts an array into a list of strings
     * @param mixed   $array
     * @param string  $key          Optional.
     * @param boolean $withoutEmpty Optional.
     * @return string[]
     */
    public static function toStrings(mixed $array, string $key = "", bool $withoutEmpty = false): array {
        if (is_string($array)) {
            if ($withoutEmpty && $array === "") {
                return [];
            }
            return [ $array ];
        }

        if (!is_array($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $row) {
            $value = self::getValue($row, $key);
            if ($withoutEmpty && self::isEmpty($value)) {
                continue;
            }
            $result[] = Strings::toString($value);
        }
        return $result;
    }

    /**
     * Converts an array into a Map of string keys and values
     * @param mixed $array
     * @return array<string,string>
     */
    public static function toStringsMap(mixed $array): array {
        if (!is_array($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $key => $value) {
            $result[Strings::toString($key)] = Strings::toString($value);
        }
        return $result;
    }

    /**
     * Converts an array into a Map of int keys and string values
     * @param mixed $array
     * @return array<integer,string>
     */
    public static function toIntStringMap(mixed $array): array {
        if (!is_array($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $key => $value) {
            $result[Numbers::toInt($key)] = Strings::toString($value);
        }
        return $result;
    }

    /**
     * Converts an array into a Map of string keys and int values
     * @param mixed $array
     * @return array<string,integer>
     */
    public static function toStringIntMap(mixed $array): array {
        if (!is_array($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $key => $value) {
            $result[Strings::toString($key)] = Numbers::toInt($value);
        }
        return $result;
    }

    /**
     * Converts an array into a Map of string keys and mixed values
     * @param mixed $array
     * @return array<string,mixed>
     */
    public static function toStringMixedMap(mixed $array): array {
        if (!is_array($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $key => $value) {
            $result[Strings::toString($key)] = $value;
        }
        return $result;
    }



    /**
     * Returns true if the given array is empty
     * @param mixed               $array
     * @param string|integer|null $key   Optional.
     * @return boolean
     */
    public static function isEmpty(mixed $array, string|int|null $key = null): bool {
        if ($key !== null && is_array($array)) {
            // @phpstan-ignore empty.notAllowed
            return empty($array[$key]);
        }
        // @phpstan-ignore empty.notAllowed
        return empty($array);
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
     * Returns the max value of the given array
     * @param mixed $array
     * @return integer
     */
    public static function max(mixed $array): int {
        if (self::isEmpty($array) || !is_array($array)) {
            return 0;
        }

        $length = self::length($array);
        if ($length > 1) {
            return Numbers::toInt(max(...$array));
        }
        return Numbers::toInt($array[0]);
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

        if (is_array($needle)) {
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
        if (is_object($row)) {
            return isset($row->$key) && Strings::isEqual($row->$key, $value, $caseInsensitive);
        }
        if (is_array($row)) {
            return isset($row[$key]) && Strings::isEqual($row[$key], $value, $caseInsensitive);
        }
        return false;
    }

    /**
     * Returns true if the array contains the needle as a key
     * @param mixed[] $array
     * @param mixed   $needle
     * @return boolean
     */
    public static function containsKey(array $array, mixed $needle): bool {
        return in_array($needle, array_keys($array), true);
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
            if (is_array($value) && $key !== "") {
                if (!isset($value[$key]) || !self::contains($other, $value[$key], $key)) {
                    return false;
                }
            } elseif ($value !== $other[$index]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns true if the arrays are Equal with the given keys
     * @param array<string,mixed> $array
     * @param array<string,mixed> $other
     * @param string[]            $keys
     * @return boolean
     */
    public static function isEqualWithKeys(array $array, array $other, array $keys): bool {
        foreach ($keys as $key) {
            if (!isset($array[$key]) || !isset($other[$key]) || $array[$key] !== $other[$key]) {
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
                if ($mine === $yours) {
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
            if (is_array($row) && (!isset($row[$checkKey]) || !self::contains($other, $row[$checkKey], $checkKey))) {
                if ($getKey !== null) {
                    $result[] = $row[$getKey];
                } else {
                    $result[] = $row;
                }
            }
        }
        return $result;
    }



    /**
     * Returns a random value from the array
     * @template TValue
     * @param TValue[] $array
     * @return TValue
     */
    public static function random(array $array): mixed {
        return $array[array_rand($array)];
    }

    /**
     * Removes the First value from the array
     * @template TValue
     * @param TValue[] $array
     * @return TValue[]
     */
    public static function removeFirst(array $array): array {
        array_shift($array);
        return $array;
    }

    /**
     * Removes the Last value from the array
     * @template TValue
     * @param TValue[] $array
     * @return TValue[]
     */
    public static function removeLast(array $array): array {
        array_pop($array);
        return $array;
    }

    /**
     * Removes the given value from the array
     * @template TValue
     * @param TValue[]       $array
     * @param string|integer $key
     * @param string|integer $idKey Optional.
     * @return TValue[]
     */
    public static function removeValue(array $array, string|int $key, string|int $idKey = ""): array {
        $result = [];
        foreach ($array as $elem) {
            $shouldAdd = false;
            if (is_object($elem)) {
                $shouldAdd = isset($elem->$idKey) && $elem->$idKey !== $key;
            } elseif (is_array($elem)) {
                $shouldAdd = isset($elem[$idKey]) && $elem[$idKey] !== $key;
            } else {
                $shouldAdd = $elem !== $key;
            }
            if ($shouldAdd) {
                $result[] = $elem;
            }
        }
        return $result;
    }

    /**
     * Removes the empty entries from the given array
     * @template TValue
     * @param TValue[] $array
     * @return TValue[]
     */
    public static function removeEmpty(array $array): array {
        $result = [];
        foreach ($array as $value) {
            if (!self::isEmpty($value)) {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Removes the duplicate entries from the given array
     * @template TValue
     * @param TValue[] $array
     * @return TValue[]
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
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey,TValue> ...$arrays
     * @return array<TKey,TValue>
     */
    public static function merge(array ...$arrays): array {
        return array_merge(...$arrays);
    }

    /**
     * Adds the given element to the start of the Array
     * @template TValue
     * @phpstan-param TValue ...$elem
     *
     * @param TValue[] $array
     * @param mixed    ...$elem
     * @return TValue[]
     */
    public static function addFirst(array $array, mixed ...$elem): array {
        array_unshift($array, ...$elem);
        return $array;
    }

    /**
     * Adds the given element at the given position
     * @template TValue
     * @phpstan-param TValue ...$elem
     *
     * @param TValue[] $array
     * @param integer  $position
     * @param mixed    ...$elem
     * @return TValue[]
     */
    public static function addAt(array $array, int $position, mixed ...$elem): array {
        array_splice($array, $position, 0, $elem);
        return $array;
    }

    /**
     * Slices an Array from the index the amount of items
     * @template TValue
     * @param TValue[]     $array
     * @param integer      $from
     * @param integer|null $amount Optional.
     * @return TValue[]
     */
    public static function slice(array $array, int $from, ?int $amount = null): array {
        return array_slice($array, $from, $amount);
    }

    /**
     * Paginates an Array from the page to the amount of items
     * @template TValue
     * @param TValue[] $array
     * @param integer  $page
     * @param integer  $amount
     * @return TValue[]
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
            if (in_array($value, $base, true)) {
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
     * @template TValue
     * @param TValue[]      $array
     * @param callable|null $callback Optional.
     * @return TValue[]
     */
    public static function sort(array $array, ?callable $callback = null): array {
        if ($callback === null) {
            sort($array);
        } elseif (self::isList($array)) {
            usort($array, $callback);
        } else {
            uasort($array, $callback);
        }
        return $array;
    }

    /**
     * Sorts the arrays at the given key of the given array using the given callback
     * @template TValue
     * @param TValue[] $array
     * @param string   $field
     * @param callable $callback
     * @return TValue[]
     */
    public static function sortArray(array &$array, string $field, callable $callback): array {
        foreach ($array as $value) {
            if (is_array($value) && isset($value[$field]) && is_array($value[$field])) {
                usort($value[$field], $callback);
            }
        }
        return $array;
    }

    /**
     * Returns the given array reversed
     * @template TValue
     * @param TValue[] $array
     * @return TValue[]
     */
    public static function reverse(array $array): array {
        return array_reverse($array);
    }

    /**
     * Applies the given callback to the elements of the given array
     * @param mixed[]  $array
     * @param callable $callback
     * @return mixed[]
     */
    public static function map(array $array, callable $callback): array {
        return array_map($callback, $array);
    }



    /**
     * Returns the sum of the elements of the given array
     * @param mixed[]     $array
     * @param string|null $key   Optional.
     * @return integer|float
     */
    public static function sum(array $array, ?string $key = null): int|float {
        $result = 0;
        foreach ($array as $value) {
            if (is_array($value)) {
                if (isset($value[$key])) {
                    $result += Numbers::toIntOrFloat($value[$key]);
                }
            } else {
                $result += Numbers::toIntOrFloat($value);
            }
        }
        return $result;
    }

    /**
     * Returns the average of the elements of the given array
     * @param mixed[]     $array
     * @param integer     $decimals Optional.
     * @param string|null $key      Optional.
     * @return float
     */
    public static function average(array $array, int $decimals = 0, ?string $key = null): float {
        $total = self::length($array);
        if ($total === 0) {
            return 0;
        }

        $sum = self::sum($array, $key);
        return Numbers::divide($sum, $total, $decimals);
    }



    /**
     * Creates a Map using the given Array
     * @template TValue
     * @param TValue[] $array
     * @param string   $key
     * @return array<string|integer,TValue>
     */
    public static function createMap(array $array, string $key): array {
        $result = [];
        foreach ($array as $row) {
            $keyValue = self::getOneValue($row, $key);
            $result[$keyValue] = $row;
        }
        return $result;
    }

    /**
     * Creates an Array using the given Array
     * @param mixed[]              $array
     * @param string[]|string|null $key       Optional.
     * @param boolean              $skipEmpty Optional.
     * @param boolean              $distinct  Optional.
     * @return mixed[]
     */
    public static function createArray(array $array, array|string|null $key = null, bool $skipEmpty = false, bool $distinct = false): array {
        $result = [];
        foreach ($array as $row) {
            $elem = self::getValue($row, $key ?? "");
            if (($distinct && in_array($elem, $result, true)) || ($skipEmpty && self::isEmpty($key))) {
                continue;
            }
            $result[] = $elem;
        }
        return $result;
    }



    /**
     * Returns the first Value of the given array
     * @template TValue
     * @param TValue[] $array
     * @param string   $key   Optional.
     * @return TValue|null
     */
    public static function getFirst(array $array, string $key = ""): mixed {
        if (self::isEmpty($array)) {
            return null;
        }
        $firstKey = array_key_first($array);
        $value    = $array[$firstKey];
        return self::getValue($value, $key);
    }

    /**
     * Returns the first Key of the given array
     * @param mixed[] $array
     * @return string|integer|mixed|null
     */
    public static function getFirstKey(array $array): mixed {
        $keys = array_keys($array);
        if (isset($keys[0])) {
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
        if (self::isEmpty($array)) {
            return null;
        }
        $lastKey = array_key_last($array);
        $value   = $array[$lastKey];
        return self::getValue($value, $key);
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
     * Returns true if there is an item with the given id key with the given is value
     * @param mixed[] $array
     * @param string  $idKey
     * @param mixed   $idValue
     * @return boolean
     */
    public static function hasValue(array $array, string $idKey, mixed $idValue): bool {
        return self::findIndex($array, $idKey, $idValue) !== -1;
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
            if (is_object($elem)) {
                if ($elem->$idKey === $idValue) {
                    return $index;
                }
            } elseif (is_array($elem)) {
                if ($elem[$idKey] === $idValue) {
                    return $index;
                }
            }
        }
        return -1;
    }

    /**
     * Returns the Value at the given id with the given key
     * @template TValue
     * @param TValue[] $array
     * @param string   $idKey
     * @param mixed    $idValue
     * @return TValue|null
     */
    public static function findValue(array $array, string $idKey, mixed $idValue) {
        foreach ($array as $elem) {
            if (is_object($elem)) {
                if (isset($elem->$idKey) && $elem->$idKey === $idValue) {
                    return $elem;
                }
            } elseif (is_array($elem)) {
                if (isset($elem[$idKey]) && $elem[$idKey] === $idValue) {
                    return $elem;
                }
            }
        }
        return null;
    }

    /**
     * Returns the Values at the given id with the given key
     * @template TValue
     * @param TValue[] $array
     * @param string   $idKey
     * @param mixed    $idValue
     * @return TValue[]
     */
    public static function findValues(array $array, string $idKey, mixed $idValue): array {
        $result = [];
        foreach ($array as $elem) {
            if (is_object($elem)) {
                if (isset($elem->$idKey) && $elem->$idKey === $idValue) {
                    $result[] = $elem;
                }
            } elseif (is_array($elem)) {
                if (isset($elem[$idKey]) && $elem[$idKey] === $idValue) {
                    $result[] = $elem;
                }
            }
        }
        // @phpstan-ignore return.type
        return $result;
    }



    /**
     * Returns the key adding the prefix or not
     * @param string $key
     * @param string $prefix Optional.
     * @return string
     */
    public static function getKey(string $key, string $prefix = ""): string {
        return $prefix !== "" ? $prefix . ucfirst($key) : $key;
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
    public static function getValue(
        mixed $array,
        array|string $key,
        string $glue = " - ",
        string $prefix = "",
        bool $useEmpty = false,
        mixed $default = "",
    ): mixed {
        if (self::isEmpty($key)) {
            return $array;
        }

        $result = $default;
        if (is_array($key)) {
            $values = [];
            foreach ($key as $id) {
                $fullKey = self::getKey($id, $prefix);
                $value   = self::getOneValue($array, $fullKey, $useEmpty);
                if ($value !== null) {
                    $values[] = $value;
                }
            }
            $result = implode($glue, $values);
        } else {
            $fullKey = self::getKey($key, $prefix);
            $value   = self::getOneValue($array, $fullKey, $useEmpty);
            if ($value !== null) {
                $result = $value;
            }
        }
        return $result;
    }

    /**
     * Returns one value
     * @param mixed      $array
     * @param string     $key
     * @param boolean    $useEmpty Optional.
     * @param mixed|null $default  Optional.
     * @return mixed
     */
    public static function getOneValue(mixed $array, string $key, bool $useEmpty = false, mixed $default = null): mixed {
        if (is_object($array) && property_exists($array, $key)) {
            if ($useEmpty || !self::isEmpty($array->$key)) {
                return $array->$key;
            }
        } elseif (is_array($array) && array_key_exists($key, $array)) {
            if ($useEmpty || !self::isEmpty($array, $key)) {
                return $array[$key];
            }
        }
        return $default;
    }
}
