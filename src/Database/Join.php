<?php
namespace Framework\Database;

use Framework\Database\Factory;
use Framework\Database\Field;
use Framework\Database\Merge;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;

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
     * @param string     $key
     * @param Dictionary $data
     */
    public function __construct(string $key, Dictionary $data) {
        $this->key        = $key;
        $this->table      = Factory::getTableName($data->getString("schema"));
        $this->asTable    = Factory::getTableName($data->getString("asSchema"));
        $this->onTable    = Factory::getTableName($data->getString("onSchema"));
        $this->leftKey    = $data->getString("leftKey", $key);
        $this->rightKey   = $data->getString("rightKey", $key);
        $this->andTable   = Factory::getTableName($data->getString("andSchema"));
        $this->and        = $data->getString("and");
        $this->andKey     = $data->getString("andKey");
        $this->andKeys    = $data->getStrings("andKeys");
        $this->orKeys     = $data->getStrings("orKeys");
        $this->andValue   = $data->getString("andValue");
        $this->andDeleted = $data->hasValue("andDeleted");

        $this->hasPrefix  = $data->hasValue("prefix");
        $this->prefix     = $data->getString("prefix");


        // Creates the Fields
        foreach ($data->getDict("fields") as $fieldKey => $value) {
            $field = new Field($fieldKey, $value, $this->prefix);
            $this->fields[] = $field;

            if ($field->mergeTo !== "") {
                if (!isset($this->merges[$field->mergeTo])) {
                    $mergeKey = $this->hasPrefix ? $this->prefix . ucfirst($field->mergeTo) : $field->mergeTo;
                    $glue     = $data->getString("mergeGlue", " ");
                    $this->merges[$field->mergeTo] = new Merge($mergeKey, $glue);
                }
                $this->merges[$field->mergeTo]->fields[] = $field->prefixName;
            }

            if ($field->defaultTo !== "") {
                $defaultKey = $this->hasPrefix ? $this->prefix . ucfirst($field->defaultTo) : $field->defaultTo;
                if (!isset($this->defaults[$defaultKey])) {
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
            if (!isset($result[$key])) {
                $result[$key] = "";
                foreach ($fields as $fieldKey) {
                    if (!Arrays::isEmpty($data[$fieldKey])) {
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
        $onTable  = $this->onTable !== "" ? $this->onTable : $mainKey;
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
        $onTable = $this->andTable !== "" ? $this->andTable : $asTable;
        $result  = "";

        if ($this->and !== "") {
            $result .= " AND {$this->and}";
        }
        if ($this->andKey !== "") {
            $result .= " AND $asTable.{$this->andKey} = $onTable.{$this->andKey}";
        }
        if (count($this->andKeys) > 0) {
            $parts = [];
            foreach ($this->andKeys as $orKey) {
                $parts[] = "$asTable.{$orKey} = $onTable.{$orKey}";
            }
            $result .= " AND " . implode(" AND ", $parts);
        }
        if (count($this->orKeys) > 0) {
            $parts = [];
            foreach ($this->orKeys as $orKey) {
                $parts[] = "$asTable.{$orKey} = $onTable.{$orKey}";
            }
            $result .= " AND (" . implode(" OR ", $parts) . ")";
        }
        if ($this->andValue !== "") {
            $result .= " AND $asTable.{$this->andValue} = ?";
        }
        if ($this->andDeleted) {
            $result .= " AND $asTable.isDeleted = 0";
        }
        return $result;
    }
}
