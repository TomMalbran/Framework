<?php
namespace Framework\Utils;

use IteratorAggregate;
use Traversable;

/**
 * The Tuple
 */
class Tuple implements IteratorAggregate {

    /** @var object[] */
    private array $items;


    /**
     * Creates a new Tuple instance
     */
    public function __construct() {
        $this->items = [];
    }

    /**
     * Creates a new Tuple and ands the Item
     * @param string  $key
     * @param mixed   $value
     * @param boolean $condition Optional.
     * @return Tuple
     */
    public static function create(string $key, mixed $value, bool $condition = true): Tuple {
        $tuple = new Tuple();
        $tuple->add($key, $value, $condition);
        return $tuple;
    }



    /**
     * Adds a new Tuple if the condition is met
     * @param string  $key
     * @param mixed   $value
     * @param boolean $condition Optional.
     * @return Tuple
     */
    public function add(string $key, mixed $value, bool $condition = true): Tuple {
        if ($condition && !empty($value)) {
            $this->items[] = (object)[
                "key"   => $key,
                "value" => $value,
            ];
        }
        return $this;
    }

    /**
     * Returns the Tuple items
     * @return object[]
     */
    public function get(): array {
        return $this->items;
    }

    /**
     * Returns true if there are items
     * @param string[]|string|null $items Optional.
     * @return boolean
     */
    public function has(array|string|null $items = null): bool {
        if ($items == null) {
            return !empty($this->items);
        }
        $items = Arrays::toArray($items);
        foreach ($items as $item) {
            if (Arrays::contains($this->items, $item, "key")) {
                return true;
            }
        }
        return false;
    }



    /**
     * Returns true if the key exists
     * @param string $key
     * @return boolean
     */
    public function __isset(string $key): bool {
        return $this->has($key);
    }

    /**
     * Return the Data for var_dump
     * @return array
     */
    public function __debugInfo(): array {
        return $this->items;
    }

    /**
     * Returns an Iterator
     * @return Traversable
     */
    public function getIterator() : Traversable {
        return (function () {
            foreach ($this->items as $item) {
                yield $item->key => $item->value;
            }
        })();
    }
}
