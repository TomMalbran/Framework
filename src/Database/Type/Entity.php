<?php
namespace Framework\Database\Type;

use Framework\Discovery\Discovery;
use Framework\Enum\Enum;
use Framework\Date\Date;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Database Entity
 */
class Entity implements JsonSerializable {

    protected bool $isEmpty = true;



    /**
     * Creates a new Entity instance
     * @param Dictionary $data
     */
    public function __construct(Dictionary $data) {
        $properties = Discovery::getProperties($this);
        foreach ($properties as $property => $type) {
            $added = $this->setValue($data, $property, $type);
            if ($added) {
                $this->isEmpty = false;
            }
        }
    }

    /**
     * Adds a value from a Dictionary
     * @param Dictionary $data
     * @param string     $property
     * @param string     $type
     * @return bool
     */
    private function setValue(Dictionary $data, string $property, string $type): bool {
        if (!$data->has($property)) {
            return false;
        }

        switch ($type) {
        case Date::class:
            $this->$property = $data->getDate($property);
            break;
        case Dictionary::class:
            $this->$property = $data->getDict($property);
            break;
        case "bool":
            $this->$property = $data->getBool($property);
            break;
        case "int":
            $this->$property = $data->getInt($property);
            break;
        case "float":
            $this->$property = $data->getFloat($property);
            break;
        case "string":
            $this->$property = $data->getString($property);
            break;
        default:
            if (method_exists($type, "fromValue")) {
                $this->$property = $type::fromValue($data->getString($property));
            } else {
                $this->$property = $data->getDict($property)->toArray();
            }
        }
        return true;
    }

    /**
     * Returns a list of Properties
     * @return list<string>
     */
    public function getProperties(): array {
        return array_keys(Discovery::getProperties($this));
    }



    /**
     * Returns true if the Entity Exists
     * @return bool
     */
    final public function exists(): bool {
        return !$this->isEmpty;
    }

    /**
     * Returns true if the Entity is Empty
     * @return bool
     */
    final public function isEmpty(): bool {
        return $this->isEmpty;
    }

    /**
     * Returns true if the key exists
     * @param string $key
     * @return bool
     */
    final public function has(string $key): bool {
        return property_exists($this, $key);
    }

    /**
     * Returns true if the value at the given key is not empty
     * @param string $key
     * @return bool
     */
    final public function hasValue(string $key): bool {
        if ($this->has($key)) {
            return !Arrays::isEmpty($this->$key);
        }
        return false;
    }

    /**
     * Returns the Type of the Data at the given key
     * @param string $key
     * @return string
     */
    final public function getType(string $key): string {
        if ($this->has($key)) {
            if ($this->$key instanceof Enum) {
                return get_class($this->$key);
            }
            if ($this->$key instanceof Date) {
                return Date::class;
            }
            return gettype($this->$key);
        }
        return "";
    }

    /**
     * Gets the Data at the given key
     * @param string     $key
     * @param mixed|null $default Optional.
     * @return mixed
     */
    final public function get(string $key, mixed $default = null): mixed {
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
    final public function getString(string $key, string $default = ""): string {
        $result = $this->get($key, $default);
        return Strings::toString($result);
    }

    /**
     * Gets the Data as an Integer
     * @param string $key
     * @param int    $default Optional.
     * @return int
     */
    final public function getInt(string $key, int $default = 0): int {
        $result = $this->get($key, $default);
        return Numbers::toInt($result);
    }

    /**
     * Sets the Data at the given key
     * @param string $key
     * @param mixed  $value
     * @return bool
     */
    final public function set(string $key, mixed $value): bool {
        if ($this->has($key)) {
            $this->$key = $value;
            return true;
        }
        return false;
    }



    /**
     * Returns the Data as a Dictionary
     * @return Dictionary
     */
    public function toDictionary(): Dictionary {
        $result = new Dictionary();
        foreach ($this->getProperties() as $property) {
            $result->set($property, $this->$property);
        }
        return $result;
    }

    /**
     * Returns the Data as an Array
     * @param array<string,mixed> $extraData Optional.
     * @return array<string,mixed>
     */
    public function toArray(array $extraData = []): array {
        $result = [];
        foreach ($this->getProperties() as $property) {
            $result[$property] = $this->$property;
        }
        return $result + $extraData;
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }
}
