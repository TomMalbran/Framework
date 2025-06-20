<?php
namespace Framework\Database;

use Framework\Database\SchemaFactory;
use Framework\Database\Field;
use Framework\Utils\Dictionary;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * The Schema Count
 */
class Count {

    public  Field  $field;
    private bool   $isSum     = false;
    public  string $key       = "";
    private string $value     = "";
    private int    $mult      = 1;

    private string $table     = "";
    private string $onTable   = "";
    private string $leftKey   = "";
    private string $rightKey  = "";
    private bool   $noDeleted = false;

    /** @var string[] */
    private array  $where     = [];


    /**
     * Creates a new Count instance
     * @param string     $key
     * @param Dictionary $data
     */
    public function __construct(string $key, Dictionary $data) {
        $whereKey = $data->getString("key", $key);

        $this->field     = new Field($key, $data);
        $this->key       = $key;

        $this->isSum     = $data->hasValue("isSum");
        $this->value     = $data->getString("value");
        $this->mult      = $data->getInt("mult", default: 1);

        $this->table     = SchemaFactory::getTableName($data->getString("schema"));
        $this->onTable   = SchemaFactory::getTableName($data->getString("onSchema"));
        $this->rightKey  = $data->getString("rightKey", $whereKey);
        $this->leftKey   = $data->getString("leftKey", $whereKey);
        $this->where     = $data->getStrings("where");
        $this->noDeleted = $data->hasValue("noDeleted");
    }



    /**
     * Returns the Expression for the Query
     * @param string $asTable
     * @param string $mainKey
     * @return string
     */
    public function getExpression(string $asTable, string $mainKey): string {
        $key        = $this->key;
        $what       = $this->isSum ? "SUM({$this->mult} * {$this->value})" : "COUNT(*)";
        $table      = $this->table;
        $onTable    = $this->onTable !== "" ? $this->onTable : $mainKey;
        $leftKey    = $this->leftKey;
        $rightKey   = $this->rightKey;
        $groupKey   = "$table.$rightKey";
        $where      = $this->getWhere();
        $select     = "SELECT $groupKey, $what AS $key FROM $table $where GROUP BY $groupKey";
        $expression = "LEFT JOIN ($select) AS $asTable ON ($asTable.$leftKey = $onTable.$rightKey)";
        return $expression;
    }

    /**
     * Returns the Count Where
     * @return string
     */
    private function getWhere(): string {
        if (count($this->where) === 0 && !$this->noDeleted) {
            return "";
        }

        $query = [];
        if ($this->noDeleted) {
            $query[] = "isDeleted = 0";
        }

        $total = count($this->where);
        if ($total % 3 === 0) {
            for ($i = 0; $i < $total; $i += 3) {
                $query[] = "{$this->where[$i]} {$this->where[$i + 1]} {$this->where[$i + 2]}";
            }
        }

        if (count($query) === 0) {
            return "";
        }
        return "WHERE " . Strings::join($query, " AND ");
    }

    /**
     * Returns the Count select name
     * @param string $joinKey
     * @return string
     */
    public function getSelect(string $joinKey): string {
        return "$joinKey.{$this->key}";
    }



    /**
     * Returns the Count Value
     * @param array<string,mixed> $data
     * @return mixed
     */
    public function getValue(array $data): mixed {
        $key    = $this->key;
        $result = $data[$key] ?? 0;

        if ($this->field->type === Field::Float) {
            $result = Numbers::toFloat($result, $this->field->decimals);
        }
        return $result;
    }
}
