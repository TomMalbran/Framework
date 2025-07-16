<?php
namespace Framework\Database;

use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Database\Model\Expression;
use Framework\Database\Model\Virtual;
use Framework\Database\Model\Count;
use Framework\Database\Model\Relation;
use Framework\Database\Model\SubRequest;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Model
 */
class SchemaModel {

    public string $name          = "";
    public string $tableName     = "";
    public string $path          = "";
    public string $namespace     = "";
    public bool   $fromFramework = false;


    // Special flags
    public bool $hasUsers        = false;
    public bool $hasTimestamps   = false;
    public bool $hasPositions    = false;
    public bool $hasStatus       = false;
    public bool $canCreate       = false;
    public bool $canEdit         = false;
    public bool $canDelete       = false;


    // Main column data
    public bool      $hasID      = false;
    public bool      $hasAutoInc = false;
    public string    $idKey      = "";
    public string    $idName     = "";
    public FieldType $idType     = FieldType::None;


    /** @var Field[] */
    public array $fields         = [];

    /** @var Field[] */
    public array $mainFields     = [];

    /** @var Field[] */
    public array $extraFields    = [];

    /** @var Virtual[] */
    public array $virtualFields  = [];

    /** @var Expression[] */
    public array $expressions    = [];

    /** @var Count[] */
    public array $counts         = [];

    /** @var Relation[] */
    public array $relations      = [];

    /** @var SubRequest[] */
    public array $subRequests    = [];



    /**
     * The Schema Model
     * @param string       $name          Optional.
     * @param string       $path          Optional.
     * @param string       $namespace     Optional.
     * @param boolean      $fromFramework Optional.
     * @param boolean      $hasUsers      Optional.
     * @param boolean      $hasTimestamps Optional.
     * @param boolean      $hasPositions  Optional.
     * @param boolean      $hasStatus     Optional.
     * @param boolean      $canCreate     Optional.
     * @param boolean      $canEdit       Optional.
     * @param boolean      $canDelete     Optional.
     * @param Field[]      $mainFields    Optional.
     * @param Virtual[]    $virtualFields Optional.
     * @param Expression[] $expressions   Optional.
     * @param Count[]      $counts        Optional.
     * @param Relation[]   $relations     Optional.
     * @param SubRequest[] $subRequests   Optional.
     */
    public function __construct(
        string $name          = "",
        string $path          = "",
        string $namespace     = "",
        bool   $fromFramework = false,

        bool   $hasUsers      = false,
        bool   $hasTimestamps = false,
        bool   $hasPositions  = false,
        bool   $hasStatus     = false,
        bool   $canCreate     = false,
        bool   $canEdit       = false,
        bool   $canDelete     = false,

        array  $mainFields    = [],
        array  $virtualFields = [],
        array  $expressions   = [],
        array  $counts        = [],
        array  $relations     = [],
        array  $subRequests   = [],
    ) {
        $this->name          = $name;
        $this->tableName     = self::getDbTableName($name);
        $this->path          = $path;
        $this->namespace     = $namespace;
        $this->fromFramework = $fromFramework;

        $this->hasUsers      = $hasUsers;
        $this->hasTimestamps = $hasTimestamps;
        $this->hasPositions  = $hasPositions;
        $this->hasStatus     = $hasStatus;
        $this->canCreate     = $canCreate;
        $this->canEdit       = $canEdit;
        $this->canDelete     = $canDelete;

        $this->mainFields    = $mainFields;
        $this->virtualFields = $virtualFields;
        $this->expressions   = $expressions;
        $this->counts        = $counts;
        $this->relations     = $relations;
        $this->subRequests   = $subRequests;

        $this->setExtraFields();
        $this->setIDField();

        $this->fields = array_merge(
            $this->mainFields,
            $this->extraFields,
        );
    }

