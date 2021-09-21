<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

/**
 * The Elements wrapper
 */
class Elements {

    private $elements = [];


    /**
     * Creates a new Elements instance
     * @param array $elements Optional.
     */
    public function __construct(array $elements = null) {
        if ($elements !== null) {
            foreach ($elements as $key => $element) {
                $this->add($key, $element);
            }
        }
    }



    /**
     * Adds a new Element
     * @param string  $key
     * @param string  $element
     * @param boolean $condition Optional.
     * @return Elements
     */
    public function add(string $key, string $element, bool $condition = true): Elements {
        if ($condition) {
            $this->elements[$key] = $element;
        }
        return $this;
    }

    /**
     * Returns true if there is at least 1 Element, or the given Element exists
     * @param string|string[] $elements Optional.
     * @return boolean
     */
    public function has($elements = null): bool {
        if ($elements !== null) {
            $elements = Arrays::toArray($elements);
            foreach ($elements as $element) {
                if (!empty($this->elements[$element])) {
                    return true;
                }
            }
            return false;
        }
        return !empty($this->elements);
    }

    /**
     * Returns the Elements as an Object
     * @return array
     */
    public function get(): array {
        return $this->elements;
    }

    /**
     * Returns the Elements as an Object
     * @return array
     */
    public function getValues(): array {
        return array_values($this->elements);
    }

    /**
     * Parses the given Values using the elements
     * @param array $values
     * @return array
     */
    public function parseValues(array $values): array {
        $result = [];
        foreach (array_keys($this->elements) as $key) {
            if (!empty($values[$key])) {
                $result[] = $values[$key];
            } else {
                $result[] = "";
            }
        }
        return $result;
    }



    /**
     * Implements the Array Access Interface
     * @param string $key
     * @return mixed
     */
    public function offsetGet(string $key) {
        return $this->elements[$key];
    }

    /**
     * Implements the Array Access Interface
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function offsetSet(string $key, $value) {
        $this->elements[$key] = $value;
    }

    /**
     * Implements the Array Access Interface
     * @param string $key
     * @return boolean
     */
    public function offsetExists(string $key) {
        return array_key_exists($key, $this->elements);
    }

    /**
     * Implements the Array Access Interface
     * @param string $key
     * @return void
     */
    public function offsetUnset(string $key) {
        unset($this->elements[$key]);
    }
}
