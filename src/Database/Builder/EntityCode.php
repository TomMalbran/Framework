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
        $imports = self::getImports($schemaModel);
        $dates   = self::getDates($schemaModel);

        $contents = Builder::render("Entity", [
            "namespace"   => $schemaModel->namespace,
            "name"        => $schemaModel->name,
            "entityClass" => $schemaModel->entityClass,
            "statusClass" => $schemaModel->statusClass,
            "hasID"       => $schemaModel->hasID,
            "hasIntID"    => $schemaModel->hasID && $schemaModel->idType === FieldType::Number,
            "hasStringID" => $schemaModel->hasID && $schemaModel->idType !== FieldType::Number,
            "idName"      => $schemaModel->idName,
            "hasStatus"   => $schemaModel->hasStatus,
            "properties"  => self::getProperties($schemaModel),
            "attributes"  => self::getAttributes($schemaModel),
            "subTypes"    => self::getSubTypes($schemaModel),
            "hasDates"    => count($dates) > 0,
            "dates"       => $dates,
            "imports"     => $imports,
            "hasImports"  => count($imports) > 0,
        ]);
        return $contents;
    }



    /**
     * Returns the Properties of the Entity
     * @param SchemaModel $schemaModel
     * @return array{name:string,list:array<string,string|bool>[]}[]
     */
    private static function getProperties(SchemaModel $schemaModel): array {
        $result = [];
        $parsed = [];

        // Main Fields
        $fields = [];
        foreach ($schemaModel->fields as $field) {
            if (!$field->isStatus) {
                self::addProperty($fields, $field->name, $field->type);
            }
        }
        self::addCategory($result, "Main", $fields, $parsed);

        // Status Fields
        $fields = [];
        if ($schemaModel->hasStatus) {
            $statusClass = $schemaModel->statusClass;
            $fields[] = self::getTypeData("status", $statusClass, default: "{$statusClass}::None");
            $fields[] = self::getTypeData("statusName", "string");
            $fields[] = self::getTypeData("statusColor", "string");
        }
        self::addCategory($result, "Status", $fields, $parsed);

        // Virtual Fields
        $fields = [];
        foreach ($schemaModel->virtualFields as $field) {
            self::addProperty($fields, $field->name, $field->type);
        }
        self::addCategory($result, "Virtual", $fields, $parsed);

        // Expression Fields
        $fields = [];
        foreach ($schemaModel->expressions as $expression) {
            self::addProperty($fields, $expression->name, $expression->type);
        }
        self::addCategory($result, "Expression", $fields, $parsed);

        // Count Fields
        $fields = [];
        foreach ($schemaModel->counts as $count) {
            self::addProperty($fields, $count->name, FieldType::Number);
        }
        self::addCategory($result, "Count", $fields, $parsed);

        // Relation Fields
        foreach ($schemaModel->relations as $relation) {
            $fields = [];
            foreach ($relation->fields as $field) {
                if ($field->isStatus) {
                    $fields[] = self::getTypeData(
                        name:    "{$field->prefixName}",
                        type:    "{$relation->relationModelName}Status",
                        default: "{$relation->relationModelName}Status::None",
                    );
                } else {
                    self::addProperty($fields, $field->prefixName, $field->type);
                }
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
     * Adds a Category to the Properties
     * @param array{name:string,list:array<string,string|bool>[]}[] $result
     * @param string                                                $name
     * @param array<string,string|bool>[]                           $fields
     * @param array<string,bool>                                    $parsed
     * @return array{name:string,list:array<string,string|bool>[]}[]
     */
    private static function addCategory(array &$result, string $name, array $fields, array &$parsed): array {
        $list = [];
        foreach ($fields as $field) {
            $fieldName = Strings::toString($field["name"] ?? "");
            if (!isset($parsed[$fieldName])) {
                $parsed[$fieldName] = true;
                $list[] = $field;
            }
        }

        if (count($list) > 0) {
            $result[] = [
                "name" => $name,
                "list" => $list,
            ];
        }
        return $result;
    }

    /**
     * Adds the Property to the Entity
     * @param array<string,string|bool>[] $result
     * @param string                      $fieldKey
     * @param FieldType                   $fieldType
     * @return bool
     */
    private static function addProperty(
        array &$result,
        string $fieldKey,
        FieldType $fieldType,
    ): bool {
        if ($fieldType === FieldType::File) {
            $result[] = self::getTypeData($fieldKey, "string");
            $result[] = self::getTypeData("{$fieldKey}Url", "string");
            $result[] = self::getTypeData("{$fieldKey}Thumb", "string");
        } else {
            $type     = FieldType::getCodeType($fieldType, true);
            $result[] = self::getTypeData($fieldKey, $type);
        }
        return true;
    }

    /**
     * Returns the Type and Default
     * @param string      $name
     * @param string      $type
     * @param string      $subType
     * @param string|null $default Optional.
     * @return array<string,string|bool>
     */
    private static function getTypeData(
        string $name,
        string $type,
        string $subType = "",
        ?string $default = null,
    ): array {
        if ($default === null) {
            $default = FieldType::getDefault($type);
        }
        return [
            "name"       => $name,
            "type"       => $type,
            "subType"    => $subType,
            "docType"    => $subType !== "" ? $subType : $type,
            "hasDefault" => $default !== null,
            "default"    => $default !== null ? $default : "",
        ];
    }



    /**
     * Returns the Attributes of the Entity
     * @param SchemaModel $schemaModel
     * @return array<string,string|bool>[]
     */
    private static function getAttributes(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->mainFields as $field) {
            if ($field->type !== FieldType::Date) {
                $type     = FieldType::getCodeType($field->type, true);
                $result[] = self::getTypeData($field->name, $type);
            }
        }
        foreach ($schemaModel->subRequests as $subRequest) {
            $type = $subRequest->type;
            if ($type !== "") {
                $result[] = self::getTypeData($subRequest->name, "array", $type);
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



    /**
     * Returns the Sub Types from the Sub Requests
     * @param SchemaModel $schemaModel
     * @return array{name:string,type:string,namespace:string}[]
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
                    "name"      => $subRequest->name,
                    "type"      => $subRequest->modelName,
                    "namespace" => $subRequest->namespace,
                ];
            }
        }
        return $result;
    }

    /**
     * Returns the Sub Types from the Sub Requests
     * @param SchemaModel $schemaModel
     * @return string[]
     */
    private static function getImports(SchemaModel $schemaModel): array {
        $result = [];
        if ($schemaModel->hasStatus) {
            $result["{$schemaModel->namespace}\\{$schemaModel->statusClass}"] = 1;
        }

        foreach ($schemaModel->relations as $relation) {
            foreach ($relation->fields as $field) {
                if ($field->isStatus && $relation->relationModel !== null) {
                    $result["{$relation->relationModel->namespace}\\{$relation->relationModelName}Status"] = 1;
                }
            }
        }

        foreach ($schemaModel->subRequests as $subRequest) {
            if ($subRequest->type === "") {
                $result["{$subRequest->namespace}\\{$subRequest->modelName}Entity"] = 1;
            }
        }

        return array_keys($result);
    }
}
