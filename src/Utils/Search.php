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

    /** @var array<string,mixed> */
    public array  $data;


    /**
     * Creates a new Search instance
     * @param int|string               $id
     * @param string                   $title
     * @param array<string,mixed>|null $data  Optional.
     */
    public function __construct(int|string $id, string $title, ?array $data = null) {
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
            return Strings::toString($this->$key);
        }
        if (isset($this->data[$key])) {
            return Strings::toString($this->data[$key]);
        }
        return "";
    }

    /**
     * Returns the value as an integer
     * @param string $key
     * @return int
     */
    public function getInt(string $key): int {
        if (property_exists($this, $key)) {
            return Numbers::toInt($this->$key);
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
    #[\Override]
    public function jsonSerialize(): mixed {
        return [
            "id"    => $this->id,
            "title" => $this->title,
            "data"  => $this->data,
        ];
    }



    /**
     * Creates a select using the given array
     * @param array<string,mixed>[] $array
     * @param string                $idKey
     * @param string[]|string       $nameKey
     * @return Search[]
     */
    public static function create(array $array, string $idKey, array|string $nameKey): array {
        $result = [];
        $ids    = [];

        foreach ($array as $row) {
            $id = $row[$idKey] ?? null;
            if (!is_int($id) && !is_string($id)) {
                continue;
            }
            if (Arrays::contains($ids, $id)) {
                continue;
            }

            $title    = Strings::toString(Arrays::getValue($row, $nameKey));
            $result[] = new Search($id, $title, $row);
            $ids[]    = $id;
        }
        return $result;
    }
}
