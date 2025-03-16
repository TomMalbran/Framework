<?php
namespace Framework\Database;

use Framework\Database\Factory;
use Framework\Database\Field;
use Framework\Database\Merge;
use Framework\Utils\Arrays;

/**
 * The Schema Join
 */
class Join {

    public string  $key        = "";
    public string  $table      = "";

    public string  $asTable    = "";
    private string $onTable    = "";
    private string $leftKey    = "";
    private string $rightKey   = "";
    private string $andTable   = "";
    private string $and        = "";
    private string $andKey     = "";
    private string $andValue   = "";
    private bool   $andDeleted = false;

    private bool   $hasPrefix  = false;
    private string $prefix     = "";

    /** @var Field[] */
    public array   $fields     = [];

    /** @var Merge[] */
    public array   $merges     = [];

    /** @var array<string,string[]> */
    public array   $defaults   = [];

    /** @var string[] */
    private array  $andKeys    = [];

    /** @var string[] */
    private array  $orKeys     = [];


    /**
     * Creates a new Join instance
     * @param string              $key
     * @param array<string,mixed> $data
     */
    public function __construct(string $key, array $data) {
        $this->key        = $key;
        $this->table      = Factory::getTableName($data["schema"]);
        $this->asTable    = !empty($data["asSchema"])   ? Factory::getTableName($data["asSchema"]) : "";
        $this->onTable    = !empty($data["onSchema"])   ? Factory::getTableName($data["onSchema"]) : "";
        $this->leftKey    = !empty($data["leftKey"])    ? $data["leftKey"]    : $key;
        $this->rightKey   = !empty($data["rightKey"])   ? $data["rightKey"]   : $key;
        $this->andTable   = !empty($data["andSchema"])  ? Factory::getTableName($data["andSchema"]) : "";
        $this->and        = !empty($data["and"])        ? $data["and"]        : "";
        $this->andKey     = !empty($data["andKey"])     ? $data["andKey"]     : "";
        $this->andKeys    = !empty($data["andKeys"])    ? $data["andKeys"]    : [];
        $this->orKeys     = !empty($data["orKeys"])     ? $data["orKeys"]     : [];
        $this->andValue   = !empty($data["andValue"])   ? $data["andValue"]   : "";
        $this->andDeleted = !empty($data["andDeleted"]) ? $data["andDeleted"] : false;

        $this->hasPrefix  = !empty($data["prefix"]);
        $this->prefix     = !empty($data["prefix"])     ? $data["prefix"]   : "";


        // Creates the Fields
        foreach ($data["fields"] as $key => $value) {
            $field = new Field($key, $value, $this->prefix);
            $this->fields[] = $field;

            if ($field->mergeTo !== "") {
                if (empty($this->merges[$field->mergeTo])) {
                    $key = $this->hasPrefix ? $this->prefix . ucfirst($field->mergeTo) : $field->mergeTo;
                    $this->merges[$field->mergeTo] = new Merge($key, $data);
                }
                $this->merges[$field->mergeTo]->fields[] = $field->prefixName;
            }

            if ($field->defaultTo !== "") {
                $defaultKey = $this->hasPrefix ? $this->prefix . ucfirst($field->defaultTo) : $field->defaultTo;
                if (empty($this->defaults[$defaultKey])) {
                    $this->defaults[$defaultKey] = [];
                }
                $this->defaults[$defaultKey][] = $field->prefixName;
            }
        }
    }



    /**
     * Returns the Values for the given Field
     * @param array<string,mixed> $data
     * @return array<string,mixed>
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
            if (empty($result[$key])) {
                $result[$key] = "";
                foreach ($fields as $fieldKey) {
                    if (!empty($data[$fieldKey])) {
                        $result[$key] = $data[$fieldKey];
                        break;
                    }
                }
            }
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
        $onTable  = $this->onTable ?: $mainKey;
        $leftKey  = $this->leftKey;
        $rightKey = $this->rightKey;
        $and      = $this->getAnd($asTable);

        return "LEFT JOIN `{$this->table}` AS `$asTable` ON ($asTable.$leftKey = $onTable.$rightKey{$and})";
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
        if (!empty($this->andKeys)) {
            $parts = [];
            foreach ($this->andKeys as $orKey) {
                $parts[] = "$asTable.{$orKey} = $onTable.{$orKey}";
            }
            $result .= " AND " . implode(" AND ", $parts);
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
