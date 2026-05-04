<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Builder\Builder;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Request Code
 * @phpstan-type Property array{
 *   type: string,
 *   name: string,
 *   value: string,
 *   extras: string,
 * }
 */
class RequestCode {

    /**
     * Returns the Request code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getCode(SchemaModel $schemaModel): string {
        $properties   = self::getProperties($schemaModel);
        $dictionaries = self::getDictionaries($schemaModel);

        $contents   = Builder::render("Request", [
            "namespace"    => $schemaModel->namespace,
            "name"         => $schemaModel->name,
            "tableName"    => $schemaModel->tableName,

            "hasIntID"     => $schemaModel->hasID && $schemaModel->idType === FieldType::Number,
            "hasStringID"  => $schemaModel->hasID && $schemaModel->idType === FieldType::String,
            "hasEnumID"    => $schemaModel->hasID && $schemaModel->idType === FieldType::Enum,
            "idName"       => $schemaModel->idName,

            "requestClass" => $schemaModel->requestClass,
            "statusClass"  => $schemaModel->statusClass,
            "hasStatus"    => $schemaModel->hasStatus,

            "properties"   => $properties,
            "dictionaries" => $dictionaries,
            "parents"      => self::getParents($schemaModel),
            "imports"      => self::getImports($schemaModel),
            "values"       => self::getValues($properties),
        ]);
        return $contents;
    }

    /**
     * Returns the Field properties
     * @param SchemaModel $schemaModel
     * @return list<Property>
     */
    private static function getProperties(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $field) {
            if (!$field->hasValue()) {
                continue;
            }

            $type = self::getValueType($field->type);
            if ($type === "") {
                continue;
            }

            $value = $field->name;
            if ($field->dateInput !== "") {
                $value = $field->dateInput;
            }

            $extras = "";
            if ($field->type === FieldType::Float) {
                $extras = ", {$field->decimals}";
            } elseif ($field->type === FieldType::Date) {
                $extras = ", \"{$field->hourInput}\"";
            }

            $result[] = [
                "type"   => $type,
                "name"   => $field->name,
                "value"  => $value,
                "extras" => $extras,
            ];
        }
        return $result;
    }

    /**
     * Returns the Dictionary properties
     * @param SchemaModel $schemaModel
     * @return list<string>
     */
    private static function getDictionaries(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $field) {
            if ($field->isDictionary()) {
                $result[] = $field->name;
            }
        }
        return $result;
    }

    /**
     * Returns the Parent Fields
     * @param SchemaModel $schemaModel
     * @return list<array{type:string,getter:string,name:string}>
     */
    private static function getParents(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $field) {
            if ($field->isParent) {
                $type     = $field->type->getCodeType();
                $result[] = [
                    "type"   => $type,
                    "getter" => "get" . Strings::upperCaseFirst($type),
                    "name"   => $field->name,
                ];
            }
        }
        return $result;
    }



    /**
     * Returns the Field Imports
     * @param SchemaModel $schemaModel
     * @return list<string>
     */
    private static function getImports(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($field->isStatus) {
                $queryName = "{$schemaModel->statusClass}Where";
                $result["{$schemaModel->namespace}\\$queryName"] = 1;
            }
        }

        foreach ($schemaModel->relations as $relation) {
            foreach ($relation->fields as $field) {
                if ($field->isStatus && $relation->relationModel !== null) {
                    $queryName = "{$relation->relationModelName}StatusWhere";
                    $result["{$relation->relationModel->namespace}\\$queryName"] = 1;
                }
            }
        }

        return array_keys($result);
    }

    /**
     * Returns the Field Values
     * @param list<Property> $properties
     * @return list<string>
     */
    private static function getValues(array $properties): array {
        $result = [];
        foreach ($properties as $property) {
            if (!Arrays::contains($result, $property["type"])) {
                $result[] = $property["type"];
            }
        }
        return $result;
    }


    /**
     * Returns the Value Type for a Field Type
     * @param FieldType $type
     * @return string
     */
    private static function getValueType(FieldType $type): string {
        return match ($type) {
            FieldType::None    => "",

            FieldType::Date    => "DateValue",
            FieldType::Enum    => "EnumValue",
            FieldType::JSON,
            FieldType::Array   => "StringValue",

            FieldType::Boolean => "BoolValue",
            FieldType::Number  => "NumberValue",
            FieldType::Float   => "FloatValue",

            FieldType::String,
            FieldType::Text,
            FieldType::LongText,
            FieldType::File,
            FieldType::Encrypt => "StringValue",
        };
    }
}
