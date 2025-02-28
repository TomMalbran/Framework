<?php
namespace Framework\Database;

use Framework\Request;
use Framework\Discovery\Discovery;
use Framework\Utils\Arrays;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Schema Entity
 */
class Entity implements JsonSerializable {

    protected const ID = "";

    private bool $empty = true;



    /**
     * Creates a new Entity instance
     * @param mixed $data Optional.
     */
    public function __construct(mixed $data = null) {
        if (empty($data)) {
            return;
        }

        foreach ($this->getProperties() as $property) {
            $value = Arrays::getOneValue($data, $property);
            if ($value === null) {
                continue;
            }

            $this->empty = false;
            if (is_numeric($this->$property) && !is_numeric($value)) {
                $this->$property = (float)$value;
            } else {
                $this->$property = $value;
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
                if (is_numeric($request->get($property))) {
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
        return Discovery::getProperties($this);
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
     * Returns true if the value at the given key is not empty
     * @param string $key
     * @return boolean
     */
    public function hasValue(string $key): bool {
        return !empty($this->$key);
    }

    /**
     * Gets the Data at the given key
     * @param string     $key
     * @param mixed|null $default Optional.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed {
        if ($this->has($key)) {
            return $this->$key;
        }
        return $default;
    }

    /**
     * Gets the Data as a String
     * @param string $key
     * @param string $default Optional.
     * @return string
     */
    public function getString(string $key, string $default = ""): string {
        $result = $this->get($key, $default);
        return Strings::toString($result);
    }

    /**
     * Gets the Data as an Integer
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getInt(string $key, int $default = 0): int {
        $result = $this->get($key, $default);
        return Numbers::toInt($result);
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
     * Dumps the Entity
     * @return Entity
     */
    public function dump(): Entity {
        var_dump($this->toArray());
        return $this;
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }
}
