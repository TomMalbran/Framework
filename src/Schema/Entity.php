<?php
namespace Framework\Schema;

use Framework\Utils\Arrays;

use ArrayAccess;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * The Schema Entity
 */
class Entity implements ArrayAccess, JsonSerializable {

    private bool $empty = true;


    /**
     * Creates a new Entity instance
     * @param ArrayAccess|array{}|null $data Optional.
     */
    public function __construct(ArrayAccess|array|null $data = []) {
        if (empty($data)) {
            return;
        }
        foreach ($this->getProperties() as $property) {
            if (isset($data[$property])) {
                $this->$property = $data[$property];
                $this->empty     = false;
            }
        }
    }

    /**
     * Returns a list of Properties
     * @return string[]
     */
    public function getProperties(): array {
        $reflection = new ReflectionClass($this);
        $props      = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
        $result     = [];

        foreach ($props as $prop) {
            $result[] = $prop->getName();
        }
        return $result;
    }



    /**
     * Returns true if the Model is Empty
     * @return boolean
     */
    public function isEmpty(): bool {
        return $this->empty;
    }

    /**
     * Returns true if the key exists
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool {
        return property_exists($this, $key);
    }

    /**
     * Gets the Data
     * @param string     $key
     * @param mixed|null $default Optional.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
        return $default;
    }



    /**
     * Returns the Data as an Array
     * @param array{} $extraData
     * @return array{}
     */
    public function toArray(array $extraData = []): array {
        $result = [];
        foreach ($this->getProperties() as $property) {
            $result[$property] = $this->$property;
        }
        return $result + $extraData;
    }

    /**
     * Returns the Data as a Model
     * @param array{} $extraData
     * @return Model
     */
    public function toModel(array $extraData = []): Model {
        $array = $this->toArray($extraData);
        $id    = !empty($array["id"]) ? $array["id"] : 0;
        return new Model("", $array, $id);
    }

    /**
     * Returns all the Data as an Object
     * @param array{} $extraData
     * @return mixed
     */
    public function toObject(array $extraData = []): mixed {
        return Arrays::toObject($this->toArray($extraData));
    }

    /**
     * Returns only the requested Fields
     * @param string ...$fields
     * @return array{}
     */
    public function toFields(string ...$fields): array {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $this->$field;
        }
        return $result;
    }



    /**
     * Prints the Entity
     * @return Entity
     */
    public function print(): Entity {
        print("<pre>");
        print_r($this->toArray());
        print("</pre>");
        return $this;
    }

    /**
     * Dumps the Entity
     * @return Entity
     */
    public function dump(): Entity {
        var_dump($this->toArray());
        return $this;
    }



    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed {
        if (property_exists($this, $key)) {
            return $this->$key;
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
        if (property_exists($this, $key)) {
            $this->$key = $value;
        }
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return boolean
     */
    public function offsetExists(mixed $key): bool {
        return property_exists($this, $key);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return void
     */
    public function offsetUnset(mixed $key): void {
        if (property_exists($this, $key)) {
            unset($this->$key);
        }
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }
}
