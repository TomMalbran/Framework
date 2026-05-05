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
        $natives      = self::getNatives($schemaModel);
        $properties   = self::getProperties($schemaModel);
        $dictionaries = self::getDictionaries($schemaModel);
        $imports      = self::getImports($schemaModel);

        $contents = Builder::render("Request", [
            "namespace"       => $schemaModel->namespace,
            "name"            => $schemaModel->name,
            "tableName"       => $schemaModel->tableName,

            "hasIntID"        => $schemaModel->hasID && $schemaModel->idType === FieldType::Number,
            "hasStringID"     => $schemaModel->hasID && $schemaModel->idType === FieldType::String,
            "hasEnumID"       => $schemaModel->hasID && $schemaModel->idType === FieldType::Enum,
            "idName"          => $schemaModel->idName,
            "idEnumName"      => Strings::substringAfter($schemaModel->idEnumClass, "\\"),
            "hasMultiID"      => self::hasMultiID($schemaModel),

            "requestClass"    => $schemaModel->requestClass,
            "statusClass"     => $schemaModel->statusClass,
            "hasStatus"       => $schemaModel->hasStatus,

            "hasNatives"      => count($natives) > 0,
            "natives"         => $natives,
            "hasProperties"   => count($properties) > 0,
            "properties"      => $properties,
            "hasDictionaries" => count($dictionaries) > 0,
            "dictionaries"    => $dictionaries,

            "values"          => self::getValues($properties),
            "hasImports"      => count($imports) > 0,
            "imports"         => $imports,
        ]);
        return $contents;
    }

    /**
     * Returns if the ID can be Requested multiple times
     * @param SchemaModel $schemaModel
     * @return bool
     */
    private static function hasMultiID(SchemaModel $schemaModel): bool {
        foreach ($schemaModel->requestedFields as $field) {
            if ($field->isMultiID && $field->name === $schemaModel->idName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the Native Fields
     * @param SchemaModel $schemaModel
     * @return list<array{type:string,getter:string,name:string}>
     */
    private static function getNatives(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $field) {
            if (!$field->isNative) {
                continue;
            }

            $type     = $field->type->getCodeType($field->enumClass);
            $typeName = Strings::upperCaseFirst($type);

            $getter = "\$request->get{$typeName}(\"{$field->name}\")";
            if ($field->type === FieldType::Enum) {
                $getter = "{$type}::fromRequest(\$request, \"{$field->name}\")";
            }

            $result[] = [
                "type"   => $type,
                "getter" => $getter,
                "name"   => $field->name,
            ];
        }
        return $result;
    }

    /**
     * Returns the Field properties
     * @param SchemaModel $schemaModel
     * @return list<Property>
     */
    private static function getProperties(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $field) {
            if (!$field->hasValue) {
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
     * Returns the used Imports
     * @param SchemaModel $schemaModel
     * @return list<string>
     */
    private static function getImports(SchemaModel $schemaModel): array {
        $result = [];
        if ($schemaModel->hasID && $schemaModel->idType === FieldType::Enum) {
            $result[$schemaModel->idEnumClass] = 1;
        }
        foreach ($schemaModel->requestedFields as $field) {
            if ($field->isNative && $field->type === FieldType::Enum) {
                $result[$field->enumClass] = 1;
            }
        }
        return array_keys($result);
    }

    /**
     * Returns the used Values
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
