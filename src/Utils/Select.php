<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

use JsonSerializable;

/**
 * A Select Wrapper
 */
class Select implements JsonSerializable {

    public int        $id;
    public string     $field;

    public string|int $key;
    public string     $value;

    public string     $description = "";

    /** @var array<string,mixed> */
    private array $extras = [];


    /**
     * Creates a new Select instance
     * @param string|integer      $key
     * @param string              $value
     * @param array<string,mixed> $extras Optional.
     */
    public function __construct(string|int $key, string $value, array $extras = []) {
        $this->id     = (int)$key;
        $this->field  = (string)$key;
        $this->key    = $key;
        $this->value  = $value;
        $this->extras = $extras;
    }

    /**
     * Returns true if the key exists
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool {
        return property_exists($this, $key) || array_key_exists($key, $this->extras);
    }

    /**
     * Returns true if the key has a value
     * @param string $key
     * @return boolean
     */
    public function hasValue(string $key): bool {
        if (property_exists($this, $key)) {
            return !Arrays::isEmpty($this->$key);
        }
        if (isset($this->extras[$key])) {
            return !Arrays::isEmpty($this->extras[$key]);
        }
        return false;
    }

    /**
     * Returns the value as a string
     * @param string $key
     * @return string
     */
    public function getString(string $key): string {
        if (property_exists($this, $key)) {
            return Strings::toString($this->$key);
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
            return Numbers::toInt($this->$key);
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
        if ($this->description !== "") {
            $result["description"] = $this->description;
        }
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
     * @param string|null          $descName Optional.
     * @param string[]|string|null $extraKey Optional.
     * @param boolean              $useEmpty Optional.
     * @param boolean              $distinct Optional.
     * @return Select[]
     */
    public static function create(
        array $array,
        string $keyName,
        array|string $valName,
        string|null $descName = null,
        array|string|null $extraKey = null,
        bool $useEmpty = false,
        bool $distinct = false,
    ): array {
        $result = [];
        $keys   = [];

        foreach ($array as $row) {
            $key   = Arrays::getValue($row, $keyName, useEmpty: true);
            $value = Arrays::getValue($row, $valName, " - ", "", $useEmpty);

            if ((!is_int($key) && !is_string($key)) || !is_string($value)) {
                continue;
            }
            if (($distinct && Arrays::contains($keys, $key)) || (!$useEmpty && $value === "")) {
                continue;
            }

            $item = new Select($key, $value);
            if ($descName !== null) {
                $item->set("description", Arrays::getValue($row, $descName, useEmpty: true));
            }
            if (!Arrays::isEmpty($extraKey)) {
                $extraKeyNames = Arrays::toStrings($extraKey);
                foreach ($extraKeyNames as $extraKeyName) {
                    $item->set($extraKeyName, Arrays::getValue($row, $extraKeyName, useEmpty: true));
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
