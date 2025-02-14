<?php
namespace Framework\Utils;

use JsonSerializable;

/**
 * A Dictionary wrapper
 */
class Dictionary implements JsonSerializable {

    /** @var array{} */
    private array $data;


    /**
     * Creates a new Dictionary instance
     * @param mixed|null $input Optional.
     */
    public function __construct(mixed $input = null) {
        if ($input instanceof self) {
            $this->data = $input->data;
        } elseif (Arrays::isArray($input)) {
            $this->data = $input;
        } elseif (Arrays::isObject($input)) {
            $this->data = (array)$input;
        } else {
            $this->data = [];
        }
    }



    /**
     * Returns true if the data is empty
     * @return boolean
     */
    public function isEmpty(): bool {
        return empty($this->data);
    }

    /**
     * Returns true if the key exits in the data
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    /**
     * Gets the value of the given key as an Integer
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getInt(string $key, int $default = 0): int {
        if ($this->has($key)) {
            return Numbers::toInt($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Float
     * @param string  $key
     * @param integer $decimals Optional.
     * @param float   $default  Optional.
     * @return float
     */
    public function getFloat(string $key, int $decimals = 2, float $default = 0.0): float {
        if ($this->has($key)) {
            return Numbers::toFloat($this->data[$key], $decimals);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a String
     * @param string $key
     * @param string $default Optional.
     * @return string
     */
    public function getString(string $key, string $default = ""): string {
        if ($this->has($key)) {
            return Strings::toString($this->data[$key]);
        }
        return $default;
    }

    /**
     * Gets the value of the given key as a Timestamp
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getTime(string $key, int $default = 0): int {
        if ($this->has($key)) {
            return DateTime::toTime($this->data[$key]);
        }
        return $default;
    }



    /**
     * Gets the value of the given key as a single Dict
     * @param string $key
     * @return Dictionary
     */
    public function getDict(string $key): Dictionary {
        if ($this->has($key) && Arrays::isMap($this->data[$key])) {
            return new Dictionary($this->data[$key]);
        }
        return new Dictionary();
    }

    /**
     * Gets the value of the given key as a list of Dictionary
     * @param string $key
     * @return Dictionary[]
     */
    public function getList(string $key): array {
        $result = [];
        if ($this->has($key) && Arrays::isList($this->data[$key])) {
            foreach ($this->data[$key] as $item) {
                $result[] = new Dictionary($item);
            }
        }
        return $result;
    }

    /**
     * Gets the first element of the list at the given key
     * @param string $key
     * @return Dictionary
     */
    public function getFirst(string $key): Dictionary {
        $list = $this->getList($key);
        return $list[0] ?: new Dictionary();
    }



    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        return $this->data;
    }
}
