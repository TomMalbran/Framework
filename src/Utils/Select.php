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

    private string    $extraKey   = "";
    private mixed     $extraValue = null;


    /**
     * Creates a new Select instance
     * @param string|integer $key
     * @param string         $value
     */
    public function __construct(string|int $key, string $value) {
        $this->id    = (int)$key;
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     * Sets the Extra key and value
     * @param string $key
     * @param mixed  $value
     * @return Select
     */
    public function setExtra(string $key, mixed $value): Select {
        $this->extraKey   = $key;
        $this->extraValue = $value;
        return $this;
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed {
        if ($key === $this->extraKey) {
            return $this->extraValue;
        }
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
        if ($key === $this->extraKey) {
            $this->extraValue = $value;
        } elseif (property_exists($this, $key)) {
            $this->$key = $value;
        }
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return boolean
     */
    public function offsetExists(mixed $key): bool {
        return $key === $this->extraKey || property_exists($this, $key);
    }

    /**
     * Implements the Array Access Interface
     * @param mixed $key
     * @return void
     */
    public function offsetUnset(mixed $key): void {
        if ($key === $this->extraKey) {
            $this->extraKey   = "";
            $this->extraValue = null;
        } elseif (property_exists($this, $key)) {
            unset($this->$key);
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
        if (!empty($this->extraKey)) {
            $result[$this->extraKey] = $this->extraValue;
        }
        return $result;
    }



    /**
     * Creates a select using the given array
     * @param mixed[]         $array
     * @param string          $keyName
     * @param string[]|string $valName
     * @param boolean         $useEmpty Optional.
     * @param string|null     $extraKey Optional.
     * @param boolean         $distinct Optional.
     * @return Select[]
     */
    public static function create(array $array, string $keyName, array|string $valName, bool $useEmpty = false, ?string $extraKey = null, bool $distinct = false): array {
        $result = [];
        $keys   = [];

        foreach ($array as $row) {
            $key   = $row[$keyName];
            $value = Arrays::getValue($row, $valName, " - ", "", $useEmpty);
            if (($distinct && Arrays::contains($keys, $key)) || (!$useEmpty && empty($value))) {
                continue;
            }

            $item = new Select($key, $value);
            if ($extraKey) {
                $item->setExtra($extraKey, Arrays::getValue($row, $extraKey));
            }

            $result[] = $item;
            $keys[]   = $key;
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
            $result[] = new Select($key, $value);
        }
        return $result;
    }
}
