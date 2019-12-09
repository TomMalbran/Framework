<?php
namespace Framework\Schema;

use Framework\Schema\Field;
use Framework\Utils\Numbers;

/**
 * The Database Count
 */
class Count {

    public $index     = "";
    public $table     = "";
    public $key       = "";
    public $isSum     = false;
    public $isCount   = false;
    public $value     = "";
    public $mult      = 1;
    
    public $asKey     = "";
    public $onTable   = "";
    public $leftKey   = "";
    public $rightKey  = "";
    public $type      = "";
    public $noDeleted = false;


    /**
     * Creates a new Count instance
     * @param string $key
     * @param array  $data
     */
    public function __construct(string $key, array $data) {
        $this->index     = "count-{$key}";
        $this->table     = $data["table"];
        $this->key       = $data["key"];

        $this->isSum     = !empty($data["isSum"]) && $data["isSum"];
        $this->isCount   = empty($data["isSum"])  || !$data["isSum"];
        $this->value     = !empty($data["value"])    ? $data["value"]     : "";
        $this->mult      = !empty($data["mult"])     ? (int)$data["mult"] : 1;
        
        $this->asKey     = !empty($data["asKey"])    ? $data["asKey"]     : "";
        $this->onTable   = !empty($data["onTable"])  ? $data["onTable"]   : "";
        $this->leftKey   = !empty($data["leftKey"])  ? $data["leftKey"]   : $this->key;
        $this->type      = !empty($data["type"])     ? $data["type"]      : "";
        $this->noDeleted = !empty($data["noDeleted"]) && $data["noDeleted"];
    }



    /**
     * Returns the Count Value
     * @param array $data
     * @return mixed
     */
    public function getValue(array $data) {
        $key    = $this->asKey;
        $result = !empty($data[$key]) ? $data[$key] : 0;

        if ($this->type == Field::Float) {
            $result = Numbers::toFloat($result, 3);
        } elseif ($this->type == Field::Price) {
            $result = Numbers::fromCents($result);
        }
        return $result;
    }
}
