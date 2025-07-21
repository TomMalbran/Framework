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


    // Name of the column to do the join in the relation Model (this)
    // It can have the name of the Model using a dot "ModelName.fieldName"
    // Multiples keys can be specified using "ModelName.fieldName AND ModelName2.fieldName2"
    // By default the primary key of the Related Model is used
    private string $relationJoin = "";

    // Name of the column to do the join in the owner Model (parent)
    // If there are 2 relations with the same Model, use "AsModelName.fieldName" to give the Model a different name
    // By default the primary key of the Related Model is used
    private string $ownerJoin    = "";


    // By default a prefix is added to the field names but it can be disabled
    private bool  $withPrefix    = true;

    // The prefix to be used for the fields. By default it uses the name of the property
    private string $prefix       = "";

    // Allows to use the 'isDeleted' field of the Model. Is not required when using the fieldNames
    private bool  $withDeleted   = false;



    /**
     * The Relation Attribute
     * @param string[] $fieldNames    Optional.
     * @param string[] $withoutPrefix Optional.
     * @param string   $relationJoin  Optional.
     * @param string   $ownerJoin     Optional.
     * @param boolean  $withPrefix    Optional.
     * @param string   $prefix        Optional.
     * @param boolean  $withDeleted   Optional.
     */
    public function __construct(
        array  $fieldNames    = [],
        array  $withoutPrefix = [],
        string $relationJoin  = "",
        string $ownerJoin     = "",
        bool   $withPrefix    = true,
        string $prefix        = "",
        bool   $withDeleted   = false,
    ) {
        $this->fieldNames    = $fieldNames;
        $this->withoutPrefix = $withoutPrefix;

        $this->relationJoin  = $relationJoin;
        $this->ownerJoin     = $ownerJoin;

        $this->withPrefix    = $withPrefix;
        $this->prefix        = $prefix;
        $this->withDeleted   = $withDeleted;
    }


    // Need to parse the Model to get an SQL Expression like:
    // LEFT JOIN `relationModelName` AS `relationAliasName` ON (relationAliasName.relationFieldName = ownerModelName.ownerFieldName AND relationModelName.andFieldName = andTableName.andFieldName AND andTableName.isDeleted = 1)
    // -

    // Used internally when parsing the Model
    public string       $name              = "";
    public string       $relationModelName = "";
    public string       $relationAliasName = "";
    public string       $relationFieldName = "";
    public string       $ownerModelName    = "";
    public string       $ownerFieldName    = "";

    // Model associated to the type of the Attribute
    public ?SchemaModel $relationModel     = null;

    // Model where the Relation attribute is defined
    public ?SchemaModel $parentModel       = null;

    /** @var Field[] */
    public array $fields = [];



    /**
     * Creates a Relation
     * @param string  $name              Optional.
     * @param string  $relationModelName Optional.
     * @param string  $relationAliasName Optional.
     * @param string  $relationFieldName Optional.
     * @param string  $ownerModelName    Optional.
     * @param string  $ownerFieldName    Optional.
     * @param Field[] $fields            Optional.
     * @return Relation
     */
    public static function create(
        string $name              = "",
        string $relationModelName = "",
        string $relationAliasName = "",
        string $relationFieldName = "",
        string $ownerModelName    = "",
        string $ownerFieldName    = "",
        array  $fields            = [],
    ): Relation {
        $result = new self();
        $result->name              = $name;
        $result->relationModelName = $relationModelName;
        $result->relationAliasName = $relationAliasName;
        $result->relationFieldName = $relationFieldName;
        $result->ownerModelName    = $ownerModelName;
        $result->ownerFieldName    = $ownerFieldName;
        $result->fields            = $fields;
        return $result;
    }

    /**
     * BUILD STEP 1: When parsing the attributes of a Model, set the name of the Relation Model and prefix
     * @param string $relationModelName The Relation Model Name comes from the type of the attribute.
     * @param string $prefix            The prefix is the name of the attribute.
     * @return Relation
     */
    public function setDataFromAttribute(string $relationModelName, string $prefix): Relation {
        $this->relationModelName = $relationModelName;

        if ($this->prefix === "") {
            $this->prefix = $prefix;
        }
        return $this;
    }

    /**
     * BUILD STEP 2: After creating all the models we can get the Relation Model and the Parent Model
     * @param SchemaModel $relationModel The Relation Model is the one associated to the type of the Attribute.
     * @param SchemaModel $parentModel   The Parent Model is the Model where the Relation is defined. It might not be the Owner in the JOIN.
     * @return Relation
     */
    public function setModels(SchemaModel $relationModel, SchemaModel $parentModel): Relation {
        $this->relationModel = $relationModel;
        $this->parentModel   = $parentModel;
        return $this;
    }

    /**
     * BUILD STEP 3: Using the Models from STEP 2, generate the fields of the Relation
     * - The fields come from the Relation Model using the fieldNames or getting all of them
     * @return boolean
     */
    public function generateFields(): bool {
        // At this Build Step the Models must be set
        if ($this->relationModel === null || $this->parentModel === null) {
            return false;
        }

        $withTimestamps = Arrays::contains($this->fieldNames, [ "createdTime", "modifiedTime" ], atLeastOne: true);
        $withDeleted    = $this->withDeleted || Arrays::contains($this->fieldNames, "isDeleted");
        $fields         = $this->relationModel->getFields($withTimestamps, $withDeleted);
        $parentFields   = $this->parentModel->getFieldNames();
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

        return true;
    }

    /**
     * BUILD STEP 4: Sets the Name of the Relation and returns it
     * - The name is the Key of the Relation Model, extracted from the relationJoin or the prefix
     * - The name must be unique inside the parent Model which is done using the otherRelationNames
     * @param string[] $otherRelationNames List of the names of the Relations in the Parent Model
     * @return string
     */
    public function setName(array $otherRelationNames): string {
        // At this Build Step the Models must be set
        if ($this->relationModel === null) {
            return "";
        }

        $result = $this->relationModel->idDbName;
        if ($result === "" || Arrays::contains($otherRelationNames, $result)) {
            $result = $this->getOwnerFieldName();
            $result = SchemaModel::getDbFieldName($result);
        }
        if ($result === "" || Arrays::contains($otherRelationNames, $result)) {
            $result = $this->prefix;
        }

        $this->name = $result;
        return $result;
    }

    /**
     * BUILD STEP 5: Parse the Relation Join to extract some values
     * - RelationModelName was set in STEP 1
     * - RelationAliasName is set if the Relation Join contains a dot with a different Model Name
     * - RelationFieldName can be the RelationModel key or be in the Relation Join
     * @return boolean
     */
    public function parseRelationJoin(): bool {
        // At this Build Step the Models must be set
        if ($this->relationModel === null) {
            return false;
        }

        // If the Relation Join is empty the name of the Relation Field is the ID of the Relation Model
        if ($this->relationJoin === "") {
            $this->relationFieldName = $this->relationModel->idName;
            return true;
        }

        // If the Relation Join does not contain a dot, the name of the Relation Field is the Relation Join
        if (!Strings::contains($this->relationJoin, ".")) {
            $this->relationFieldName = $this->relationJoin;
            return true;
        }

        // If the Relation Join contains a dot: "ModelName.fieldName"
        // If the ModelName is different from the Relation Model Name, then is an alias
        [ $relationModelName, $relationFieldName ] = Strings::split($this->relationJoin, ".");
        if ($this->relationModelName !== $relationModelName) {
            $this->relationAliasName = $relationModelName;
        }
        $this->relationFieldName = $relationFieldName;
        return true;
    }

    /**
     * BUILD STEP 6: Parse the Owner Join to extract some values
     * - OwnerModelName is extracted if there is an Owner Join with a dot
     * - RelationAliasName is set if the Relation Join contains a dot with a different Model Name
     * - RelationFieldName can be the RelationModel key or be in the Relation Join
     * @return boolean
     */
    public function parseOwnerJoin(): bool {
        // At this Build Step the Models must be set
        if ($this->relationModel === null) {
            return false;
        }

        // At this Step we can only parse the Owner Join if it is not empty and contains a dot
        if ($this->ownerJoin === "" || !Strings::contains($this->ownerJoin, ".")) {
            return false;
        }

        // The Owner Join is in the format "ModelName.fieldName" and both belong to the Owner Model
        // The Owner Join might also contain an "AND" to specify more fields
        $ownerJoin = Strings::substringBefore($this->ownerJoin, " AND ");
        [ $this->ownerModelName, $this->ownerFieldName ] = Strings::split($ownerJoin, ".");
        return true;
    }

    /**
     * Tries to set the Owner Model Name using the other Relation
     * @param Relation $otherRelation
     * @return bool
     */
    public function setOwnerModelName(Relation $otherRelation): bool {
        if ($this === $otherRelation || $otherRelation->relationModel === null) {
            return false;
        }
        foreach ($otherRelation->relationModel->fields as $field) {
            if ($field->name === $this->getOwnerKey()) {
                $this->ownerModelName = $otherRelation->relationModel->name;
                return true;
            }
        }
        return false;
    }



    /**
     * Returns the fields from the Model
     * @return Field[]
     */
    public function getFields(): array {
        if ($this->relationModel === null) {
            return [];
        }

        $withTimestamps = Arrays::contains($this->fieldNames, [ "createdTime", "modifiedTime" ], atLeastOne: true);
        $withDeleted    = $this->withDeleted || Arrays::contains($this->fieldNames, "isDeleted");
        return $this->relationModel->getFields($withTimestamps, $withDeleted);
    }

    /**
     * Returns the Name of the Owner Key
     * @return string
     */
    public function getOwnerKey(): string {
        $ownerFieldName = $this->getOwnerFieldName();
        if ($ownerFieldName !== "") {
            return $ownerFieldName;
        }
        if ($this->relationModel !== null) {
            return $this->relationModel->idName;
        }
        return "";
    }

    /**
     * Returns the Name of the Table
     * @param bool $useOwnerModel Optional.
     * @return string
     */
    public function getDbTableName(bool $useOwnerModel = false): string {
        if ($useOwnerModel) {
            $ownerTableName = $this->getOwnerTableName();
            if ($ownerTableName !== "") {
                return $ownerTableName;
            }
        }
        return SchemaModel::getDbTableName($this->relationModelName);
    }



    /**
     * Returns the Name of the Relation Model
     * @return string
     */
    public function getRelationModelName(): string {
        if ($this->ownerModelName !== "") {
            return $this->ownerModelName;
        }

        $parts = Strings::split($this->relationJoin, " AND ");
        if (isset($parts[0]) && Strings::contains($parts[0], ".")) {
            $result = Strings::substringBefore($parts[0], ".");
            if ($this->relationModelName !== $result) {
                return $result;
            }
        }
        return "";
    }

    /**
     * Returns the Name of the Relation Field
     * @return string
     */
    public function getRelationFieldName(): string {
        $result = Strings::substringBefore($this->relationJoin, " AND ");
        if (Strings::contains($result, ".")) {
            return Strings::substringAfter($result, ".");
        }
        return $result;
    }



    /**
     * Returns the Name of the Owner Model
     * @return string
     */
    public function getOwnerModelName(): string {
        if (Strings::contains($this->ownerJoin, ".")) {
            $result = Strings::substringBefore($this->ownerJoin, ".");
            if ($this->relationModelName !== $result) {
                return $result;
            }
        }
        return "";
    }

    /**
     * Returns the Name of the Owner Table
     * @return string
     */
    public function getOwnerTableName(): string {
        $result = $this->getOwnerModelName();
        if ($result !== "") {
            return SchemaModel::getDbTableName($result);
        }
        return "";
    }

    /**
     * Returns the Name of the Owner Field
     * @return string
     */
    public function getOwnerFieldName(): string {
        if (Strings::contains($this->ownerJoin, ".")) {
            return Strings::substringAfter($this->ownerJoin, ".");
        }
        return $this->ownerJoin;
    }



    /**
     * Returns the Name of the And Model
     * @return string
     */
    public function getAndModelName(): string {
        $parts = Strings::split($this->ownerJoin, " AND ");
        if (isset($parts[1]) && Strings::contains($parts[1], ".")) {
            return Strings::substringBefore($parts[1], ".");
        }
        return "";
    }

    /**
     * Returns the Name of And Table
     * @return string
     */
    public function getAndTableName(): string {
        $result = $this->getAndModelName();
        if ($result !== "") {
            return SchemaModel::getDbTableName($result);
        }
        return "";
    }

    /**
     * Returns the Names of the And Fields
     * @return string[]
     */
    public function getAndFieldNames(): array {
        $andParts = Strings::split($this->ownerJoin, " AND ");
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
        if (Strings::contains($this->ownerJoin, " AND ") && Strings::endsWith($this->ownerJoin, " = ?")) {
            return trim(Strings::substringBetween($this->ownerJoin, " AND ", " = ?"));
        }
        return "";
    }

    /**
     * Returns true if there a isDeleted
     * @return bool
     */
    public function getAndIsDeleted(): bool {
        return Strings::contains($this->ownerJoin, "isDeleted");
    }



    /**
     * Returns the Expression for the Query
     * @param string $asTableName
     * @param string $parentTableName
     * @return string
     */
    public function getExpression(string $asTableName, string $parentTableName): string {
        $tableName         = $this->getDbTableName();
        $relationTableName = SchemaModel::getDbTableName($this->relationModelName);
        $relationFieldName = $this->relationFieldName;
        $ownerFieldName    = $this->getOwnerFieldName();

        $asTable           = $tableName !== $asTableName ? " AS `$asTableName`" : "";
        $relationTable     = $relationTableName !== "" ? $relationTableName : $parentTableName;
        $ownerColumn       = SchemaModel::getDbFieldName($ownerFieldName);
        $relationColumn    = SchemaModel::getDbFieldName($relationFieldName);
        $and               = $this->getAndExpression($asTableName);

        return "LEFT JOIN `{$tableName}`{$asTable} ON ($asTableName.$relationColumn = $ownerTableName.$ownerColumn{$and})";
    }

    /**
     * Returns the And Expression for the Query
     * @param string $asTableName
     * @return string
     */
    public function getAndExpression(string $asTableName): string {
        $andTableName  = $this->getAndTableName();
        $andFieldNames = $this->getAndFieldNames();
        $andValue      = $this->getAndValue();
        $andIsDeleted  = $this->getAndIsDeleted();

        $onTableName   = $andTableName !== "" ? $andTableName : $asTableName;
        $result        = "";

        if (count($andFieldNames) > 0) {
            $parts = [];
            foreach ($andFieldNames as $andFieldName) {
                $columnName = SchemaModel::getDbFieldName($andFieldName);
                $parts[]    = "$asTableName.{$columnName} = $onTableName.{$columnName}";
            }
            $result .= " AND " . implode(" AND ", $parts);
        }
        if ($andValue !== "") {
            $columnName = SchemaModel::getDbFieldName($andValue);
            $result    .= " AND $asTableName.{$columnName} = ?";
        }
        if ($andIsDeleted) {
            $result .= " AND $asTableName.isDeleted = 0";
        }
        return $result;
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
        return $result;
    }

    /**
     * Returns the Data to build a Relation
     * @return array<string,mixed>
     */
    public function toBuildData(): array {
        $fields = [];
        foreach ($this->fields as $field) {
            $fieldData = [
                "name"       => $field->name,
                "dbName"     => $field->dbName,
                "prefixName" => $field->prefixName,
                "type"       => $field->type,
            ];
            if ($field->decimals !== 2) {
                $fieldData["decimals"] = $field->decimals;
            }
            if ($field->filePath !== "") {
                $fieldData["filePath"] = $field->filePath;
            }
            $fields[] = $fieldData;
        }

        return [
            "name"              => $this->name,
            "relationModelName" => $this->relationModelName,
            "relationAliasName" => $this->relationAliasName,
            "relationFieldName" => $this->relationFieldName,
            "ownerModelName"    => $this->ownerModelName,
            "ownerFieldName"    => $this->ownerFieldName,
            "fields"            => $fields,
        ];
    }

    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        if ($this->relationModel === null) {
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
            "schema" => $this->relationModelName,
            "fields" => $fields,
        ];
        if ($this->withPrefix) {
            $result["prefix"] = $this->prefix;
        }

        // Parse the Joins
        $andModelName  = $this->getAndModelName();
        $andFieldNames = $this->getAndFieldNames();
        $andValue      = $this->getAndValue();
        $andDeleted    = $this->getAndIsDeleted();

        if ($this->relationAliasName !== "") {
            $result["asSchema"] = $this->relationAliasName;
        }
        if ($this->relationFieldName !== "") {
            $result["leftKey"] = SchemaModel::getDbFieldName($this->relationFieldName);
        }

        if ($this->ownerModelName !== "") {
            $result["onSchema"] = $this->ownerModelName;
        }
        if ($this->ownerFieldName !== "") {
            $result["rightKey"] = SchemaModel::getDbFieldName($this->ownerFieldName);
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
