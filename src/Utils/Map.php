<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use JsonSerializable;

/**
 * A Map wrapper
 */
class Map implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable {

    /** @var ArrayAccess|array{} */
    private ArrayAccess|array $data = [];


    /**
     * Creates a new Map instance
     * @param ArrayAccess|array{}|null $data Optional.
     */
    public function __construct(ArrayAccess|array|null $data = []) {
        if (!empty($data)) {
            $this->data = $data;
        }
    }

    /**
     * Creates a Map using the given Array
     * @param Map|mixed[]          $array
     * @param string               $key
     * @param string[]|string|null $value    Optional.
     * @param boolean              $useEmpty Optional.
     * @return Map
     */
    public static function create(Map|array $array, string $key, array|string|null $value = null, bool $useEmpty = false): Map {
        $result = [];
        foreach ($array as $row) {
            $result[$row[$key]] = !empty($value) ? Arrays::getValue($row, $value, " - ", "", $useEmpty) : $row;
        }
        return new Map($result);
    }

    /**
     * Creates a Map of Arrays using the given Array
     * @param Map|mixed[]          $array
     * @param string               $key
     * @param string[]|string|null $value    Optional.
     * @param boolean              $useEmpty Optional.
     * @return Map
     */
    public static function createArrays(Map|array $array, string $key, array|string|null $value = null, bool $useEmpty = false): Map {
        $result = [];
        foreach ($array as $row) {
            if (empty($result[$row[$key]])) {
                $result[$row[$key]] = [];
            }
            $result[$row[$key]][] = !empty($value) ? Arrays::getValue($row, $value, " - ", "", $useEmpty) : $row;
        }
        return new Map($result);
    }



    /**
     * Returns the data at the given key
     * @param mixed $key
     * @return mixed
     */
    public function get(mixed $key): mixed {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Sets the given key on the data with the given value
     * @param mixed $key
     * @param mixed $value
     * @return Map
     */
    public function set(mixed $key, mixed $value): Map {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Returns true if the given key is set in the data
     * @param mixed $key
     * @return boolean
     */
    public function exists(mixed $key): bool {
        return isset($this->data[$key]);
    }

    /**
     * Removes the data at the given key
     * @param mixed $key
     * @return void
     */
    public function remove(mixed $key): void {
        unset($this->data[$key]);
    }



    /**
     * Returns the data at the given key
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed {
        return $this->get($key);
    }

    /**
     * Sets the given key on the data with the given value
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function __set(string $key, mixed $value): void {
        $this->set($key, $value);
    }

    /**
     * Returns true if the given key is set in the data
     * @param string $key
     * @return boolean
     */
    public function __isset(string $key): bool {
        return $this->exists($key);
    }

    /**
     * Removes the data at the given key
     * @param string $key
     * @return void
     */
    public function __unset(string $key): void {
        $this->remove($key);
    }



    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed {
        return $this->get($key);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void {
        $this->set($key, $value);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return boolean
     */
    public function offsetExists(mixed $key): bool {
        return $this->exists($key);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return void
     */
    public function offsetUnset(mixed $key): void {
        $this->remove($key);
    }



    /**
     * Implements the Countable Interface
     * @return integer
     */
    public function count(): int {
        return count($this->data);
    }

    /**
     * Implements the Iterator Aggregate Interface
     * @return Traversable
     */
    public function getIterator(): Traversable {
        return new ArrayIterator($this->data);
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        return $this->data;
    }

    /**
     * Return the Data for var_dump
     * @return array
     */
    public function __debugInfo(): array {
        return $this->data;
    }
}
