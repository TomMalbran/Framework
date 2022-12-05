<?php
namespace Framework\Schema;

use Framework\Utils\Arrays;

use ArrayAccess;

/**
 * The Database Model
 */
class Model implements ArrayAccess {

    private $idKey   = "";
    private $idValue = 0;
    private $data    = [];
    private $empty   = true;


    /**
     * Creates a new Model instance
     * @param string $idKey Optional.
     * @param array  $data  Optional.
     */
    public function __construct(string $idKey = "", array $data = []) {
        $this->idKey = $idKey;
        $this->data  = $data;
        $this->empty = empty($data);

        if (!empty($idKey) && isset($data[$idKey])) {
            $this->idValue = $data[$idKey];
        }
    }

    /**
     * Creates a Model without an ID
     * @param array $data Optional.
     * @return Model
     */
    public static function create(array $data = []) {
        $model = new Model();
        $model->data  = $data;
        $model->empty = empty($data);
        return $model;
    }

    /**
     * Creates an empty Model
     * @param mixed $idValue Optional.
     * @return Model
     */
    public static function createEmpty($idValue = null): Model {
        $model = new Model();
        $model->idValue = $idValue;
        $model->empty   = true;
        return $model;
    }



    /**
     * Gets the Data
     * @param string $key
     * @return mixed
     */
    public function __get(string $key) {
        return $this->get($key);
    }

    /**
     * Gets the Data
     * @param string $key
     * @param mixed  $default Optional.
     * @return mixed
     */
    public function get(string $key, $default = null) {
        if ($key === "id") {
            return $this->idValue;
        }
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        if ($key === "isDeleted") {
            return empty($this->data);
        }
        return $default;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getInt(string $key, int $default = 0): int {
        $value = $this->get($key);
        return !empty($value) ? (int)$value : $default;
    }

    /**
     * Returns the data at the given key and index or the default
     * @param string  $key
     * @param integer $index
     * @param mixed   $default Optional.
     * @return mixed
     */
    public function getFromArray(string $key, int $index, $default = "") {
        if ($this->has($key) && !empty($this->data[$key][$index])) {
            return $this->data[$key][$index];
        }
        return $default;
    }

    /**
     * Returns the first not empty value with the given keys
     * @param string[] $keys
     * @param mixed    $default Optional.
     * @return mixed
     */
    public function getAnyValue(array $keys, $default = null) {
        return Arrays::getAnyValue($this->data, $keys, $default);
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
     * Returns true if the key exists
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool {
        if ($key === "id") {
            return !empty($this->data[$this->id]);
        }
        return !empty($this->data[$key]);
    }



    /**
     * Sets the Data
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function __set(string $key, $value): void {
        $this->set($key, $value);
    }

    /**
     * Sets the Data
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function set(string $key, $value): void {
        $this->empty = false;
        if ($key === "id") {
            $this->idValue = $value;
            if (!empty($this->idKey)) {
                $this->data[$this->idKey] = $value;
            }
        } else {
            $this->data[$key] = $value;
            if (!empty($this->idKey) && $key == $this->idKey) {
                $this->idValue = $value;
            }
        }
    }

    /**
     * Adds Data
     * @param array $data
     * @return void
     */
    public function add(array $data): void {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Merges another Model into this one
     * @param Model $model
     * @return void
     */
    public function merge(Model $model): void {
        $data = $model->toObject();
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }



    /**
     * Returns true if the Model is Empty
     * @return boolean
     */
    public function isEmpty(): bool {
        return $this->empty;
    }

    /**
     * Returns a new Model
     * @return Model
     */
    public function toModel(): Model {
        return new Model($this->idKey, $this->data);
    }

    /**
     * Returns the Data as an Array
     * @param array $extraData
     * @return array
     */
    public function toArray(array $extraData = []): array {
        return $this->data + $extraData;
    }

    /**
     * Returns all the Data as an Object
     * @return mixed
     */
    public function toObject() {
        return Arrays::toObject($this->data);
    }

    /**
     * Returns only the requested Fields
     * @param string ...$fields
     * @return array
     */
    public function toFields(string ...$fields): array {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $this->data[$field];
        }
        return $result;
    }

    /**
     * Returns true if the Model is equal to the Request
     * @param Model    $other
     * @param string[] $fields
     * @param string[] $subFields Optional.
     * @return boolean
     */
    public function isEqualTo(Model $other, array $fields, array $subFields = []): bool {
        if ($this->empty != $other->empty) {
            return false;
        }
        foreach ($fields as $key) {
            if (isset($this->data[$key]) && isset($other[$key]) && $this->data[$key] != $other[$key]) {
                return false;
            }
        }
        foreach ($subFields as $subKey => $subKeys) {
            if (!empty($subKeys)) {
                if (empty($this->data[$subKey]) && empty($other[$subKey])) {
                    continue;
                }
                if ((empty($this->data[$subKey])  && !empty($other[$subKey])) ||
                    (!empty($this->data[$subKey]) && empty($other[$subKey]))  ||
                    (count($this->data[$subKey])  != count($other[$subKey]))
                ) {
                    return false;
                }
                foreach ($this->data[$subKey] as $index => $subData) {
                    $subOther = $other[$subKey][$index];
                    foreach ($subKeys as $key) {
                        if (isset($subData[$key]) && isset($subOther[$key]) && $subData[$key] != $subOther[$key]) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Returns true if the Model has the same fields to the given Model
     * @param Model    $other
     * @param string[] $fields
     * @return boolean
     */
    public function hasSameFieldsAs(Model $other, array $fields): bool {
        foreach ($fields as $field) {
            if ($this->data[$field] != $other[$field]) {
                return false;
            }
        }
        return true;
    }



    /**
     * Prints the Model
     * @return void
     */
    public function print(): void {
        print("<pre>");
        print_r($this->data);
        print("</pre>");
    }

    /**
     * Dumps the Model
     * @return void
     */
    public function dump(): void {
        var_dump($this->data);
    }

    /**
     * Return the Data for var_dump
     * @return array
     */
    public function __debugInfo(): array {
        return $this->data;
    }



    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key) {
        return $this->get($key);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value): void {
        $this->set($key, $value);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return boolean
     */
    public function offsetExists($key): bool {
        return array_key_exists($key, $this->data);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key): void {
        unset($this->data[$key]);
    }
}
