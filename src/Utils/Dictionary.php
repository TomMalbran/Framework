<?php
namespace Framework\Utils;

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

    /** @var array<string|int,mixed> */
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
     * Returns true if the data is a list
     * @param string $key Optional.
     * @return bool
     */
    public function isList(string $key = ""): bool {
        if ($key !== "") {
            return isset($this->data[$key]) && Arrays::isList($this->data[$key]);
        }
        return Arrays::isList($this->data);
    }

    /**
     * Returns true if the key exits in the data
     * @param string|int $key
     * @return bool
     */
    public function has(string|int $key): bool {
        $key = (string)$key;
        return isset($this->data[$key]);
    }

    /**
     * Returns true if the key exits and has a value in the data
     * @param string|int $key
     * @return bool
     */
    public function hasValue(string|int $key): bool {
        $key = (string)$key;
        return !Arrays::isEmpty($this->data, $key);
    }



    /**
     * Adds an array to the data
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
     * @param string $key
     * @param mixed  $value
     * @return Dictionary
     */
    public function set(string $key, mixed $value): Dictionary {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Sets a string value of the given key
     * @param string $key
     * @param string $value
     * @return Dictionary
     */
    public function setString(string $key, string $value): Dictionary {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Sets an int value of the given key
     * @param string $key
     * @param int    $value
     * @return Dictionary
     */
    public function setInt(string $key, int $value): Dictionary {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Removes the value at the given key
     * @param string $key
     * @return Dictionary
     */
    public function remove(string $key): Dictionary {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
        return $this;
    }



    /**
     * Gets the value of the given key
     * @param string|int $key
     * @return mixed
     */
    public function get(string|int $key): mixed {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Gets the value of the given key as a Boolean
     * @param string $key
     * @return bool
     */
    public function getBool(string $key): bool {
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return $this->hasValue($key);
        }
        return false;
    }

    /**
     * Gets the value of the given key as an Integer
     * @param string $key
     * @param int    $decimals Optional.
     * @param int    $default  Optional.
     * @return int
     */
    public function getInt(string $key, int $decimals = 0, int $default = 0): int {
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Numbers::toInt($this->data[$key], $decimals);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Float
     * @param string $key
     * @param float  $default Optional.
     * @return float
     */
    public function getFloat(string $key, float $default = 0.0): float {
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Numbers::toFloat($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Price
     * @param string $key
     * @param float  $default Optional.
     * @return float
     */
    public function getPrice(string $key, float $default = 0.0): float {
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Numbers::fromCents($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a String
     * @param string $key
     * @param string $default Optional.
     * @return string
     */
    public function getString(string $key, string $default = ""): string {
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Strings::toString($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Date
     * @param string $key
     * @return Date
     */
    public function getDate(string $key): Date {
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return Date::create($this->data[$key]);
        }
        return Date::empty();
    }

    /**
     * Gets the value of the given key as a Date
     * @param string $key
     * @return Date
     */
    public function getDateParsed(string $key): Date {
        $date = $this->getString($key);
        return Date::parse($date);
    }



    /**
     * Gets the keys of the Data
     * @return string[]
     */
    public function getKeys(): array {
        $result = array_keys($this->data);
        return Arrays::toStrings($result);
    }

    /**
     * Gets the value of the given key as a single Dictionary
     * @param string|int $key
     * @return Dictionary
     */
    public function getDict(string|int $key): Dictionary {
        $key = (string)$key;
        if (isset($this->data[$key])) {
            return new Dictionary($this->data[$key]);
        }
        return new Dictionary();
    }

    /**
     * Gets the value of the given key as a list of Dictionary
     * @param string $key
     * @return Dictionary[]
     */
    public function getList(string $key): array {
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
     * @param string $key Optional.
     * @return Dictionary
     */
    public function getFirst(string $key = ""): Dictionary {
        if ($key === "") {
            $first = Arrays::getFirst($this->data);
            return new Dictionary($first);
        }

        $list = $this->getList($key);
        return $list[0] ?? new Dictionary();
    }

    /**
     * Finds an element in the list at the given key
     * @param string $key
     * @param string $value
     * @return Dictionary
     */
    public function findDict(string $key, string $value): Dictionary {
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
     * Gets the value of the given key as a list of Integers
     * @param string $key
     * @return int[]
     */
    public function getInts(string $key): array {
        if (isset($this->data[$key])) {
            return Arrays::toInts($this->data[$key]);
        }
        return [];
    }

    /**
     * Gets the value of the given key as a list of Strings
     * @param string $key
     * @param bool   $withoutEmpty Optional.
     * @return string[]
     */
    public function getStrings(string $key, bool $withoutEmpty = false): array {
        if (isset($this->data[$key])) {
            return Arrays::toStrings($this->data[$key], withoutEmpty: $withoutEmpty);
        }
        return [];
    }

    /**
     * Gets the value of the given key as an Array
     * @param string $key
     * @return array<string|int,mixed>
     */
    public function getArray(string $key): array {
        if (isset($this->data[$key]) && is_array($this->data[$key])) {
            return $this->data[$key];
        }
        return [];
    }

    /**
     * Gets the value of the given key as a JSON
     * @param string $key
     * @return string
     */
    public function getJSON(string $key): string {
        if (isset($this->data[$key]) && is_array($this->data[$key])) {
            return JSON::encode($this->data[$key]);
        }
        return JSON::encode([]);
    }



    /**
     * Gets the value of the given key as an Array decoded from JSON
     * @param string $key
     * @return array<string|int,mixed>
     */
    public function decodeAsArray(string $key): array {
        if (isset($this->data[$key]) && is_string($this->data[$key])) {
            return JSON::decodeAsArray($this->data[$key]);
        }
        return [];
    }

    /**
     * Gets the value of the given key as a list of Strings decoded from JSON
     * @param string $key
     * @return string[]
     */
    public function decodeAsStrings(string $key): array {
        if (isset($this->data[$key]) && is_string($this->data[$key])) {
            return JSON::decodeAsStrings($this->data[$key]);
        }
        return [];
    }



    /**
     * Creates a map from the list using the given key
     * @param string $key
     * @return Dictionary
     */
    public function createMap(string $key): Dictionary {
        $result = new Dictionary();
        if (array_is_list($this->data)) {
            foreach ($this->data as $elem) {
                if (is_array($elem)) {
                    $keyVal = Strings::toString($elem[$key] ?? "");
                    if ($keyVal !== "") {
                        $result->set($keyVal, $elem);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Returns the data as an Array
     * @return array<string|int,mixed>
     */
    public function toArray(): array {
        return $this->data;
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
     * @return string[]
     */
    public function toStrings(bool $withoutEmpty = false): array {
        if (Arrays::isList($this->data)) {
            return Arrays::toStrings($this->data, withoutEmpty: $withoutEmpty);
        }

        $values = array_values($this->data);
        if (Arrays::isList($values)) {
            return Arrays::toStrings($values, withoutEmpty: $withoutEmpty);
        }
        return [];
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
