<?php
namespace Framework\Schema;

use Framework\Schema\Field;
use Framework\Utils\Arrays;

/**
 * The Database Join
 */
class Join {

    public string $key        = "";
    public string $table      = "";
    public string $asTable    = "";
    public string $onTable    = "";
    public string $leftKey    = "";
    public string $rightKey   = "";
    public string $and        = "";
    public string $andKey     = "";
    public string $andValue   = "";
    public string $andTable   = "";
    public bool   $andDeleted = false;

    /** @var Field[] */
    public array  $fields     = [];

    /** @var object[] */
    public array  $merges     = [];

    /** @var array{}[] */
    public array  $defaults   = [];

    /** @var string[] */
    public array  $orKeys    = [];

    public bool   $hasPrefix  = false;
    public string $prefix     = "";


    /**
     * Creates a new Join instance
     * @param string  $key
     * @param array{} $data
     */
    public function __construct(string $key, array $data) {
        $this->key        = $key;
        $this->table      = $data["table"];
        $this->asTable    = !empty($data["asTable"])    ? $data["asTable"]    : "";
        $this->onTable    = !empty($data["onTable"])    ? $data["onTable"]    : "";
        $this->leftKey    = !empty($data["leftKey"])    ? $data["leftKey"]    : $key;
        $this->rightKey   = !empty($data["rightKey"])   ? $data["rightKey"]   : $key;
        $this->and        = !empty($data["and"])        ? $data["and"]        : "";
        $this->andKey     = !empty($data["andKey"])     ? $data["andKey"]     : "";
        $this->orKeys     = !empty($data["orKeys"])     ? $data["orKeys"]     : [];
        $this->andValue   = !empty($data["andValue"])   ? $data["andValue"]   : "";
        $this->andTable   = !empty($data["andTable"])   ? $data["andTable"]   : "";
        $this->andDeleted = !empty($data["andDeleted"]) ? $data["andDeleted"] : false;

        $this->hasPrefix  = !empty($data["prefix"]);
        $this->prefix     = !empty($data["prefix"])     ? $data["prefix"]   : "";


        // Creates the Fields
        if (!empty($data["fields"])) {
            foreach ($data["fields"] as $key => $value) {
                $this->fields[] = new Field($key, $value, $this->prefix);
            }
        } elseif (!empty($data["fieldKeys"])) {
            foreach ($data["fieldKeys"] as $key) {
                $this->fields[] = new Field($key, [ "type" => Field::String ], $this->prefix);
            }
        }

        // Creates the Merges
        foreach ($this->fields as $field) {
            if (!empty($field->mergeTo)) {
                if (empty($this->merges[$field->mergeTo])) {
                    $this->merges[$field->mergeTo] = (object)[
                        "key"    => $this->hasPrefix ? $this->prefix . ucfirst($field->mergeTo) : $field->mergeTo,
                        "glue"   => !empty($data["mergeGlue"]) ? $data["mergeGlue"] : " ",
                        "fields" => [],
                    ];
                }
                $this->merges[$field->mergeTo]->fields[] = $field->prefixName;
            }
            if (!empty($field->defaultTo)) {
                if (empty($this->defaults[$field->defaultTo])) {
                    $this->defaults[$field->defaultTo] = [];
                }
                $this->defaults[$field->defaultTo][] = $field->prefixName;
            }
        }
    }



    /**
     * Returns the Values for the given Field
     * @param array{} $data
     * @return array{}
     */
    public function toValues(array $data): array {
        $result = [];
        foreach ($this->fields as $field) {
            $values = $field->toValues($data);
            $result = array_merge($result, $values);
        }
        foreach ($this->merges as $merge) {
            $result[$merge->key] = Arrays::getValue($data, $merge->fields, $merge->glue);
        }
        foreach ($this->defaults as $key => $fields) {
            $result[$key] = Arrays::getAnyValue($data, $fields, "");
        }
        return $result;
    }

    /**
     * Returns the Expression for the Query
     * @param string $asTable
     * @param string $mainKey
     * @return string
     */
    public function getExpression(string $asTable, string $mainKey): string {
        $table    = "{dbPrefix}{$this->table}";
        $onTable  = $this->onTable ?: $mainKey;
        $leftKey  = $this->leftKey;
        $rightKey = $this->rightKey;
        $and      = $this->getAnd($asTable);

        return "LEFT JOIN $table AS $asTable ON (
            $asTable.$leftKey = $onTable.$rightKey{$and}
        )";
    }

    /**
     * Returns the And for the Query
     * @param string $asTable
     * @return string
     */
    public function getAnd(string $asTable): string {
        $onTable = $this->andTable ?: $asTable;
        $result  = "";
        if (!empty($this->and)) {
            $result .= " AND {$this->and}";
        }
        if (!empty($this->andKey)) {
            $result .= " AND $asTable.{$this->andKey} = $onTable.{$this->andKey}";
        }
        if (!empty($this->orKeys)) {
            $parts = [];
            foreach ($this->orKeys as $orKey) {
                $parts[] = "$asTable.{$orKey} = $onTable.{$orKey}";
            }
            $result .= " AND (" . implode(" OR ", $parts) . ")";
        }
        if (!empty($this->andValue)) {
            $result .= " AND $asTable.{$this->andValue} = ?";
        }
        if ($this->andDeleted) {
            $result .= " AND $asTable.isDeleted = 0";
        }
        return $result;
    }
}
