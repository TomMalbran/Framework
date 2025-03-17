<?php
namespace Framework\Utils;

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

    /** @var array<string|integer,mixed> */
    private array $data;


    /**
     * Creates a new Dictionary instance
     * @param mixed|null $input Optional.
     */
    public function __construct(mixed $input = null) {
        if ($input instanceof self) {
            $this->data = $input->data;
        } elseif (Arrays::isArray($input)) {
            $this->data = $input;
        } elseif (Arrays::isObject($input)) {
            $this->data = (array)$input;
        } elseif (JSON::isValid($input)) {
            $this->data = JSON::decodeAsArray($input);
        } else {
            $this->data = [];
        }
    }



    /**
     * Returns true if the data is empty
     * @return boolean
     */
    public function isEmpty(): bool {
        return empty($this->data);
    }

    /**
     * Returns true if the data is a list
     * @param string $key Optional.
     * @return boolean
     */
    public function isList(string $key = ""): bool {
        if ($key !== "") {
            return !empty($this->data[$key]) && Arrays::isList($this->data[$key]);
        }
        return Arrays::isList($this->data);
    }

    /**
     * Returns true if the key exits in the data
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    /**
     * Returns true if the key exits and has a value in the data
     * @param string $key
     * @return boolean
     */
    public function hasValue(string $key): bool {
        return !empty($this->data[$key]);
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
     * @param string  $key
     * @param integer $value
     * @return Dictionary
     */
    public function setInt(string $key, int $value): Dictionary {
        $this->data[$key] = $value;
        return $this;
    }



    /**
     * Gets the value of the given key as a Boolean
     * @param string $key
     * @return boolean
     */
    public function getBool(string $key): bool {
        if ($this->has($key) && !Arrays::isArray($this->data[$key])) {
            return !empty($this->data[$key]);
        }
        return false;
    }

    /**
     * Gets the value of the given key as an Integer
     * @param string  $key
     * @param integer $decimals Optional.
     * @param integer $default  Optional.
     * @return integer
     */
    public function getInt(string $key, int $decimals = 0, int $default = 0): int {
        if ($this->has($key) && !Arrays::isArray($this->data[$key])) {
            return Numbers::toInt($this->data[$key], $decimals);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Float
     * @param string  $key
     * @param float   $default Optional.
     * @return float
     */
    public function getFloat(string $key, float $default = 0.0): float {
        if ($this->has($key) && !Arrays::isArray($this->data[$key])) {
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
        if ($this->has($key) && !Arrays::isArray($this->data[$key])) {
            return Numbers::fromCents((int)$this->data[$key]);
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
        if ($this->has($key) && !Arrays::isArray($this->data[$key])) {
            return Strings::toString($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Timestamp
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getTime(string $key, int $default = 0): int {
        if ($this->has($key) && !Arrays::isArray($this->data[$key])) {
            return DateTime::toTime($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Timestamp
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getTimeParsed(string $key, int $default = 0): int {
        if ($this->has($key) && !Arrays::isArray($this->data[$key])) {
            return DateTime::parseDate($this->data[$key]);
        }
        return $default;
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
     * @param string $key
     * @return Dictionary
     */
    public function getDict(string $key): Dictionary {
        if ($this->has($key)) {
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
        if ($this->has($key) && Arrays::isArray($this->data[$key])) {
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
     * @return Dictionary
     */
    public function findDict(string $key, string $value): Dictionary {
        if (Arrays::isList($this->data)) {
            foreach ($this->data as $elem) {
                if ($elem[$key] === $value) {
                    return new Dictionary($elem);
                }
            }
        }
        return new Dictionary();
    }

    /**
     * Gets the value of the given key as a list of Integers
     * @param string $key
     * @return integer[]
     */
    public function getInts(string $key): array {
        if ($this->has($key)) {
            return Arrays::toInts($this->data[$key]);
        }
        return [];
    }

    /**
     * Gets the value of the given key as a list of Strings
     * @param string  $key
     * @param boolean $withoutEmpty Optional.
     * @return string[]
     */
    public function getStrings(string $key, bool $withoutEmpty = false): array {
        if ($this->has($key)) {
            return Arrays::toStrings($this->data[$key], withoutEmpty: $withoutEmpty);
        }
        return [];
    }

    /**
     * Gets the value of the given key as an Array
     * @param string $key
     * @return array<string|integer,mixed>
     */
    public function getArray(string $key): array {
        if ($this->has($key) && is_array($this->data[$key])) {
            return $this->data[$key];
        }
        return [];
    }



    /**
     * Returns the data as an Array
     * @return array<string|integer,mixed>
     */
    public function toArray(): array {
        return $this->data;
    }

    /**
     * Returns the data as a Map
     * @return array<string,string>
     */
    public function toMap(): array {
        $result = [];
        foreach ($this->data as $key => $value) {
            $result[(string)$key] = Strings::toString($value);
        }
        return $result;
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
     * @return integer
     */
    public function count(): int {
        return count($this->data);
    }

    /**
     * Returns an Iterator
     * @return Traversable<string,Dictionary>
     */
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
    public function jsonSerialize(): mixed {
        return $this->data;
    }
}
