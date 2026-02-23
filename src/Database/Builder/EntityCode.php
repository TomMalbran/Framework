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
        $dictionaries = self::getFieldsByType($schemaModel, FieldType::JSON);
        $dates        = self::getFieldsByType($schemaModel, FieldType::Date);
        $imports      = self::getImports($schemaModel);

        $contents = Builder::render("Entity", [
            "namespace"       => $schemaModel->namespace,
            "name"            => $schemaModel->name,
            "entityClass"     => $schemaModel->entityClass,
            "statusClass"     => $schemaModel->statusClass,
            "hasStatus"       => $schemaModel->hasStatus,

            "hasID"           => $schemaModel->hasID,
            "hasIntID"        => $schemaModel->hasID && $schemaModel->idType === FieldType::Number,
            "hasStringID"     => $schemaModel->hasID && $schemaModel->idType === FieldType::String,
            "hasEnumID"       => $schemaModel->hasID && $schemaModel->idType === FieldType::Enum,
            "idName"          => $schemaModel->idName,
            "idEnumName"      => Strings::substringAfter($schemaModel->idEnumClass, "\\"),

            "properties"      => self::getProperties($schemaModel),
            "subTypes"        => self::getSubTypes($schemaModel),
            "mainFields"      => self::getMainFields($schemaModel),
            "hasDictionaries" => count($dictionaries) > 0,
            "dictionaries"    => $dictionaries,
            "hasDates"        => count($dates) > 0,
            "dates"           => $dates,
            "imports"         => $imports,
            "hasImports"      => count($imports) > 0,
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
                self::addProperty($fields, $field->name, $field->type, "", $field->enumClass);
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
            self::addProperty($fields, $field->name, $field->type, $field->subType, $field->enumClass);
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
                    self::addProperty($fields, $field->prefixName, $field->type, "", $field->enumClass);
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
     * @return void
     */
    private static function addCategory(array &$result, string $name, array $fields, array &$parsed): void {
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
    }

    /**
     * Adds the Property to the Entity
     * @param array<string,string|bool>[] $result
     * @param string                      $fieldKey
     * @param FieldType                   $fieldType
     * @param string                      $subType   Optional.
     * @param string                      $enumClass Optional.
     * @return void
     */
    private static function addProperty(
        array &$result,
        string $fieldKey,
        FieldType $fieldType,
        string $subType = "",
        string $enumClass = "",
    ): void {
        if ($fieldType === FieldType::Enum) {
            $type     = FieldType::getCodeType($fieldType, $enumClass, true);
            $result[] = self::getTypeData($fieldKey, $type, default: "{$type}::None");
        } elseif ($fieldType === FieldType::File) {
            $result[] = self::getTypeData($fieldKey, "string");
            $result[] = self::getTypeData("{$fieldKey}Url", "string");
            $result[] = self::getTypeData("{$fieldKey}Thumb", "string");
        } else {
            $type     = FieldType::getCodeType($fieldType, $enumClass, true);
            $result[] = self::getTypeData($fieldKey, $type, $subType);
        }
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
     * Returns the Main Fields of the Entity
     * @param SchemaModel $schemaModel
     * @return array<string,string|bool>[]
     */
    private static function getMainFields(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->mainFields as $field) {
            if ($field->type === FieldType::Enum) {
                $type     = FieldType::getCodeType($field->type, $field->enumClass, true);
                $result[] = self::getTypeData($field->name, $type, default: "{$type}::None");
            } elseif ($field->type !== FieldType::JSON && $field->type !== FieldType::Date) {
                $type     = FieldType::getCodeType($field->type, $field->enumClass, true);
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
     * Returns the Fields by Type
     * @param SchemaModel $schemaModel
     * @param FieldType   $type
     * @return string[]
     */
    private static function getFieldsByType(SchemaModel $schemaModel, FieldType $type): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($field->type === $type) {
                $result[] = $field->name;
            }
        }

        foreach ($schemaModel->relations as $relation) {
            foreach ($relation->fields as $field) {
                if ($field->type === $type) {
                    $result[] = $field->prefixName;
                }
            }
        }

        foreach ($schemaModel->virtualFields as $field) {
            if ($field->type === $type) {
                $result[] = $field->name;
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
     * Returns used Imports
     * @param SchemaModel $schemaModel
     * @return string[]
     */
    private static function getImports(SchemaModel $schemaModel): array {
        $result = [];

        foreach ($schemaModel->fields as $field) {
            if ($field->isStatus) {
                $result["{$schemaModel->namespace}\\{$schemaModel->statusClass}"] = 1;
            } elseif ($field->type === FieldType::Enum) {
                $result[$field->enumClass] = 1;
            }
        }

        foreach ($schemaModel->virtualFields as $field) {
            if ($field->type === FieldType::Enum) {
                $result[$field->enumClass] = 1;
            }
        }

        foreach ($schemaModel->relations as $relation) {
            foreach ($relation->fields as $field) {
                if ($field->type === FieldType::Enum) {
                    $result[$field->enumClass] = 1;
                } elseif ($field->isStatus && $relation->relationModel !== null) {
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
