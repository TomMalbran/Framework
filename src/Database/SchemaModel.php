<?php
namespace Framework\Database;

use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Database\Model\Validate;
use Framework\Database\Model\Expression;
use Framework\Database\Model\Virtual;
use Framework\Database\Model\Count;
use Framework\Database\Model\Relation;
use Framework\Database\Model\SubRequest;
use Framework\Database\Status\State;
use Framework\Date\DateType;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Model
 */
class SchemaModel {

    public string $name          = "";
    public string $tableName     = "";
    public string $fantasyName   = "";
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
    public bool $usesRequest     = true;


    // Main column data
    public bool      $hasID      = false;
    public bool      $hasAutoInc = false;
    public string    $idName     = "";
    public string    $idDbName   = "";
    public FieldType $idType     = FieldType::None;


    /** @var Field[] */
    public array $fields         = [];

    /** @var Field[] */
    public array $mainFields     = [];

    /** @var Field[] */
    public array $extraFields    = [];

    /** @var Validate[] */
    public array $validates      = [];

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

    /** @var State[] */
    public array $states         = [];



    /**
     * The Schema Model
     * @param string       $name          Optional.
     * @param string       $fantasyName   Optional.
     * @param string       $path          Optional.
     * @param string       $namespace     Optional.
     * @param bool         $fromFramework Optional.
     * @param bool         $hasUsers      Optional.
     * @param bool         $hasTimestamps Optional.
     * @param bool         $hasPositions  Optional.
     * @param bool         $hasStatus     Optional.
     * @param bool         $canCreate     Optional.
     * @param bool         $canEdit       Optional.
     * @param bool         $canDelete     Optional.
     * @param bool         $usesRequest   Optional.
     * @param Field[]      $mainFields    Optional.
     * @param Validate[]   $validates     Optional.
     * @param Virtual[]    $virtualFields Optional.
     * @param Expression[] $expressions   Optional.
     * @param Count[]      $counts        Optional.
     * @param Relation[]   $relations     Optional.
     * @param SubRequest[] $subRequests   Optional.
     * @param State[]      $states        Optional.
     */
    public function __construct(
        string $name = "",
        string $fantasyName = "",
        string $path = "",
        string $namespace = "",
        bool $fromFramework = false,
        bool $hasUsers = false,
        bool $hasTimestamps = false,
        bool $hasPositions = false,
        bool $hasStatus = false,
        bool $canCreate = false,
        bool $canEdit = false,
        bool $canDelete = false,
        bool $usesRequest = true,
        array $mainFields = [],
        array $validates = [],
        array $virtualFields = [],
        array $expressions = [],
        array $counts = [],
        array $relations = [],
        array $subRequests = [],
        array $states = [],
    ) {
        $this->name          = $name;
        $this->fantasyName   = $fantasyName;
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
        $this->usesRequest   = $usesRequest;

        $this->mainFields    = $mainFields;
        $this->validates     = $validates;
        $this->virtualFields = $virtualFields;
        $this->expressions   = $expressions;
        $this->counts        = $counts;
        $this->relations     = $relations;
        $this->subRequests   = $subRequests;
        $this->states        = $states;

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
                type:        FieldType::Number,
                name:        "position",
                fromRequest: true,
            );
        }
        if ($this->hasStatus) {
            $this->extraFields[] = Field::create(
                type:        FieldType::String,
                name:        "status",
                noEmpty:     true,
                isKey:       true,
                isStatus:    true,
                fromRequest: true,
            );
        }

        if ($this->canCreate && $this->hasTimestamps) {
            $this->extraFields[] = Field::create(
                type: FieldType::Date,
                name: "createdTime",
            );
        }
        if ($this->canCreate && $this->hasUsers) {
            $this->extraFields[] = Field::create(
                type: FieldType::Number,
                name: "createdUser",
            );
        }

        if ($this->canEdit && $this->hasTimestamps) {
            $this->extraFields[] = Field::create(
                type: FieldType::Date,
                name: "modifiedTime",
            );
        }
        if ($this->canEdit && $this->hasUsers) {
            $this->extraFields[] = Field::create(
                type: FieldType::Number,
                name: "modifiedUser",
            );
        }

        if ($this->canDelete) {
            $this->extraFields[] = Field::create(
                type: FieldType::Boolean,
                name: "isDeleted",
            );
        }
        return $this;
    }

    /**
     * Returns the ID Field of the Model
     * @return bool
     */
    public function setIDField(): bool {
        foreach ($this->mainFields as $field) {
            if ($field->isID) {
                $this->hasID      = true;
                $this->hasAutoInc = $field->isAutoInc();
                $this->idName     = $field->name;
                $this->idDbName   = $field->dbName;
                $this->idType     = $field->type;
                return true;
            }
        }
        return false;
    }



    /**
     * Returns the Fields of the Model
     * @param bool $withTimestamps
     * @param bool $withDeleted
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
            $result[] = $field->name;
        }
        foreach ($this->extraFields as $field) {
            $result[] = $field->name;
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
        foreach ($this->relations as $relation) {
            foreach ($relation->fields as $field) {
                $result[] = $field->prefixName;
            }
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
     * @return array{params:string,fields:array{}}[]
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
        case "relations":
            foreach ($this->relations as $relation) {
                $result[] = $this->generateBuildData($relation->toBuildData(), false);
            }
            break;
        case "subRequests":
            foreach ($this->subRequests as $subRequest) {
                $result[] = $this->generateBuildData($subRequest->toBuildData(), false);
            }
            break;
        default:
        }
        return $result;
    }

    /**
     * Returns the Build Data for the Schema Builder
     * @param array<string,mixed> $data
     * @param bool                $withKey
     * @return array{params:string,fields:array{}}
     */
    private function generateBuildData(array $data, bool $withKey): array {
        $params = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $text = "";
            if ($value instanceof FieldType) {
                $text = "FieldType::{$value->name}";
            } elseif ($value instanceof DateType) {
                $text = "DateType::{$value->name}";
            } elseif (is_string($value) && Strings::endsWith($value, "Schema")) {
                $text = "{$value}::getModel()";
            } elseif (is_string($value)) {
                $text = "\"$value\"";
            } elseif (is_bool($value)) {
                $text = $value ? "true" : "false";
            } elseif (is_numeric($value)) {
                $text = $value;
            }
            $params[] = $withKey ? "$key: $text" : $text;
        }

        $fields = [];
        if (isset($data["fields"]) && is_array($data["fields"])) {
            foreach ($data["fields"] as $value) {
                $value    = Arrays::toStringMixedMap($value);
                $fields[] = self::generateBuildData($value, true);
            }
        }

        return [
            "params"    => Strings::join($params, ", "),
            "paramList" => $params,
            "fields"    => $fields,
        ];
    }

    /**
     * Returns the Data for the Schema JSON
     * @return array<string,mixed>
     */
    public function toSchemaJSON(): array {
        $result = [
            "hasTimestamps" => $this->hasTimestamps,
            "hasStatus"     => $this->hasStatus,
            "hasPositions"  => $this->hasPositions,
            "hasUsers"      => $this->hasUsers,
            "canCreate"     => $this->canCreate,
            "canEdit"       => $this->canEdit,
            "canDelete"     => $this->canDelete,
            "fields"        => [],
            "joins"         => [],
            "foreigns"      => [],
        ];
        $relationNames = [];

        // Add the fields
        foreach ($this->mainFields as $field) {
            $result["fields"][] = $field->toSchemaJSON();
        }

        // Parse the relations and add the necessary joins
        foreach ($this->relations as $relation) {
            if ($relation->relationModel !== null) {
                $relationName      = $relation->getName($relationNames);
                $relationNames[]   = $relationName;
                $result["joins"][] = $relation->toSchemaJSON($relationName);
            }
        }

        // Add the foreign fields
        foreach ($this->mainFields as $field) {
            if ($field->belongsTo !== "" && !Arrays::contains($relationNames, $field->dbName)) {
                $result["foreigns"][] = $field->toSchemaForeign();
            }
        }

        return $result;
    }



    /**
     * Gets the name of the model without the class stuff
     * @param string|null $modelName
     * @return string
     */
    public static function getBaseModelName(?string $modelName): string {
        if ($modelName === null) {
            return "";
        }

        $result = Strings::stripEnd($modelName, "Model");
        if (Strings::contains($result, "\\")) {
            $result = Strings::substringAfter($result, "\\");
        }
        return $result;
    }

    /**
     * Gets the name of the table for the Database
     * @param string $modelName
     * @return string
     */
    public static function getDbTableName(string $modelName): string {
        return Strings::pascalCaseToSnakeCase($modelName);
    }

    /**
     * Generates the name of the field for the Database
     * @param string $name
     * @return string
     */
    public static function getDbFieldName(string $name): string {
        if (Strings::endsWith($name, "ID") && !Strings::isUpperCase($name)) {
            $name = Strings::replace($name, "ID", "Id");
            $name = Strings::camelCaseToUpperCase($name);
        }
        return $name;
    }
}
