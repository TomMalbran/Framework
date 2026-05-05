<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Builder\Builder;
use Framework\Database\Model\FieldType;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Entity Code
 * @phpstan-type Property array{
 *   name: string,
 *   type: string,
 *   subType: string,
 *   docType: string,
 *   hasDefault: bool,
 *   default: string,
 * }
 * @phpstan-type Category array{
 *   name: string,
 *   list: list<Property>,
 * }
 * @phpstan-type SubType array{
 *   name: string,
 *   type: string,
 *   namespace: string,
 *   useIndex: bool,
 *   keyType: string,
 * }
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
            "hasImports"      => count($imports) > 0,
            "imports"         => $imports,
        ]);
        return $contents;
    }



    /**
     * Returns the Properties of the Entity
     * @param SchemaModel $schemaModel
     * @return list<Category>
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

        // Status Field
        $fields = [];
        if ($schemaModel->hasStatus) {
            $statusClass = $schemaModel->statusClass;
            $fields[] = self::getPropertyData("status", $statusClass, default: "{$statusClass}::None");
            $fields[] = self::getPropertyData("statusName", "string");
            $fields[] = self::getPropertyData("statusColor", "string");
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
                    $fields[] = self::getPropertyData(
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
            $fields[] = self::getPropertyData($subRequest->name, "array", $subRequest->getDocType());
        }
        self::addCategory($result, "SubRequest", $fields, $parsed);

        return $result;
    }

    /**
     * Adds a Category to the Properties
     * @param list<Category>     $result
     * @param string             $name
     * @param list<Property>     $fields
     * @param array<string,bool> $parsed
     * @return void
     */
    private static function addCategory(array &$result, string $name, array $fields, array &$parsed): void {
        $list = [];
        foreach ($fields as $field) {
            if (!isset($parsed[$field["name"]])) {
                $parsed[$field["name"]] = true;
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
     * @param list<Property> $result
     * @param string         $fieldKey
     * @param FieldType      $fieldType
     * @param string         $subType   Optional.
     * @param string         $enumClass Optional.
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
            $type     = $fieldType->getCodeType($enumClass, forEntity: true);
            $result[] = self::getPropertyData($fieldKey, $type, default: "{$type}::None");
        } elseif ($fieldType === FieldType::File) {
            $result[] = self::getPropertyData($fieldKey, "string");
            $result[] = self::getPropertyData("{$fieldKey}Url", "string");
            $result[] = self::getPropertyData("{$fieldKey}Thumb", "string");
        } else {
            $type     = $fieldType->getCodeType($enumClass, forEntity: true);
            $result[] = self::getPropertyData($fieldKey, $type, $subType);
        }
    }

    /**
     * Returns the Type and Default
     * @param string      $name
     * @param string      $type
     * @param string      $subType Optional.
     * @param string|null $default Optional.
     * @return Property
     */
    private static function getPropertyData(
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
     * @return list<Property>
     */
    private static function getMainFields(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->mainFields as $field) {
            if ($field->type === FieldType::Enum) {
                $type     = $field->type->getCodeType($field->enumClass, forEntity: true);
                $result[] = self::getPropertyData($field->name, $type, default: "{$type}::None");
            } elseif ($field->type !== FieldType::JSON && $field->type !== FieldType::Date) {
                $type     = $field->type->getCodeType($field->enumClass, forEntity: true);
                $result[] = self::getPropertyData($field->name, $type);
            }
        }

        foreach ($schemaModel->virtualFields as $field) {
            if ($field->type === FieldType::Array) {
                $result[] = self::getPropertyData($field->name, "array", $field->subType);
            } elseif ($field->type === FieldType::Enum) {
                $type     = $field->type->getCodeType($field->enumClass, forEntity: true);
                $result[] = self::getPropertyData($field->name, $type, default: "{$type}::None");
            } elseif ($field->type !== FieldType::JSON && $field->type !== FieldType::Date) {
                $type     = $field->type->getCodeType($field->enumClass, forEntity: true);
                $result[] = self::getPropertyData($field->name, $type);
            }
        }

        foreach ($schemaModel->subRequests as $subRequest) {
            if ($subRequest->type !== "") {
                $result[] = self::getPropertyData($subRequest->name, "array", $subRequest->getDocType());
            }
        }
        return $result;
    }

    /**
     * Returns the Fields by Type
     * @param SchemaModel $schemaModel
     * @param FieldType   $type
     * @return list<string>
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
     * @return list<SubType>
     */
    private static function getSubTypes(SchemaModel $schemaModel): array {
        $models = [];
        $result = [];

        foreach ($schemaModel->subRequests as $subRequest) {
            if ($subRequest->type !== "" || $subRequest->modelName === "") {
                continue;
            }

            $model = "{$subRequest->namespace}/{$subRequest->modelName}";
            if (Arrays::contains($models, $model)) {
                continue;
            }

            $models[] = $model;
            $result[] = [
                "name"      => $subRequest->name,
                "type"      => $subRequest->modelName,
                "namespace" => $subRequest->namespace,
                "useIndex"  => $subRequest->keyType !== "",
                "keyType"   => $subRequest->keyType !== "string" ? "($subRequest->keyType)" : "",
            ];
        }
        return $result;
    }

    /**
     * Returns the used Imports
     * @param SchemaModel $schemaModel
     * @return list<string>
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
            } elseif ($field->subClass !== "") {
                $result[$field->subClass] = 1;
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
            if ($subRequest->type === "" && $subRequest->modelName !== "") {
                $result["{$subRequest->namespace}\\{$subRequest->modelName}Entity"] = 1;
            }
        }

        return array_keys($result);
    }
}
