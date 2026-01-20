<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Builder\Builder;
use Framework\Database\Model\FieldType;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

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
        $contents = Builder::render("Entity", [
            "namespace"  => $schemaModel->namespace,
            "name"       => $schemaModel->name,
            "id"         => $schemaModel->idName,
            "subTypes"   => self::getSubTypes($schemaModel),
            "attributes" => self::getAttributes($schemaModel),
        ]);
        return $contents;
    }

    /**
     * Returns the Field attributes for the Entity
     * @param SchemaModel $schemaModel
     * @return array{name:string,type:string,subType:string,default:string}[]
     */
    private static function getAttributes(SchemaModel $schemaModel): array {
        $result = [];
        if ($schemaModel->hasID) {
            $type     = FieldType::getCodeType($schemaModel->idType);
            $result[] = self::getTypeData("id", $type);
        }

        foreach ($schemaModel->fields as $field) {
            self::addAttribute($result, $field->name, $field->type);
        }
        foreach ($schemaModel->virtualFields as $field) {
            self::addAttribute($result, $field->name, $field->type);
        }
        if ($schemaModel->hasStatus) {
            $result[] = self::getTypeData("statusName", "string");
            $result[] = self::getTypeData("statusColor", "string");
        }

        foreach ($schemaModel->expressions as $expression) {
            self::addAttribute($result, $expression->name, $expression->type);
        }
        foreach ($schemaModel->counts as $count) {
            self::addAttribute($result, $count->name, FieldType::Number);
        }
        foreach ($schemaModel->relations as $relation) {
            foreach ($relation->fields as $field) {
                self::addAttribute($result, $field->prefixName, $field->type);
            }
        }
        foreach ($schemaModel->subRequests as $subRequest) {
            $type = $subRequest->type;
            if ($type === "") {
                $type = "{$subRequest->modelName}Entity[]";
            }
            $result[] = self::getTypeData($subRequest->name, "array", $type);
        }

        return self::parseAttributes($result);
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
        return [
            "name"    => $name,
            "type"    => $type,
            "subType" => $subType,
            "default" => FieldType::getDefault($type),
        ];
    }

    /**
     * Parses the Attributes
     * @param array{name:string,type:string,subType:string,default:string}[] $attributes
     * @return array{name:string,type:string,subType:string,default:string}[]
     */
    private static function parseAttributes(array $attributes): array {
        $nameLength = 0;
        $typeLength = 0;
        $result     = [];
        $parsed     = [];

        foreach ($attributes as $attribute) {
            $nameLength = max($nameLength, Strings::length($attribute["name"]));
            $typeLength = max($typeLength, Strings::length($attribute["type"]));
        }

        foreach ($attributes as $attribute) {
            if (isset($parsed[$attribute["name"]])) {
                continue;
            }
            $result[] = [
                "name"    => Strings::padRight($attribute["name"], $nameLength),
                "type"    => Strings::padRight($attribute["type"], $typeLength),
                "subType" => $attribute["subType"],
                "default" => $attribute["default"],
            ];
            $parsed[$attribute["name"]] = true;
        }
        return $result;
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
}
