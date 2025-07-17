<?php
namespace Framework\Database\Model;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use Attribute;

/**
 * The Count Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Count {

    public string $modelName      = "";
    public string $otherModelName = "";
    public string $fieldName      = "";
    public string $query          = "";
    public int    $decimals       = 2;


    /**
     * The Count Attribute
     * @param string $modelName      Optional.
     * @param string $otherModelName Optional.
     * @param string $fieldName      Optional.
     * @param string $query          Optional.
     * @param int    $decimals       Optional.
     */
    public function __construct(
        string $modelName      = "",
        string $otherModelName = "",
        string $fieldName      = "",
        string $query          = "",
        int    $decimals       = 2,
    ) {
        $this->modelName      = SchemaModel::getBaseModelName($modelName);
        $this->otherModelName = SchemaModel::getBaseModelName($otherModelName);
        $this->fieldName      = $fieldName;
        $this->query          = $query;
        $this->decimals       = $decimals;
    }



    // Used internally when parsing the Model
    public FieldType $type       = FieldType::String;
    public string    $name       = "";
    public bool      $hasDeleted = false;


    /**
     * Creates a Count
     * @param string    $name
     * @param FieldType $type
     * @param string    $modelName
     * @param string    $otherModelName
     * @param string    $fieldName
     * @param string    $query
     * @param int       $decimals
     * @return Count
     */
    public static function create(
        string    $name,
        FieldType $type,
        string    $modelName,
        string    $otherModelName,
        string    $fieldName,
        string    $query,
        int       $decimals,
    ): Count {
        $result = new self($modelName, $otherModelName, $fieldName, $query, $decimals);
        $result->name = $name;
        $result->type = $type;
        return $result;
    }

    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @return Count
     */
    public function setData(string $name, string $typeName): Count {
        $this->name = $name;
        $this->type = FieldType::fromType($typeName);
        return $this;
    }

    /**
     * Sets the Model for the Count
     * @param SchemaModel $relatedModel
     * @return Count
     */
    public function setModel(SchemaModel $relatedModel): Count {
        $this->hasDeleted = $relatedModel->canDelete;
        return $this;
    }



    /**
     * Returns the Expression for the Query
     * @param string $asTable
     * @param string $mainKey
     * @return string
     */
    public function getExpression(string $asTable, string $mainKey): string {
        $name       = $this->name;
        $what       = "COUNT(*)";
        $table      = SchemaModel::getDbTableName($this->modelName);
        $onTable    = $this->otherModelName !== "" ? SchemaModel::getDbTableName($this->otherModelName) : $mainKey;
        $leftKey    = SchemaModel::getDbFieldName($this->fieldName);
        $rightKey   = SchemaModel::getDbFieldName($this->fieldName);
        $groupKey   = "$table.$rightKey";
        $where      = $this->getWhere();

        $select     = "SELECT $groupKey, $what AS $name FROM $table $where GROUP BY $groupKey";
        $expression = "LEFT JOIN ($select) AS $asTable ON ($asTable.$leftKey = $onTable.$rightKey)";
        return $expression;
    }

    /**
     * Returns the Count Where
     * @return string
     */
    private function getWhere(): string {
        if ($this->query === "" && !$this->hasDeleted) {
            return "";
        }

        $query = [ $this->query ];
        if ($this->hasDeleted) {
            $query[] = "isDeleted = 0";
        }
        return "WHERE " . Strings::join($query, " AND ");
    }

    /**
     * Returns the Count select name
     * @param string $joinKey
     * @return string
     */
    public function getSelect(string $joinKey): string {
        return "$joinKey.{$this->name}";
    }

    /**
     * Returns the Count Value
     * @param array<string,mixed> $data
     * @return mixed
     */
    public function getValue(array $data): mixed {
        $key    = $this->name;
        $result = $data[$key] ?? 0;

        if ($this->type === FieldType::Float) {
            $result = Numbers::toFloat($result, $this->decimals);
        }
        return $result;
    }



    /**
     * Returns the Data to build a Count
     * @return array<string,mixed>
     */
    public function toBuildData(): array {
        return [
            "name"           => $this->name,
            "type"           => $this->type,
            "modelName"      => $this->modelName,
            "otherModelName" => $this->otherModelName,
            "fieldName"      => $this->fieldName,
            "query"          => $this->query,
            "decimals"       => $this->decimals,
        ];
    }

    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        $result = [
            "isSum"     => false,
            "schema"    => $this->modelName,
            "key"       => SchemaModel::getDbFieldName($this->fieldName),
            "type"      => "number",
            "noDeleted" => $this->hasDeleted,
        ];
        if ($this->otherModelName !== "") {
            $result["onSchema"] = $this->otherModelName;
        }
        if ($this->query !== "") {
            $result["where"] = Strings::split($this->query, " ");
        }
        return $result;
    }
}
