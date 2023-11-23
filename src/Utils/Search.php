<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

use ArrayAccess;
use JsonSerializable;

/**
 * A Search Wrapper
 */
class Search implements ArrayAccess, JsonSerializable {

    public int    $id;
    public string $title;
    public mixed  $data;


    /**
     * Creates a new Search instance
     * @param integer|string $id
     * @param string         $title
     * @param mixed          $data
     */
    public function __construct(int|string $id, string $title, mixed $data) {
        $this->id    = (int)$id;
        $this->title = $title;
        $this->data  = $data;
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
        return [
            "id"    => $this->id,
            "title" => $this->title,
            "data"  => $this->data,
        ];
    }



    /**
     * Creates a select using the given array
     * @param mixed[]         $array
     * @param string          $idKey
     * @param string[]|string $nameKey
     * @return Search[]
     */
    public static function create(array $array, string $idKey, array|string $nameKey): array {
        $result = [];
        $ids    = [];

        foreach ($array as $row) {
            $id = $row[$idKey];
            if (!Arrays::contains($ids, $id)) {
                $title    = Arrays::getValue($row, $nameKey);
                $result[] = new Search($id, $title, $row);
                $ids[]    = $id;
            }
        }
        return $result;
    }
}
