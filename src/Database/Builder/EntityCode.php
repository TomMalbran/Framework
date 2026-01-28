<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Builder\Builder;
use Framework\Database\Model\FieldType;
use Framework\Utils\Arrays;

/**
 * The Entity Code
 */
class EntityCode {

    /**
     * Returns the Entity code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getCode(SchemaModel $schemaModel): string {
        $dates    = self::getDates($schemaModel);
        $contents = Builder::render("Entity", [
            "namespace"  => $schemaModel->namespace,
            "name"       => $schemaModel->name,
            "id"         => $schemaModel->idName,
            "subTypes"   => self::getSubTypes($schemaModel),
            "categories" => self::getAttributes($schemaModel),
            "hasDates"   => count($dates) > 0,
            "dates"      => $dates,
        ]);
        return $contents;
    }

    /**
     * Returns the Field attributes for the Entity
     * @param SchemaModel $schemaModel
     * @return array{name:string,attributes:array{name:string,type:string,subType:string,default:string}[]}[]
     */
    private static function getAttributes(SchemaModel $schemaModel): array {
        $result = [];
        $parsed = [];

        // Main Fields
        $fields = [];
        if ($schemaModel->hasID) {
            $type     = FieldType::getCodeType($schemaModel->idType);
            $fields[] = self::getTypeData("id", $type);
        }
        foreach ($schemaModel->fields as $field) {
            self::addAttribute($fields, $field->name, $field->type);
        }
        self::addCategory($result, "Main", $fields, $parsed);

        // Virtual Fields
        $fields = [];
        foreach ($schemaModel->virtualFields as $field) {
            self::addAttribute($fields, $field->name, $field->type);
        }
        self::addCategory($result, "Virtual", $fields, $parsed);

        // Status Fields
        $fields = [];
        if ($schemaModel->hasStatus) {
            $fields[] = self::getTypeData("statusName", "string");
            $fields[] = self::getTypeData("statusColor", "string");
        }
        self::addCategory($result, "Status", $fields, $parsed);

        // Expression Fields
        $fields = [];
        foreach ($schemaModel->expressions as $expression) {
            self::addAttribute($fields, $expression->name, $expression->type);
        }
        self::addCategory($result, "Expression", $fields, $parsed);

        // Count Fields
        $fields = [];
        foreach ($schemaModel->counts as $count) {
            self::addAttribute($fields, $count->name, FieldType::Number);
        }
        self::addCategory($result, "Count", $fields, $parsed);

        // Relation Fields
        foreach ($schemaModel->relations as $relation) {
            $fields = [];
            foreach ($relation->fields as $field) {
                self::addAttribute($fields, $field->prefixName, $field->type);
            }
            self::addCategory($result, $relation->relationModelName, $fields, $parsed);
        }

        // SubRequest Fields
        $fields = [];
        foreach ($schemaModel->subRequests as $subRequest) {
            $type = $subRequest->type;
            if ($type === "") {
                $type = "{$subRequest->modelName}Entity[]";
            }
            $fields[] = self::getTypeData($subRequest->name, "array", $type);
        }
        self::addCategory($result, "SubRequest", $fields, $parsed);

        return $result;
    }

    /**
     * Adds a Category to the Result
     * @param array{name:string,attributes:array{name:string,type:string,subType:string,default:string}[]}[] $result
     * @param string                                                                                         $name
     * @param array{name:string,type:string,subType:string,default:string}[]                                 $fields
     * @param array<string,bool>                                                                             $parsed
     * @return array{name:string,attributes:array{name:string,type:string,subType:string,default:string}[]}[]
     */
    private static function addCategory(array &$result, string $name, array $fields, array &$parsed): array {
        $attributes = [];
        foreach ($fields as $field) {
            if (!isset($parsed[$field["name"]])) {
                $parsed[$field["name"]] = true;
                $attributes[]           = $field;
            }
        }

        if (count($attributes) > 0) {
            $result[] = [
                "name"       => $name,
                "attributes" => $attributes,
            ];
        }
        return $result;
    }

    /**
     * Adds the Field types for the Entity
     * @param array{name:string,type:string,subType:string,default:string}[] $result
     * @param string                                                         $fieldKey
     * @param FieldType                                                      $fieldType
     * @return bool
     */
    private static function addAttribute(
        array &$result,
        string $fieldKey,
        FieldType $fieldType,
    ): bool {
        switch ($fieldType) {
        case FieldType::Date:
            $result[] = self::getTypeData($fieldKey, "Date");
            break;
        case FieldType::Boolean:
            $result[] = self::getTypeData($fieldKey, "bool");
            break;
        case FieldType::Number:
            $result[] = self::getTypeData($fieldKey, "int");
            break;
        case FieldType::Float:
            $result[] = self::getTypeData($fieldKey, "float");
            break;
        case FieldType::JSON:
            $result[] = self::getTypeData($fieldKey, "mixed");
            break;
        case FieldType::File:
            $result[] = self::getTypeData($fieldKey, "string");
            $result[] = self::getTypeData("{$fieldKey}Url", "string");
            $result[] = self::getTypeData("{$fieldKey}Thumb", "string");
            break;
        default:
            $result[] = self::getTypeData($fieldKey, "string");
        }
        return true;
    }

    /**
     * Returns the Type and Default
     * @param string $name
     * @param string $type
     * @param string $subType
     * @return array{name:string,type:string,subType:string,default:string}
     */
    private static function getTypeData(string $name, string $type, string $subType = ""): array {
        $default = FieldType::getDefault($type);
        return [
            "name"       => $name,
            "type"       => $type,
            "subType"    => $subType,
            "hasDefault" => $default !== null,
            "default"    => $default !== null ? $default : "",
        ];
    }



    /**
     * Returns the Sub Types from the Sub Requests
     * @param SchemaModel $schemaModel
     * @return array{namespace:string,type:string}[]
     */
    private static function getSubTypes(SchemaModel $schemaModel): array {
        $models = [];
        $result = [];

        foreach ($schemaModel->subRequests as $subRequest) {
            $model = "{$subRequest->namespace}/{$subRequest->modelName}";
            if (Arrays::contains($models, $model)) {
                continue;
            }

            if ($subRequest->type === "") {
                $models[] = $model;
                $result[] = [
                    "namespace" => $subRequest->namespace,
                    "type"      => $subRequest->modelName,
                ];
            }
        }
        return $result;
    }

    /**
     * Returns the Date Fields
     * @param SchemaModel $schemaModel
     * @return string[]
     */
    private static function getDates(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($field->type === FieldType::Date) {
                $result[] = $field->name;
            }
        }

        foreach ($schemaModel->relations as $relation) {
            foreach ($relation->fields as $field) {
                if ($field->type === FieldType::Date) {
                    $result[] = $field->prefixName;
                }
            }
        }
        return $result;
    }
}
