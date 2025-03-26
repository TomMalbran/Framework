<?php
namespace Framework\Database;

use Framework\Request;
use Framework\Discovery\Discovery;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Schema Entity
 */
class Entity implements JsonSerializable {

    protected const ID = "";

    private bool $isEmpty = true;
    private bool $isEdit  = false;



    /**
     * Creates a new Entity instance from a Request
     * @param Request $request
     * @return static[]
     */
    final public static function createList(Request $request, string $key): array {
        $data   = $request->getDictionary($key);
        $result = [];
        foreach ($data as $item) {
            $result[] = new static($item);
        }
        return $result;
    }

    /**
     * Creates a new Entity instance
     * @param mixed $data Optional.
     */
    final public function __construct(mixed $data = null) {
        if (empty($data)) {
            return;
        }

        foreach ($this->getPropertiesTypes() as $property => $type) {
            if ($data instanceof Request) {
                $added = $this->addFromRequest($data, $property, $type);
            } elseif ($data instanceof Dictionary) {
                $added = $this->addFromDictionary($data, $property, $type);
            } else {
                $added = $this->addFromObject($data, $property, $type);
            }
            if ($added) {
                $this->isEmpty = false;
            }
        }
    }

    /**
     * Adds a value from an Object or Array
     * @param mixed  $data
     * @param string $property
     * @param string $type
     * @return boolean
     */
    private function addFromObject(mixed $data, string $property, string $type): bool {
        $value = Arrays::getOneValue($data, $property);
        if ($value === null) {
            return false;
        }

        switch ($type) {
        case "int":
            $this->$property = Numbers::toInt($value);
            break;
        case "float":
            $this->$property = Numbers::toFloat($value);
            break;
        default:
            $this->$property = $value;
        }
        return true;
    }

    /**
     * Adds a value from a Request
     * @param Request $request
     * @param string  $property
     * @param string  $type
     * @return boolean
     */
    private function addFromRequest(Request $request, string $property, string $type): bool {
        if (!$request->exists($property)) {
            return false;
        }

        if ($property === static::ID && property_exists($this, "id")) {
            if (is_numeric($request->get($property))) {
                $this->{"id"} = $request->getInt($property);
            } else {
                $this->{"id"} = $request->getString($property);
            }
            if ($this->{"id"} !== "" && $this->{"id"} !== 0) {
                $this->isEdit = true;
            }
        }

        switch ($type) {
        case "bool":
            $this->$property = $request->has($property);
            break;
        case "int":
            $this->$property = $request->getInt($property);
            break;
        case "float":
            $this->$property = $request->getFloat($property);
            break;
        case "string":
            $this->$property = $request->getString($property);
            break;
        default:
            $this->$property = $request->get($property);
        }
        return true;
    }

    /**
     * Adds a value from a Dictionary
     * @param Dictionary $data
     * @param string  $property
     * @param string  $type
     * @return boolean
     */
    private function addFromDictionary(Dictionary $data, string $property, string $type): bool {
        if (!$data->has($property)) {
            return false;
        }

        if ($property === static::ID && property_exists($this, "id")) {
            if (is_numeric($data->getString($property))) {
                $this->{"id"} = $data->getInt($property);
            } else {
                $this->{"id"} = $data->getString($property);
            }
            if ($this->{"id"} !== "" && $this->{"id"} !== 0) {
                $this->isEdit = true;
            }
        }

        switch ($type) {
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
            $this->$property = $data->getArray($property);
        }
        return true;
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
    final public function isEmpty(): bool {
        return $this->isEmpty;
    }

    /**
     * Returns true if the Entity is being Edited
     * @return boolean
     */
    final public function isEdit(): bool {
        return $this->isEdit;
    }

    /**
     * Returns true if the key exists
     * @param string $key
     * @return boolean
     */
    final public function has(string $key): bool {
        return property_exists($this, $key);
    }

    /**
     * Returns true if the value at the given key is not empty
     * @param string $key
     * @return boolean
     */
    final public function hasValue(string $key): bool {
        return !empty($this->$key);
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
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
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
     * Returns the Data as an Array
     * @param array{} $extraData
     * @return array{}
     */
    final public function toArray(array $extraData = []): array {
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
    final public function toFields(string ...$fields): array {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $this->$field;
        }
        return $result;
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        return $this->toArray();
    }
}
