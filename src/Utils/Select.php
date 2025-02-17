<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

use JsonSerializable;

/**
 * A Select Wrapper
 */
class Select implements JsonSerializable {

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
     * Returns true if the key exists
     * @param mixed $key
     * @return boolean
     */
    public function has(mixed $key): bool {
        return property_exists($this, $key) || array_key_exists($key, $this->extras);
    }

    /**
     * Returns the value as a string
     * @param string $key
     * @return string
     */
    public function getString(string $key): string {
        if (property_exists($this, $key)) {
            return (string)$this->$key;
        }
        if (isset($this->extras[$key])) {
            return Strings::toString($this->extras[$key]);
        }
        return "";
    }

    /**
     * Returns the value as an integer
     * @param string $key
     * @return integer
     */
    public function getInt(string $key): int {
        if (property_exists($this, $key)) {
            return (int)$this->$key;
        }
        if (isset($this->extras[$key])) {
            return Numbers::toInt($this->extras[$key]);
        }
        return 0;
    }

    /**
     * Returns all the Extra Values
     * @return array<string,mixed>
     */
    public function getExtras(): array {
        return $this->extras;
    }

    /**
     * Sets the Data
     * @param string $key
     * @param mixed  $value
     * @return Select
     */
    public function set(string $key, mixed $value): Select {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        } else {
            $this->extras[$key] = $value;
        }
        return $this;
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
                    $item->set($extraKey, Arrays::getValue($row, $extraKey, useEmpty: true));
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
     * @param array<string|integer,string> $array
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