    /**
     * Sets extra fields based on the model attributes
     * @return SchemaModel
     */
    private function setExtraFields(): SchemaModel {
        if ($this->hasPositions) {
            $this->extraFields[] = Field::create(
                type: FieldType::Number,
                name: "position",
            );
        }
        if ($this->hasStatus) {
            $this->extraFields[] = Field::create(
                type:    FieldType::String,
                name:    "status",
                noEmpty: true,
                isKey:   true,
            );
        }

        if ($this->canCreate && $this->hasTimestamps) {
            $this->extraFields[] = Field::create(
                type:    FieldType::Number,
                name:    "createdTime",
                canEdit: false,
            );
        }
        if ($this->canCreate && $this->hasUsers) {
            $this->extraFields[] = Field::create(
                type:    FieldType::Number,
                name:    "createdUser",
                canEdit: false,
            );
        }

        if ($this->canEdit && $this->hasTimestamps) {
            $this->extraFields[] = Field::create(
                type:    FieldType::Number,
                name:    "modifiedTime",
                canEdit: false,
            );
        }
        if ($this->canEdit && $this->hasUsers) {
            $this->extraFields[] = Field::create(
                type:    FieldType::Number,
                name:    "modifiedUser",
                canEdit: false,
            );
        }

        if ($this->canDelete) {
            $this->extraFields[] = Field::create(
                type:    FieldType::Boolean,
                name:    "isDeleted",
                canEdit: false,
            );
        }
        return $this;
    }

    /**
     * Returns the ID Field of the Model
     * @return boolean
     */
    public function setIDField(): bool {
        foreach ($this->mainFields as $field) {
            if ($field->isID) {
                $this->hasID      = true;
                $this->hasAutoInc = $field->isAutoInc();
                $this->idKey      = $field->dbName;
                $this->idName     = $field->name;
                $this->idType     = $field->type;
                return true;
            }
        }
        return false;
    }



    /**
     * Returns the Fields of the Model
     * @param boolean $withTimestamps
     * @param boolean $withDeleted
     * @return Field[]
     */
    public function getFields(bool $withTimestamps, bool $withDeleted): array {
        $result = [];
        foreach ($this->mainFields as $field) {
            $result[] = $field;
        }

        foreach ($this->extraFields as $field) {
            $fieldName = $field->dbName;
            if (!$withTimestamps && ($fieldName === "createdTime" || $fieldName === "modifiedTime")) {
                continue;
            }
            if (!$withDeleted && $fieldName === "isDeleted") {
                continue;
            }
            $result[] = $field;
        }
        return $result;
    }

    /**
     * Returns all the Field Names of the Model
     * @return string[]
     */
    public function getFieldNames(): array {
        $result = [];
        foreach ($this->mainFields as $field) {
            $result[] = $field->dbName;
        }
        foreach ($this->extraFields as $field) {
            $result[] = $field->dbName;
        }
        foreach ($this->expressions as $expression) {
            $result[] = $expression->name;
        }
        foreach ($this->virtualFields as $virtual) {
            $result[] = $virtual->name;
        }
        foreach ($this->expressions as $expression) {
            $result[] = $expression->name;
        }
        foreach ($this->counts as $count) {
            $result[] = $count->name;
        }
        return $result;
    }

