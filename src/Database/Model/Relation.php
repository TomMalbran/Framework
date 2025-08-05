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

    // The joins are in the form of:
    // - relationJoin: Join associated with the Model used as the type of the attribute. It can be in the form of:
    //   - `relationFieldName` where only the field name is used
    //   - `relationModelName.relationFieldName` where there is a Model Name and a field name
    //   - `relationAliasName.relationFieldName` where the Model Name as an alias and the field name
    // - ownerJoin: Join associates with the Model that owns a Column referencing the Relation. It can be in the form of:
    //   - `ownerFieldName` where only the field name is used and the Model is infered
    //   - `ownerModelName.ownerFieldName` where there is a Model Name and a field name
    //   - `ownerModelName.ownerFieldName AND andModelName.andFieldName` where there is a Model Name, a field name and an AND extra condition
    //   - `ownerModelName.ownerFieldName AND andTableName.isDeleted = 1` where there is a Model Name, a field name and an AND extra condition
    //   - This last 2 forms can be combines and there can be multiple AND conditions but all using the same Model Name

    // We then extract the data from:
    // - relationModelName: Name of the Model associated to the type of the attribute.
    //     This is the Model that has the fields defined.
    // - relationAliasName: If there are 2 relations with the same Model, an alias name for the Relation Model is required.
    //     The alias name is set in the `relationJoin` before the dot.
    // - relationFieldName: Name of the field in the Relation Model used to do the join.
    //     - It can be inferred as the ID of the Relation Model.
    //     - It can be set in the `relationJoin` after the dot.
    // - ownerModelName: Name of the Model to perform the join with. It can be:
    //    - The Model where the Relation is defined if there is an attribute as the key of the Relation Model.
    //    - A Model from another Relation in the Model where the Relation is defined.
    //    - It can come from `ownerJoin` as the Model Name before the dot.
    // - ownerFieldName: Name of the field in the Owner Model used to do the join.
    //     - It can be inferred as the ID of the Relation Model.
    //     - It can be set in the `ownerJoin` after the first dot.
    // - ownerAndQuery: If the `ownerJoin` contains an AND condition, this fields contains what comes after the AND.



    // Used internally when parsing the Model
    public string $relationModelName  = "";
    public string $relationAliasName  = "";
    public string $relationFieldName  = "";
    public string $ownerModelName     = "";
    public string $ownerFieldName     = "";
    public string $ownerAndQuery      = "";

    // Model associated to the type of the Attribute
    public ?SchemaModel $relationModel = null;

    // Model where the Relation attribute is defined
    public ?SchemaModel $parentModel   = null;

    /** @var Field[] */
    public array $fields = [];



    /**
     * Creates a Relation
     * @param string  $relationModelName
     * @param string  $relationAliasName
     * @param string  $relationFieldName
     * @param string  $ownerModelName
     * @param string  $ownerFieldName
     * @param string  $ownerAndQuery
     * @param Field[] $fields
     * @return Relation
     */
    public static function create(
        string $relationModelName,
        string $relationAliasName,
        string $relationFieldName,

        string $ownerModelName,
        string $ownerFieldName,
        string $ownerAndQuery,

        array  $fields,
    ): Relation {
        $result = new self();
        $result->relationModelName  = $relationModelName;
        $result->relationAliasName  = $relationAliasName;
        $result->relationFieldName  = $relationFieldName;

        $result->ownerModelName     = $ownerModelName;
        $result->ownerFieldName     = $ownerFieldName;
        $result->ownerAndQuery      = $ownerAndQuery;

        $result->fields             = $fields;
        return $result;
    }

    /**
     * BUILD STEP 1: When parsing the attributes of a Model, set the name of the Relation Model and prefix
     * @param string $relationModelName The Relation Model Name comes from the type of the attribute.
     * @param string $prefix            The prefix is the name of the attribute.
     * @return boolean
     */
    public function setDataFromAttribute(string $relationModelName, string $prefix): bool {
        $this->relationModelName = $relationModelName;

        if ($this->prefix === "") {
            $this->prefix = $prefix;
        }
        return true;
    }

    /**
     * BUILD STEP 2: Parse the Relation Join to extract some values
     * Skip this step is the relationJoin is empty
     * - relationAliasName is set if the Relation Join contains a dot with a different Model Name
     * - relationFieldName can be the RelationModel key or be in the Relation Join
     * @return boolean
     */
    public function parseRelationJoin(): bool {
        if ($this->relationJoin === "") {
            return false;
        }

        // If the Relation Join does not contain a dot, the name of the Relation Field is the Relation Join
        if (!Strings::contains($this->relationJoin, ".")) {
            $this->relationFieldName = $this->relationJoin;
            return true;
        }

        // If the Relation Join contains a dot: "ModelName.fieldName"
        // If the ModelName is different from the Relation Model Name, then is an alias
        [ $relationModelName, $relationFieldName ] = Strings::split($this->relationJoin, ".");
        $relationModelName = SchemaModel::getBaseModelName($relationModelName);
        if ($this->relationModelName !== $relationModelName) {
            $this->relationAliasName = $relationModelName;
        }
        $this->relationFieldName = $relationFieldName;
        return true;
    }

    /**
     * BUILD STEP 3: Parse the Owner Join to extract some values
     * Skip this step is the ownerJoin is empty
     * - ownerModelName is extracted if there is an Owner Join with a dot
     * - ownerFieldName can be the RelationModel key or be in the Relation Join
     * @return boolean
     */
    public function parseOwnerJoin(): bool {
        if ($this->ownerJoin === "") {
            return false;
        }

        // Only considere the part before the "AND" if it exists and save the rest
        $ownerJoin = $this->ownerJoin;
        if (Strings::contains($this->ownerJoin, " AND ")) {
            $ownerJoin           = trim(Strings::substringBefore($this->ownerJoin, " AND "));
            $this->ownerAndQuery = trim(Strings::substringAfter($this->ownerJoin, " AND ", useFirst: true));
        }

        // The Owner Join is in the format "fieldName"
        if (!Strings::contains($ownerJoin, ".")) {
            $this->ownerFieldName = $ownerJoin;
            return true;
        }

        // The Owner Join is in the format "ModelName.fieldName" and both belong to the Owner Model
        [ $ownerModelName, $ownerFieldName ] = Strings::split($ownerJoin, ".");
        $this->ownerModelName = SchemaModel::getBaseModelName($ownerModelName);
        $this->ownerFieldName = $ownerFieldName;
        return true;
    }

    /**
     * BUILD STEP 4: After creating all the models we can get the Relation Model and the Parent Model
     * @param SchemaModel $relationModel The Relation Model is the one associated to the type of the Attribute.
     * @param SchemaModel $parentModel   The Parent Model is the Model where the Relation is defined. It might not be the Owner in the JOIN.
     * @return Relation
     */
    public function setModels(SchemaModel $relationModel, SchemaModel $parentModel): Relation {
        $this->relationModel = $relationModel;
        $this->parentModel   = $parentModel;

        // If the Relation Field Name is not set, use the ID of the Relation Model
        if ($this->relationFieldName === "") {
            $this->relationFieldName = $this->relationModel->idName;
        }

        // If the Owner Field Name is not set, use the ID of the Relation Model
        if ($this->ownerFieldName === "") {
            $this->ownerFieldName = $this->relationModel->idName;
        }
        return $this;
    }

    /**
     * BUILD STEP 5: Using the Models from STEP 2, generate the fields of the Relation
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
     * BUILD STEP 6: Tries to find the Owner Model Name using the Fields and Relations from the Parent Model
     * - If the ownerModelName was set in the previous steps, it does nothing
     * - If there is a field in the parent with the same ID as the Relation then the ownerModel is the parent Model
     * - If there is a Relation in the Parent Model with a field that has the same ID as the Relation then the ownerModel is that Relation Model
     * @return bool
     */
    public function inferOwnerModelName(): bool {
        // At this Build Step the Models must be set
        if ($this->relationModel === null || $this->parentModel === null) {
            return false;
        }

        // The Relation has an Owner Model using the Owner Join
        if ($this->ownerModelName !== "") {
            return false;
        }

        // First try to find if a Main Field is the ID of the related Model
        $hasKey = false;
        foreach ($this->parentModel->mainFields as $field) {
            if ($field->name === $this->ownerFieldName) {
                $hasKey = true;
                break;
            }
        }
        if ($hasKey) {
            $this->ownerModelName = $this->parentModel->name;
            return true;
        }

        // Then check if a Field from another Relation has the ID of the related Model
        foreach ($this->parentModel->relations as $otherRelation) {
            if ($this === $otherRelation || $otherRelation->relationModel === null) {
                continue;
            }
            foreach ($otherRelation->relationModel->fields as $field) {
                if ($field->name === $this->ownerFieldName) {
                    $this->ownerModelName = $otherRelation->relationModel->name;
                    return true;
                }
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
     * Returns the Name of the Table
     * @return string
     */
    public function getDbTableName(): string {
        if ($this->relationAliasName !== "") {
            return SchemaModel::getDbTableName($this->relationAliasName);
        }
        return SchemaModel::getDbTableName($this->relationModelName);
    }

    /**
     * Returns the Name of the And Model
     * @return string
     */
    public function getAndModelName(): string {
        if ($this->ownerAndQuery === "") {
            return "";
        }

        $parts = Strings::split($this->ownerAndQuery, " AND ");
        if (isset($parts[0]) && Strings::contains($parts[0], ".")) {
            return Strings::substringBefore($parts[0], ".");
        }
        return "";
    }

    /**
     * Returns the Name of And Table
     * @return string
     */
    public function getAndTableName(): string {
        $result = $this->getAndModelName();
        return SchemaModel::getDbTableName($result);
    }

    /**
     * Returns the Names of the And Fields
     * @return string[]
     */
    public function getAndFieldNames(): array {
        $andParts = Strings::split($this->ownerAndQuery, " AND ");
        $result   = [];
        for ($i = 0; $i < count($andParts); $i++) {
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
        if (Strings::endsWith($this->ownerAndQuery, " = ?")) {
            return trim(Strings::substringBetween($this->ownerAndQuery, " AND ", " = ?"));
        }
        return "";
    }

    /**
     * Returns true if there a isDeleted
     * @return bool
     */
    public function getAndIsDeleted(): bool {
        return Strings::contains($this->ownerAndQuery, "isDeleted");
    }



    /**
     * Returns the Expression for the Query
     * @return string
     */
    public function getExpression(): string {
        $joinTable      = SchemaModel::getDbTableName($this->relationModelName);
        $asTable        = $this->relationAliasName !== "" ? " AS " . SchemaModel::getDbTableName($this->relationAliasName) : "";
        $tableName      = $this->getDbTableName();

        $relationColumn = SchemaModel::getDbFieldName($this->relationFieldName);
        $ownerTableName = SchemaModel::getDbTableName($this->ownerModelName);
        $ownerColumn    = SchemaModel::getDbFieldName($this->ownerFieldName);
        $and            = $this->getAndExpression($tableName);

        return "LEFT JOIN `{$joinTable}`{$asTable} ON ($tableName.$relationColumn = $ownerTableName.$ownerColumn{$and})";
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
            "relationModelName" => $this->relationModelName,
            "relationAliasName" => $this->relationAliasName,
            "relationFieldName" => $this->relationFieldName,
            "ownerModelName"    => $this->ownerModelName,
            "ownerFieldName"    => $this->ownerFieldName,
            "ownerAndQuery"     => $this->ownerAndQuery,
            "fields"            => $fields,
        ];
    }


    /**
     * Returns the Name of the Relation
     * @param string[] $otherRelationNames List of the names of the Relations in the Parent Model
     * @return string
     */
    public function getName(array $otherRelationNames): string {
        if ($this->relationModel === null) {
            return "";
        }

        $result = $this->relationModel->idDbName;
        if ($result === "" || Arrays::contains($otherRelationNames, $result)) {
            $result = SchemaModel::getDbFieldName($this->ownerFieldName);
        }
        if ($result === "" || Arrays::contains($otherRelationNames, $result)) {
            $result = $this->prefix;
        }
        return $result;
    }

    /**
     * Returns the Data as an Array
     * @param string $dbName
     * @return array<string,mixed>
     */
    public function toArray(string $dbName): array {
        if ($this->relationModel === null || $this->parentModel === null) {
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
        $relationFieldName = SchemaModel::getDbFieldName($this->relationFieldName);
        $ownerFieldName    = SchemaModel::getDbFieldName($this->ownerFieldName);
        $andModelName      = $this->getAndModelName();
        $andFieldNames     = $this->getAndFieldNames();
        $andValue          = $this->getAndValue();
        $andDeleted        = $this->getAndIsDeleted();

        if ($this->relationAliasName !== "") {
            $result["asSchema"] = $this->relationAliasName;
        }

        if ($relationFieldName !== $dbName) {
            $result["leftKey"] = $relationFieldName;
        }

        if ($this->ownerModelName !== $this->parentModel->name) {
            $result["onSchema"] = $this->ownerModelName;
        }
        if ($ownerFieldName !== $dbName) {
            $result["rightKey"] = $ownerFieldName;
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
