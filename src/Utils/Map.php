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
     * @param ArrayAccess|array{} $data Optional.
     */
    public function __construct(ArrayAccess|array $data = []) {
        $this->data = $data;
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void {
        $this->data[$key] = $value;
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return boolean
     */
    public function offsetExists(mixed $key): bool {
        return isset($this->data[$key]);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return void
     */
    public function offsetUnset(mixed $key): void {
        unset($this->data[$key]);
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
     * Creates a Map using the given Array
     * @param Map|mixed[]          $array
     * @param string               $key
     * @param string[]|string|null $value    Optional.
     * @param boolean              $useEmpty Optional.
     * @return Map
     */
    public static function create(Map|array $array, string $key, array|string $value = null, bool $useEmpty = false): Map {
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
    public static function createArrays(Map|array $array, string $key, array|string $value = null, bool $useEmpty = false): Map {
        $result = [];
        foreach ($array as $row) {
            if (empty($result[$row[$key]])) {
                $result[$row[$key]] = [];
            }
            $result[$row[$key]][] = !empty($value) ? Arrays::getValue($row, $value, " - ", "", $useEmpty) : $row;
        }
        return new Map($result);
    }
}