    /**
     * Returns true if there is an Encrypt Field in the Model
     * @return bool
     */
    public function hasEncrypt(): bool {
        foreach ($this->mainFields as $field) {
            if ($field->type === FieldType::Encrypt) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the Key adding the table as the prefix
     * @param string $key
     * @return string
     */
    public function getKey(string $key): string {
        if (!Strings::contains($key, ".")) {
            $mainKey = $this->tableName;
            return "{$mainKey}.{$key}";
        }
        return $key;
    }



    /**
     * Returns the Build Data for the Schema Builder
     * @param string $name
     * @return string[]
     */
    public function toBuildData(string $name): array {
        $result = [];
        switch ($name) {
        case "mainFields":
            foreach ($this->mainFields as $field) {
                $result[] = $this->generateBuildData($field->toBuildData(), true);
            }
            break;
        case "expressions":
            foreach ($this->expressions as $expression) {
                $result[] = $this->generateBuildData($expression->toBuildData(), false);
            }
            break;
        case "counts":
            foreach ($this->counts as $count) {
                $result[] = $this->generateBuildData($count->toBuildData(), false);
            }
            break;
        default:
        }
        return $result;
    }

    /**
     * Returns the Build Data for the Schema Builder
     * @param array<string,mixed> $params
     * @param boolean             $withKey
     * @return string
     */
    private function generateBuildData(array $params, bool $withKey): string {
        $result = [];
        foreach ($params as $key => $value) {
            $text = "";
            if ($value instanceof FieldType) {
                $text = "FieldType::{$value->name}";
            } elseif (is_string($value)) {
                $text = "\"$value\"";
            } elseif (is_bool($value)) {
                $text = $value ? "true" : "false";
            } elseif (is_numeric($value)) {
                $text = $value;
            }
            $result[] = $withKey ? "$key: $text" : $text;
        }
        return Strings::join($result, ", ");
    }

    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        $data = [
            "fields"      => [],
            "expressions" => [],
            "processed"   => [],
            "counts"      => [],
            "joins"       => [],
            "subrequests" => [],
            "foreigns"    => [],
        ];
        $relations = [];

        // Add the fields
        foreach ($this->mainFields as $field) {
            $data["fields"][$field->dbName] = $field->toArray();
        }

        // Add the expressions
        foreach ($this->expressions as $expression) {
            $data["expressions"][$expression->name] = $expression->toArray();
        }

        // Add the virtual fields
        foreach ($this->virtualFields as $virtual) {
            $data["processed"][$virtual->name] = $virtual->toArray();
        }

        // Add the expressions
        foreach ($this->expressions as $expression) {
            $data["expressions"][$expression->name] = $expression->toArray();
        }

        // Parse the counts and add the necessary values
        foreach ($this->counts as $count) {
            $data["counts"][$count->name] = $count->toArray();
        }

        // Parse the relations and add the necessary joins
        foreach ($this->relations as $relation) {
            if ($relation->relatedModel !== null) {
                $relationKey = $relation->getKey();
                $relations[] = $relationKey;
                $data["joins"][$relationKey] = $relation->toArray();
            }
        }

        // Parse the sub requests and add the necessary values
        foreach ($this->subRequests as $subRequest) {
            $data["subrequests"][$subRequest->schemaName] = $subRequest->toArray();
        }

        // Add the foreign fields
        foreach ($this->mainFields as $field) {
            $name = $field->dbName;
            if ($field->belongsTo !== "" && !Arrays::contains($relations, $name)) {
                $data["foreigns"][$name] = [
                    "schema" => $field->belongsTo,
                ];
                if ($field->otherKey !== "") {
                    $data["foreigns"][$name]["leftKey"] = self::getDbFieldName($field->otherKey);
                }
            }
        }


        // Generate the result
        $result = [
            "hasTimestamps" => $this->hasTimestamps,
            "canCreate"     => $this->canCreate,
            "canEdit"       => $this->canEdit,
            "canDelete"     => $this->canDelete,
        ];

        $optionals = [ "hasUsers", "hasStatus", "hasPositions" ];
        foreach ($optionals as $name) {
            if ($this->$name) {
                $result[$name] = $this->$name;
            }
        }

        foreach ($data as $name => $value) {
            if (count($value) > 0) {
                $result[$name] = $value;
            }
        }

        return $result;
    }



    /**
     * Gets the Table Name for the Schema
     * @param string $schema
     * @return string
     */
    public static function getDbTableName(string $schema): string {
        return Strings::pascalCaseToSnakeCase($schema);
    }

    /**
     * Generates the name of the field for the Database
     * @param string $name
     * @return string
     */
    public static function getDbFieldName(string $name): string {
        if (Strings::endsWith($name, "ID")) {
            $name = Strings::replace($name, "ID", "Id");
            $name = Strings::camelCaseToUpperCase($name);
        }
        return $name;
    }
}
