<?php
namespace Framework\Schema;

use Framework\Schema\Field;
use Framework\Utils\Strings;
use Framework\Utils\Numbers;

/**
 * The Database Count
 */
class Count {

    public string $index     = "";
    public string $table     = "";
    public string $key       = "";
    public bool   $isSum     = false;
    public bool   $isCount   = false;
    public string $value     = "";
    public int    $mult      = 1;

    public string $asKey     = "";
    public string $onTable   = "";
    public string $leftKey   = "";
    public string $rightKey  = "";
    public string $type      = "";
    public bool   $noDeleted = false;

    /** @var mixed|string */
    public mixed  $where     = "";


    /**
     * Creates a new Count instance
     * @param string  $key
     * @param array{} $data
     */
    public function __construct(string $key, array $data) {
        $this->index     = "count-{$key}";
        $this->table     = $data["table"];
        $this->key       = $data["key"];

        $this->isSum     = !empty($data["isSum"]) && $data["isSum"];
        $this->isCount   = empty($data["isSum"])  || !$data["isSum"];
        $this->value     = !empty($data["value"])   ? $data["value"]     : "";
        $this->mult      = !empty($data["mult"])    ? (int)$data["mult"] : 1;

        $this->asKey     = !empty($data["asKey"])   ? $data["asKey"]     : "";
        $this->onTable   = !empty($data["onTable"]) ? $data["onTable"]   : "";
        $this->leftKey   = !empty($data["leftKey"]) ? $data["leftKey"]   : $this->key;
        $this->type      = !empty($data["type"])    ? $data["type"]      : "";
        $this->where     = !empty($data["where"])   ? $data["where"]     : "";
        $this->noDeleted = !empty($data["noDeleted"]) && $data["noDeleted"];
    }



    /**
     * Returns the Count Value
     * @param array{} $data
     * @return mixed
     */
    public function getValue(array $data): mixed {
        $key    = $this->asKey;
        $result = !empty($data[$key]) ? $data[$key] : 0;

        if ($this->type == Field::Float) {
            $result = Numbers::toFloat($result, 3);
        } elseif ($this->type == Field::Price) {
            $result = Numbers::fromCents($result);
        }
        return $result;
    }

    /**
     * Returns the Count Where
     * @return string
     */
    public function getWhere(): string {
        if (empty($this->where) && !$this->noDeleted) {
            return "";
        }
        $result = "WHERE ";
        if ($this->noDeleted) {
            $result .= "isDeleted = 0";
            if (!empty($this->where)) {
                $result .= " AND ";
            }
        }
        if (!empty($this->where)) {
            $result .= Strings::join($this->where, " ");
        }
        return $result;
    }
}
