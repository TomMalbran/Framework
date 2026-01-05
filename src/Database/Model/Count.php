<?php
namespace Framework\Database\Model;

use Framework\Database\SchemaModel;
use Framework\Utils\Strings;

use Attribute;

/**
 * The Count Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Count {

    // The name of the Model where the count is applied
    public string $modelName      = "";

    // The name of the Model used in the Join
    // If empty, the join is done with the Model where the Count is defined
    public string $otherModelName = "";

    // The name of the Field used in the Count
    // If empty, the field is the ID field of the Model where the Count is defined
    public string $fieldName      = "";

    // An additional Query to filter the Count
    public string $query          = "";


    /**
     * The Count Attribute
     * @phpstan-param class-string|null $modelName
     * @phpstan-param class-string|null $otherModelName
     *
     * @param string|null $modelName      Optional.
     * @param string|null $otherModelName Optional.
     * @param string      $fieldName      Optional.
     * @param string      $query          Optional.
     */
    public function __construct(
        ?string $modelName      = null,
        ?string $otherModelName = null,
        string  $fieldName       = "",
        string  $query           = "",
    ) {
        $this->modelName      = SchemaModel::getBaseModelName($modelName);
        $this->otherModelName = SchemaModel::getBaseModelName($otherModelName);
        $this->fieldName      = $fieldName;
        $this->query          = $query;
    }



    // Used internally when parsing the Model
    public string $name       = "";
    public bool   $hasDeleted = false;


    /**
     * Creates a Count
     * @param string  $name
     * @param string  $modelName
     * @param string  $otherModelName
     * @param string  $fieldName
     * @param string  $query
     * @param boolean $hasDeleted
     * @return Count
     */
    public static function create(
        string $name,
        string $modelName,
        string $otherModelName,
        string $fieldName,
        string $query,
        bool   $hasDeleted,
    ): Count {
        $result = new self();
        $result->name           = $name;
        $result->modelName      = $modelName;
        $result->otherModelName = $otherModelName;
        $result->fieldName      = $fieldName;
        $result->query          = $query;
        $result->hasDeleted     = $hasDeleted;
        return $result;
    }

    /**
     * Sets the Data from the Model
     * @param string $name
     * @return Count
     */
    public function setData(string $name): Count {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the Model for the Count
     * @param SchemaModel $schemaModel
     * @param SchemaModel $parentModel
     * @return Count
     */
    public function setModel(SchemaModel $schemaModel, SchemaModel $parentModel): Count {
        $this->hasDeleted = $schemaModel->canDelete;

        if ($this->fieldName === "") {
            $this->fieldName = $parentModel->idName;
        }
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
        $query = [];
        if ($this->query !== "") {
            $query[] = $this->query;
        }
        if ($this->hasDeleted) {
            $query[] = "isDeleted = 0";
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
        return $result;
    }



    /**
     * Returns the Data to build a Count
     * @return array<string,mixed>
     */
    public function toBuildData(): array {
        return [
            "name"           => $this->name,
            "modelName"      => $this->modelName,
            "otherModelName" => $this->otherModelName,
            "fieldName"      => $this->fieldName,
            "query"          => $this->query,
            "hasDeleted"     => $this->hasDeleted,
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
