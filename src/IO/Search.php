<?php
namespace Framework\IO;

use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * A Search Wrapper
 */
class Search implements JsonSerializable {

    public int $id;
    public string $title;

    public Dictionary $data;


    /**
     * Creates a new Search instance
     * @param int|string      $id
     * @param string          $title
     * @param Dictionary|null $data  Optional.
     */
    public function __construct(
        int|string $id,
        string $title,
        ?Dictionary $data = null,
    ) {
        $this->id    = (int)$id;
        $this->title = $title;

        if ($data !== null) {
            $this->data = $data;
        } else {
            $this->data = new Dictionary();
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
        return $this->data->getString($key);
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
        return $this->data->getInt($key);
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
     * @param Dictionary          $data
     * @param string              $idKey
     * @param list<string>|string $nameKey
     * @return list<Search>
     */
    public static function create(
        Dictionary $data,
        string $idKey,
        array|string $nameKey,
    ): array {
        $result = [];
        $ids    = [];

        foreach ($data as $row) {
            $id = $row->getString($idKey);
            if (is_numeric($id)) {
                $id = (int)$id;
            }
            if (Arrays::contains($ids, $id)) {
                continue;
            }

            $title = "";
            if (is_array($nameKey)) {
                $titles = [];
                foreach ($nameKey as $key) {
                    $titles[] = $row->getString($key);
                }
                $title = Strings::join($titles, " - ");
            } else {
                $title = $row->getString($nameKey);
            }

            $result[] = new Search($id, $title, $row);
            $ids[]    = $id;
        }
        return $result;
    }
}
