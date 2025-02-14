<?php
namespace Framework\Database;

use Framework\Request;

use ArrayAccess;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * The Schema Entity
 */
class Entity implements ArrayAccess, JsonSerializable {

    protected const ID = "";

    private bool $empty = true;



    /**
     * Creates a new Entity instance
     * @param ArrayAccess|array<string,mixed>|null $data Optional.
     */
    public function __construct(ArrayAccess|array|null $data = []) {
        if (empty($data)) {
            return;
        }

        foreach ($this->getProperties() as $property) {
            if (!isset($data[$property])) {
                continue;
            }

            $this->empty = false;
            if (is_numeric($this->$property) && !is_numeric($data[$property])) {
                $this->$property = (float)$data[$property];
            } else {
                $this->$property = $data[$property];
            }
        }
    }

    /**
     * Creates a new Entity instance from a Request
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static {
        $entity = new static();
        foreach ($entity->getPropertiesTypes() as $property => $type) {
            if ($request->exists($property)) {
                $entity->empty = false;
            } else {
                continue;
            }

            if ($property === static::ID && property_exists($entity, "id")) {
                if (is_numeric($request->$property)) {
                    $entity->{"id"} = $request->getInt($property);
                } else {
                    $entity->{"id"} = $request->getString($property);
                }
            }

            switch ($type) {
            case "bool":
                $entity->$property = $request->has($property);
                break;
            case "int":
                $entity->$property = $request->getInt($property);
                break;
            case "float":
                $entity->$property = $request->getFloat($property);
                break;
            default:
                $entity->$property = $request->getString($property);
            }
        }
        return $entity;
    }

    /**
     * Returns a list of Properties and Types
     * @return array<string,string>
     */
    private function getPropertiesTypes(): array {
        $reflection = new ReflectionClass($this);
        $props      = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
        $result     = [];

        foreach ($props as $prop) {
            $type     = $prop->getType();
            $typeName = $type ? $type->getName() : "mixed";
            $result[$prop->getName()] = $typeName;
        }
        return $result;
    }

    /**
     * Returns a list of Properties
     * @return string[]
     */
    public function getProperties(): array {
        return array_keys($this->getPropertiesTypes());
    }



    /**
     * Returns true if the Entity is Empty
     * @return boolean
     */
    public function isEmpty(): bool {
        return $this->empty;
    }

    /**
     * Returns true if the Entity is being Edited
     * @return boolean
     */
    public function isEdit(): bool {
        return !empty($this->get(static::ID));
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
