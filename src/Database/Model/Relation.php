<?php
namespace Framework\Database\Model;

use Framework\Database\SchemaModel;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use Attribute;

/**
 * The Relation Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Relation {

    // By default all fields are returned but this allows to specify a list of field names
    /** @var string[] */
    private array $fieldNames    = [];

    // Allows to specify which fields should be returned without the prefix
    /** @var string[] */
    private array $withoutPrefix = [];


    // Name of the column to do the join in this Model
    // If there are 2 relations with the same Model, use "AsModelName.fieldName" to give the Model a different name
    // By default the primary key of the Related Model is used
    private string $myJoin       = "";

    // Name of the column to do the join in the other Model
    // It can have the name of the Model using a dot "ModelName.fieldName"
    // Multiples keys can be specified using "ModelName.fieldName AND ModelName2.fieldName2"
    // By default the primary key of the Related Model is used
    private string $otherJoin    = "";


    // By default a prefix is added to the field names but it can be disabled
    private bool  $withPrefix    = true;

    // The prefix to be used for the fields. By default it uses the name of the property
    private string $prefix       = "";

    // Allows to use the 'isDeleted' field of the Model. Is not required when using the fieldNames
    private bool  $withDeleted   = false;



    // Used internally when parsing the Model
    public ?SchemaModel $relatedModel   = null;
    public ?SchemaModel $parentModel    = null;
    public string       $modelName      = "";
    public string       $otherModelName = "";

    /** @var Field[] */
    public array $fields = [];



    /**
     * The Relation Attribute
     * @param string[] $fieldNames    Optional.
     * @param string[] $withoutPrefix Optional.
     * @param string   $myJoin        Optional.
     * @param string   $otherJoin     Optional.
     * @param boolean  $withPrefix    Optional.
     * @param string   $prefix        Optional.
     * @param boolean  $withDeleted   Optional.
     */
    public function __construct(
        array  $fieldNames    = [],
        array  $withoutPrefix = [],
        string $myJoin        = "",
        string $otherJoin     = "",
        bool   $withPrefix    = true,
        string $prefix        = "",
        bool   $withDeleted   = false,
    ) {
        $this->fieldNames    = $fieldNames;
        $this->withoutPrefix = $withoutPrefix;

        $this->myJoin        = $myJoin;
        $this->otherJoin     = $otherJoin;

        $this->withPrefix    = $withPrefix;
        $this->prefix        = $prefix;
        $this->withDeleted   = $withDeleted;
    }



    /**
     * Sets the Data from the Model
     * @param string $modelName
     * @param string $prefix
     * @return Relation
     */
    public function setData(string $modelName, string $prefix): Relation {
        if ($this->modelName === "") {
            $this->modelName = $modelName;
        }
        if ($this->prefix === "") {
            $this->prefix = $prefix;
        }
        return $this;
    }

    /**
     * Sets the Model for the Relation and creates the fields
     * @param SchemaModel $relatedModel
     * @param SchemaModel $parentModel
     * @return Relation
     */
    public function setModel(SchemaModel $relatedModel, SchemaModel $parentModel): Relation {
        $this->relatedModel = $relatedModel;
        $this->parentModel  = $parentModel;

        $withTimestamps = Arrays::contains($this->fieldNames, [ "createdTime", "modifiedTime" ], atLeastOne: true);
        $withDeleted    = $this->withDeleted || Arrays::contains($this->fieldNames, "isDeleted");
        $fields         = $relatedModel->getFields($withTimestamps, $withDeleted);
        $parentFields   = $parentModel->getFieldNames();
        $hasFields      = count($this->fieldNames) > 0;

        foreach ($fields as $field) {
            $prefixName = $this->prefix . Strings::upperCaseFirst($field->name);
            $fieldName  = $this->withPrefix ? $prefixName : $field->name;

            if ($hasFields) {
                if (!Arrays::contains($this->fieldNames, $field->name)) {
                    continue;
                }
            } else {
                if ($field->isID || Arrays::contains($parentFields, $fieldName)) {
                    continue;
                }
            }

            if ($this->withPrefix && !Arrays::contains($parentFields, $field->name) && (
                $field->isSchemaID() ||
                Strings::startsWith($field->name, $this->prefix) ||
                Arrays::contains($this->withoutPrefix, $field->name)
            )) {
                $fieldName = $field->name;
            }

            $newField = clone $field;
            $newField->prefixName = $fieldName;
            $this->fields[] = $newField;
        }

        return $this;
    }



    /**
     * Returns the fields from the Model
     * @return Field[]
     */
    public function getFields(): array {
        if ($this->relatedModel === null) {
            return [];
        }

        $withTimestamps = Arrays::contains($this->fieldNames, [ "createdTime", "modifiedTime" ], atLeastOne: true);
        $withDeleted    = $this->withDeleted || Arrays::contains($this->fieldNames, "isDeleted");
        return $this->relatedModel->getFields($withTimestamps, $withDeleted);
    }

    /**
     * Returns the Name of the ID Field
     * @param string[] $otherKeys
     * @return string
     */
    public function getKey(array $otherKeys): string {
        $result = "";
        if ($this->relatedModel !== null) {
            $result = $this->relatedModel->idKey;
        }
        if ($result === "" || Arrays::contains($otherKeys, $result)) {
            $result = $this->getMyFieldName();
            $result = SchemaModel::getDbFieldName($result);
        }
        if ($result === "" || Arrays::contains($otherKeys, $result)) {
            $result = $this->prefix;
        }
        return $result;
    }

    /**
     * Returns the Name of the ID Field
     * @return string
     */
    public function getMyKey(): string {
        $myFieldName = $this->getMyFieldName();
        if ($myFieldName !== "") {
            return $myFieldName;
        }
        if ($this->relatedModel !== null) {
            return $this->relatedModel->idName;
        }
        return "";
    }

    /**
     * Returns the Name of the Table
     * @return string
     */
    public function getDbTableName(): string {
        $modelName = $this->modelName;
        if ($this->getMyModelName() !== "") {
            $modelName = $this->getMyModelName();
        }
        return SchemaModel::getDbTableName($modelName);
    }

    /**
     * Returns the Name of the My Model
     * @return string
     */
    public function getMyModelName(): string {
        if (Strings::contains($this->myJoin, ".")) {
            $modelName = Strings::substringBefore($this->myJoin, ".");
            if ($this->modelName !== $modelName) {
                return $modelName;
            }
        }
        return "";
    }

    /**
     * Returns the Name of the My Field
     * @return string
     */
    public function getMyFieldName(): string {
        if (Strings::contains($this->myJoin, ".")) {
            return Strings::substringAfter($this->myJoin, ".");
        }
        return $this->myJoin;
    }

    /**
     * Returns the Name of the Other Model
     * @return string
     */
    public function getOtherModelName(): string {
        if ($this->otherModelName !== "") {
            return $this->otherModelName;
        }

        $parts = Strings::split($this->otherJoin, " AND ");
        if (isset($parts[0]) && Strings::contains($parts[0], ".")) {
            $modelName = Strings::substringBefore($parts[0], ".");
            if ($this->modelName !== $modelName) {
                return $modelName;
            }
        }
        return "";
    }

    /**
     * Returns the Name of the Other Field
     * @return string
     */
    public function getOtherFieldName(): string {
        $otherJoin = Strings::substringBefore($this->otherJoin, " AND ");
        if (Strings::contains($otherJoin, ".")) {
            return Strings::substringAfter($otherJoin, ".");
        }
        return $otherJoin;
    }

    /**
     * Returns the Name of the And Model
     * @return string
     */
    public function getAndModelName(): string {
        $parts = Strings::split($this->otherJoin, " AND ");
        if (isset($parts[1]) && Strings::contains($parts[1], ".")) {
            return Strings::substringBefore($parts[1], ".");
        }
        return "";
    }

    /**
     * Returns the Names of the And Fields
     * @return string[]
     */
    public function getAndFieldNames(): array {
        $andParts = Strings::split($this->otherJoin, " AND ");
        $result   = [];
        for ($i = 1; $i < count($andParts); $i++) {
            if (Strings::contains($andParts[$i], ".")) {
                $result[] = trim(Strings::substringAfter($andParts[$i], "."));
            }
        }
        return count($result) > 0 ? $result : [];
    }

    /**
     * Returns the Name of the And Value
     * @return string
     */
    public function getAndValue(): string {
        if (Strings::contains($this->otherJoin, " AND ") && Strings::endsWith($this->otherJoin, " = ?")) {
            return trim(Strings::substringBetween($this->otherJoin, " AND ", " = ?"));
        }
        return "";
    }

    /**
     * Returns true if there a isDeleted
     * @return bool
     */
    public function getAndIsDeleted(): bool {
        return Strings::contains($this->otherJoin, "isDeleted");
    }



    /**
     * Returns the Expression for the Query
     * @param string $asTable
     * @param string $mainKey
     * @return string
     */
    public function getExpression(string $asTable, string $mainKey): string {
        // $onTable  = $this->onTable !== "" ? $this->onTable : $mainKey;
        // $leftKey  = $this->leftKey;
        // $rightKey = $this->rightKey;
        // $and      = $this->getAnd($asTable);

        // return "LEFT JOIN `{$this->table}` AS `$asTable` ON ($asTable.$leftKey = $onTable.$rightKey{$and})";
        return "";
    }



    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        if ($this->relatedModel === null) {
            return [];
        }

        $fields = [];
        foreach ($this->fields as $field) {
            $fieldData = [ "type" => $field->type->getName() ];
            if ($this->withPrefix && $field->name === $field->prefixName) {
                $fieldData["noPrefix"] = true;
            }
            if ($field->decimals !== 2) {
                $fieldData["decimals"] = $field->decimals;
            }
            $fields[$field->dbName] = $fieldData;
        }

        $result = [
            "schema" => $this->modelName,
            "fields" => $fields,
        ];
        if ($this->withPrefix) {
            $result["prefix"] = $this->prefix;
        }

        // Parse the Joins
        $myModelName    = $this->getMyModelName();
        $myFieldName    = $this->getMyFieldName();
        $otherModelName = $this->getOtherModelName();
        $otherFieldName = $this->getOtherFieldName();
        $andModelName   = $this->getAndModelName();
        $andFieldNames  = $this->getAndFieldNames();
        $andValue       = $this->getAndValue();
        $andDeleted     = $this->getAndIsDeleted();

        if ($myModelName !== "") {
            $result["asSchema"] = $myModelName;
        }
        if ($otherModelName !== "") {
            $result["onSchema"] = $otherModelName;
        }
        if ($myFieldName !== "") {
            $result["rightKey"] = SchemaModel::getDbFieldName($myFieldName);
        }
        if ($otherFieldName !== "") {
            $result["leftKey"] = SchemaModel::getDbFieldName($otherFieldName);
        }

        if ($andModelName !== "") {
            $result["andSchema"] = $andModelName;
        }
        if (count($andFieldNames) === 1) {
            $result["andKey"] = SchemaModel::getDbFieldName($andFieldNames[0]);
        } elseif (count($andFieldNames) > 1) {
            $result["andKeys"] = array_map(fn($key) => SchemaModel::getDbFieldName($key), $andFieldNames);
        }
        if ($andValue !== "") {
            $result["andValue"] = SchemaModel::getDbFieldName($andValue);
        }
        if ($andDeleted) {
            $result["andDeleted"] = true;
        }
        return $result;
    }
}
