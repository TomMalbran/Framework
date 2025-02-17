<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

use JsonSerializable;

/**
 * A Search Wrapper
 */
class Search implements JsonSerializable {

    public int    $id;
    public string $title;
    public mixed  $data;


    /**
     * Creates a new Search instance
     * @param integer|string $id
     * @param string         $title
     * @param mixed|null     $data  Optional.
     */
    public function __construct(int|string $id, string $title, mixed $data = null) {
        $this->id    = (int)$id;
        $this->title = $title;

        if ($data !== null) {
            $this->data = $data;
        } else {
            $this->data = [];
        }
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
        if (isset($this->data[$key])) {
            return Strings::toString($this->data[$key]);
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
        if (isset($this->data[$key])) {
            return Numbers::toInt($this->data[$key]);
        }
        return 0;
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
