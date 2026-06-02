<?php
namespace Framework\Utils;

use Framework\Enum\Enum;
use Framework\Date\Date;
use Framework\Utils\JSON;

use IteratorAggregate;
use Countable;
use Traversable;
use JsonSerializable;

/**
 * A Dictionary wrapper
 * @implements IteratorAggregate<string,Dictionary>
 */
class Dictionary implements Countable, IteratorAggregate, JsonSerializable {

    /** @var array<int|string,mixed> */
    private array $data;


    /**
     * Creates a new Dictionary instance
     * @param mixed|null $input Optional.
     */
    public function __construct(mixed $input = null) {
        if ($input instanceof self) {
            $this->data = $input->data;
        } elseif (is_array($input)) {
            $this->data = $input;
        } elseif (is_object($input)) {
            $this->data = (array)$input;
        } elseif (JSON::isValid($input)) {
            $this->data = JSON::decodeAsArray($input);
        } elseif (is_string($input)) {
            $this->data = Strings::split($input, ",", trim: true, skipEmpty: true);
        } else {
            $this->data = [];
        }
    }



    /**
     * Returns true if the data is empty
     * @return bool
     */
    public function isEmpty(): bool {
        return count($this->data) === 0;
    }

    /**
     * Returns true if the data is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return count($this->data) !== 0;
    }

    /**
     * Returns the total number of elements in the data
     * @return int
     */
    public function getTotal(): int {
        return count($this->data);
    }



    /**
     * Returns true if the data is equal to another Dictionary
     * @param Dictionary $dict
     * @return bool
     */
    public function isEqual(Dictionary $dict): bool {
        return Arrays::isEqualJSON($this->data, $dict->data);
    }

    /**
     * Returns true if the data is not equal to another Dictionary
     * @param Dictionary $dict
     * @return bool
     */
    public function isNotEqual(Dictionary $dict): bool {
        return !$this->isEqual($dict);
    }

    /**
     * Returns true if the data is a list
     * @param string $key Optional.
     * @return bool
     */
    public function isList(string $key = ""): bool {
        if ($this->isEmpty()) {
            return false;
        }
        if ($key !== "") {
            return isset($this->data[$key]) && Arrays::isList($this->data[$key]);
        }
        return Arrays::isList($this->data);
    }

    /**
     * Returns true if the data is a list of arrays or objects
     * @param string $key Optional.
     * @return bool
     */
    public function isArrayList(string $key = ""): bool {
        if ($this->isEmpty()) {
            return false;
        }
        if ($key !== "") {
            return isset($this->data[$key]) && Arrays::isArrayList($this->data[$key]);
        }
        return Arrays::isArrayList($this->data);
    }

    /**
     * Returns true if the key exits in the data
     * @param Enum|int|string $key
     * @return bool
     */
    public function has(Enum|int|string $key): bool {
        $key = Strings::toString($key);
        return isset($this->data[$key]);
    }

    /**
     * Returns true if the key exits and has a value in the data
     * @param Enum|int|string $key
     * @return bool
     */
    public function hasValue(Enum|int|string $key): bool {
        $key = Strings::toString($key);
        return !Arrays::isEmpty($this->data, $key);
    }

    /**
     * Returns true if the key exits in the data or in the list
     * @param Enum|int|string $needle
     * @param int|string|null $key    Optional.
     * @return bool
     */
    public function contains(Enum|int|string $needle, int|string|null $key = null): bool {
        $needle = Strings::toString($needle);
        if ($this->isList()) {
            return Arrays::contains($this->data, $needle, $key);
        }
        return $this->has($needle);
    }

    /**
     * Returns true if the key exits in the data or in the list as an Integer
     * @param int $key
     * @return bool
     */
    public function containsInt(int $key): bool {
        if ($this->isList()) {
            return Arrays::contains($this->toInts(), $key);
        }
        return $this->has($key);
    }



