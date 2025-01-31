<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

use ArrayAccess;
use JsonSerializable;

/**
 * A Select Wrapper
 */
class Select implements ArrayAccess, JsonSerializable {

    public int        $id;
    public string|int $key;
    public string     $value;

    /** @var array<string,mixed> */
    private array     $extras = [];


    /**
     * Creates a new Select instance
     * @param string|integer      $key
     * @param string              $value
     * @param array<string,mixed> $extras Optional.
     */
    public function __construct(string|int $key, string $value, array $extras = []) {
        $this->id     = (int)$key;
        $this->key    = $key;
        $this->value  = $value;
        $this->extras = $extras;
    }

    /**
     * Set an Extra key and value
     * @param string $key
     * @param mixed  $value
     * @return Select
     */
    public function setExtra(string $key, mixed $value): Select {
        $this->extras[$key] = $value;
        return $this;
    }

    /**
     * Returns true if there is an Extra Value
     * @param string $key Optional.
     * @return boolean
     */
    public function hasExtra(string $key = ""): bool {
        if (empty($key)) {
            return !empty($this->extras);
        }
        return array_key_exists($key, $this->extras);
    }

    /**
     * Returns the Extra Value
     * @param string $key
     * @return mixed
     */
    public function getExtra(string $key): mixed {
        if (array_key_exists($key, $this->extras)) {
            return $this->extras[$key];
        }
        return null;
    }

    /**
     * Returns all the Extra Values
     * @return array<string,mixed>
     */
    public function getExtras(): array {
        return $this->extras;
    }



    /**
     * Returns true if the key exists
     * @param mixed $key
     * @return boolean
     */
    public function has(mixed $key): bool {
        return property_exists($this, $key) || array_key_exists($key, $this->extras);
    }

    /**
     * Returns the Data
     * @param mixed $key
     * @return mixed
     */
    public function get(mixed $key): mixed {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
        if (!empty($this->extras[$key])) {
            return $this->extras[$key];
        }
        return null;
    }

    /**
     * Sets the Data
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function set(mixed $key, mixed $value): void {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        } elseif (array_key_exists($key, $this->extras)) {
            $this->extras[$key] = $value;
        }
    }



    /**
     * Implements the Object exists
     * @param string $key
     * @return boolean
     */
    public function __isset(string $key): bool {
        return $this->has($key);
    }

    /**
     * Implements the Object Get
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed {
        return $this->get($key);
    }

    /**
     * Implements the Object Set
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function __set(string $key, mixed $value): void {
        $this->set($key, $value);
    }



    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return boolean
     */
    public function offsetExists(mixed $key): bool {
        return $this->has($key);
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
     * @return void
     */
    public function offsetUnset(mixed $key): void {
        if (property_exists($this, $key)) {
            unset($this->$key);
        } elseif (!empty($this->extras[$key])) {
            unset($this->extras[$key]);
        }
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        $result = [
            "key"   => $this->key,
            "value" => $this->value,
        ];
        foreach ($this->extras as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }



    /**
     * Creates a select using the given array
     * @param mixed[]              $array
     * @param string               $keyName
     * @param string[]|string      $valName
     * @param boolean              $useEmpty Optional.
     * @param string[]|string|null $extraKey Optional.
     * @param boolean              $distinct Optional.
     * @return Select[]
     */
    public static function create(
        array $array,
        string $keyName,
        array|string $valName,
        bool $useEmpty = false,
        array|string|null $extraKey = null,
        bool $distinct = false,
    ): array {
        $result = [];
        $keys   = [];

        foreach ($array as $row) {
            $key   = Arrays::getValue($row, $keyName);
            $value = Arrays::getValue($row, $valName, " - ", "", $useEmpty);
            if (($distinct && Arrays::contains($keys, $key)) || (!$useEmpty && empty($value))) {
                continue;
            }

            $item = new Select($key, $value);
            if (!empty($extraKey)) {
                $extraKeys = Arrays::toArray($extraKey);
                foreach ($extraKeys as $extraKey) {
                    $item->setExtra($extraKey, Arrays::getValue($row, $extraKey));
                }
            }

            $result[] = $item;
            $keys[]   = $key;
        }
        return $result;
    }

    /**
     * Creates a select using the given array
     * @param string[] $array
     * @return Select[]
     */
    public static function createFromList(array $array): array {
        $result = [];
        foreach ($array as $value) {
            $result[] = new Select($value, $value);
        }
        return $result;
    }

    /**
     * Creates a select using the given array
     * @param mixed[] $array
     * @return Select[]
     */
    public static function createFromMap(array $array): array {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = new Select((string)$key, $value);
        }
        return $result;
    }
}