    /**
     * Merges another Dictionary into this one
     * @param Dictionary $dict
     * @return Dictionary
     */
    public function merge(Dictionary $dict): Dictionary {
        $this->data = Arrays::merge($this->data, $dict->data);
        return $this;
    }

    /**
     * Adds an array to the data if the data is a list
     * @param mixed $value
     * @return Dictionary
     */
    public function push(mixed $value): Dictionary {
        if (Arrays::isList($this->data)) {
            if ($value instanceof self) {
                $this->data[] = $value->toArray();
            } else {
                $this->data[] = $value;
            }
        }
        return $this;
    }

    /**
     * Sets the value of the given key
     * @param Enum|int|string $key
     * @param mixed           $value
     * @return Dictionary
     */
    public function set(Enum|int|string $key, mixed $value): Dictionary {
        $key = Strings::toString($key);
        if ($value instanceof Enum) {
            $value = $value->toString();
        }

        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Sets a string value to the given key
     * @param Enum|int|string $key
     * @param string          $value
     * @return Dictionary
     */
    public function setString(Enum|int|string $key, string $value): Dictionary {
        $key = Strings::toString($key);
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Sets an int value to the given key
     * @param Enum|int|string $key
     * @param int             $value
     * @return Dictionary
     */
    public function setInt(Enum|int|string $key, int $value): Dictionary {
        $key = Strings::toString($key);
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Sets an Enum value to the given key
     * @param Enum|int|string $key
     * @param Enum            $value
     * @return Dictionary
     */
    public function setEnum(Enum|int|string $key, Enum $value): Dictionary {
        $key = Strings::toString($key);
        $this->data[$key] = $value->toString();
        return $this;
    }

    /**
     * Removes the value at the given key
     * @param Enum|int|string $key
     * @return Dictionary
     */
    public function remove(Enum|int|string $key): Dictionary {
        $key = Strings::toString($key);
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
        return $this;
    }



    /**
     * Gets the value of the given key
     * @param Enum|int|string $key
     * @return mixed
     */
    public function get(Enum|int|string $key): mixed {
        $key = Strings::toString($key);
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Gets the value of the given key as a Boolean
     * @param Enum|int|string $key
     * @return bool
     */
    public function getBool(Enum|int|string $key): bool {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return $this->hasValue($key);
        }
        return false;
    }

    /**
     * Gets the value of the given key as an Integer
     * @param Enum|int|string $key
     * @param int             $decimals Optional.
     * @param int             $default  Optional.
     * @return int
     */
    public function getInt(Enum|int|string $key, int $decimals = 0, int $default = 0): int {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Numbers::toInt($this->data[$key], $decimals);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Float
     * @param Enum|int|string $key
     * @param float           $default Optional.
     * @return float
     */
    public function getFloat(Enum|int|string $key, float $default = 0.0): float {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Numbers::toFloat($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Price
     * @param Enum|int|string $key
     * @param float           $default Optional.
     * @return float
     */
    public function getPrice(Enum|int|string $key, float $default = 0.0): float {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Numbers::fromCents($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a String
     * @param Enum|int|string $key
     * @param string          $default Optional.
     * @return string
     */
    public function getString(Enum|int|string $key, string $default = ""): string {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Strings::toString($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Date
     * @param Enum|int|string $key
     * @return Date
     */
    public function getDate(Enum|int|string $key): Date {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Date::create($this->data[$key]);
        }
        return Date::empty();
    }

    /**
     * Gets the value of the given key as a Date
     * @param Enum|int|string $key
     * @return Date
     */
    public function getDateParsed(Enum|int|string $key): Date {
        $key  = Strings::toString($key);
        $date = $this->getString($key);
        return Date::parse($date);
    }



    /**
     * Gets the keys of the Data
     * @return list<string>
     */
    public function getKeys(): array {
        $result = array_keys($this->data);
        return Arrays::toStrings($result);
    }

    /**
     * Gets the value of the given key as a single Dictionary
     * @param Enum|int|string $key
     * @return Dictionary
     */
    public function getDict(Enum|int|string $key): Dictionary {
        $key = Strings::toString($key);
        if (isset($this->data[$key])) {
            return new Dictionary($this->data[$key]);
        }
        return new Dictionary();
    }

    /**
     * Finds an element in the list at the given key
     * @param Enum|int|string $key
     * @param string          $value
     * @return Dictionary
     */
    public function findDict(Enum|int|string $key, string $value): Dictionary {
        $key = Strings::toString($key);
        if (array_is_list($this->data)) {
            foreach ($this->data as $elem) {
                if (is_array($elem) && isset($elem[$key]) && $elem[$key] === $value) {
                    return new Dictionary($elem);
                }
            }
        }
        return new Dictionary();
    }

    /**
     * Gets the value of the given key as a list of Dictionary
     * @param Enum|int|string $key
     * @return list<Dictionary>
     */
    public function getList(Enum|int|string $key): array {
        $key    = Strings::toString($key);
        $result = [];
        if (isset($this->data[$key]) && is_array($this->data[$key])) {
            foreach ($this->data[$key] as $item) {
                $result[] = new Dictionary($item);
            }
        }
        return $result;
    }

    /**
     * Gets the first element of the list at the given key
     * @param Enum|int|string $key Optional.
     * @return Dictionary
     */
    public function getFirst(Enum|int|string $key = ""): Dictionary {
        $key = Strings::toString($key);
        if ($key === "") {
            $first = Arrays::getFirst($this->data);
            return new Dictionary($first);
        }

        $list = $this->getList($key);
        return $list[0] ?? new Dictionary();
    }

    /**
     * Gets the last element of the list at the given key
     * @param Enum|int|string $key Optional.
     * @return Dictionary
     */
    public function getLast(Enum|int|string $key = ""): Dictionary {
        $key = Strings::toString($key);
        if ($key === "") {
            $last = Arrays::getLast($this->data);
            return new Dictionary($last);
        }

        $list = $this->getList($key);
        $last = $list[count($list) - 1] ?? null;
        return $last ?? new Dictionary();
    }

    /**
     * Gets the value of the given key as a list of Integers
     * @param Enum|int|string $key
     * @return list<int>
     */
    public function getInts(Enum|int|string $key): array {
        $key = Strings::toString($key);
        if (isset($this->data[$key])) {
            if ($this->data[$key] instanceof self) {
                return $this->data[$key]->toInts();
            }
            return Arrays::toInts($this->data[$key]);
        }
        return [];
    }

    /**
     * Gets the value of the given key as a list of Strings
     * @param Enum|int|string $key
     * @param bool            $withoutEmpty Optional.
     * @return list<string>
     */
    public function getStrings(Enum|int|string $key, bool $withoutEmpty = false): array {
        $key = Strings::toString($key);
        if (isset($this->data[$key])) {
            if ($this->data[$key] instanceof self) {
                return $this->data[$key]->toStrings(withoutEmpty: $withoutEmpty);
            }
            return Arrays::toStrings($this->data[$key], withoutEmpty: $withoutEmpty);
        }
        return [];
    }

    /**
     * Gets the value of the given key as an Array
     * @param Enum|int|string $key
     * @return array<int|string,mixed>
     */
    public function getArray(Enum|int|string $key): array {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && is_array($this->data[$key])) {
            return $this->data[$key];
        }
        return [];
    }

    /**
     * Gets the value of the given key as a JSON
     * @param Enum|int|string $key
     * @return string
     */
    public function getJSON(Enum|int|string $key): string {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && is_array($this->data[$key])) {
            return JSON::encode($this->data[$key]);
        }
        return JSON::encode([]);
    }



    /**
     * Gets the value of the given key as an Array decoded from JSON
     * @param Enum|int|string $key
     * @return array<int|string,mixed>
     */
    public function decodeAsArray(Enum|int|string $key): array {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && is_string($this->data[$key])) {
            return JSON::decodeAsArray($this->data[$key]);
        }
        return [];
    }

    /**
     * Gets the value of the given key as a list of Strings decoded from JSON
     * @param Enum|int|string $key
     * @return list<string>
     */
    public function decodeAsStrings(Enum|int|string $key): array {
        $key = Strings::toString($key);
        if (isset($this->data[$key]) && is_string($this->data[$key])) {
            return JSON::decodeAsStrings($this->data[$key]);
        }
        return [];
    }



    /**
     * Creates a map from the list using the given key
     * @param Enum|int|string $key
     * @param Enum|int|string $value Optional.
     * @return Dictionary
     */
    public function createMap(
        Enum|int|string $key,
        Enum|int|string $value = "",
    ): Dictionary {
        $result = new Dictionary();
        if (!array_is_list($this->data)) {
            return $result;
        }

        $key   = Strings::toString($key);
        $value = Strings::toString($value);

        foreach ($this->data as $elem) {
            if (!is_array($elem)) {
                continue;
            }
            $keyVal = Strings::toString($elem[$key] ?? "");
            if ($keyVal === "") {
                continue;
            }

            if ($value !== "") {
                if (!isset($elem[$value])) {
                    continue;
                }
                $result->set($keyVal, $elem[$value]);
            } else {
                $result->set($keyVal, $elem);
            }
        }
        return $result;
    }

    /**
     * Returns the data as an Array
     * @return array<int|string,mixed>
     */
    public function toArray(): array {
        return $this->data;
    }

    /**
     * Returns the data as a List
     * @return list<mixed>
     */
    public function toList(): array {
        return Arrays::getValues($this->data);
    }

    /**
     * Returns the data as a Map of string keys and values
     * @return array<string,string>
     */
    public function toStringsMap(): array {
        return Arrays::toStringsMap($this->data);
    }

    /**
     * Returns the data as a Map of int keys and string values
     * @return array<int,string>
     */
    public function toIntStringMap(): array {
        return Arrays::toIntStringMap($this->data);
    }

    /**
     * Returns the data as a Map of string keys and int values
     * @return array<string,int>
     */
    public function toStringIntMap(): array {
        return Arrays::toStringIntMap($this->data);
    }

    /**
     * Returns the data as a Map of string keys and mixed values
     * @return array<string,mixed>
     */
    public function toStringMixedMap(): array {
        return Arrays::toStringMixedMap($this->data);
    }

    /**
     * Returns the data as an array of Strings
     * @param bool $withoutEmpty Optional.
     * @return list<string>
     */
    public function toStrings(bool $withoutEmpty = false): array {
        if (Arrays::isList($this->data)) {
            return Arrays::toStrings($this->data, withoutEmpty: $withoutEmpty);
        }

        $values = Arrays::getValues($this->data);
        return Arrays::toStrings($values, withoutEmpty: $withoutEmpty);
    }

    /**
     * Returns the data as an array of Ints
     * @param bool $withoutEmpty Optional.
     * @return list<int>
     */
    public function toInts(bool $withoutEmpty = false): array {
        if (Arrays::isList($this->data)) {
            return Arrays::toInts($this->data, withoutEmpty: $withoutEmpty);
        }

        $values = Arrays::getValues($this->data);
        return Arrays::toInts($values, withoutEmpty: $withoutEmpty);
    }

    /**
     * Encodes the data as a JSON
     * @return string
     */
    public function toJSON(): string {
        return JSON::encode($this->data);
    }



    /**
     * Implements the Countable Interface
     * @phpstan-return 0|positive-int
     * @return int
     */
    #[\Override]
    public function count(): int {
        $result = count($this->data);
        return max(0, $result);
    }

    /**
     * Returns an Iterator
     * @return Traversable<string,Dictionary>
     */
    #[\Override]
    public function getIterator(): Traversable {
        return (function () {
            foreach ($this->data as $key => $value) {
                yield (string)$key => new Dictionary($value);
            }
        })();
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->data;
    }
}
