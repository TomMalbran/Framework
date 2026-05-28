<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Database\Type\ValueType;
use Framework\Builder\Builder;
use Framework\Date\Date;
use Framework\Date\Type\DateType;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Request Code
 * @phpstan-type IDProperty array{
 *   hasID: bool,
 *   hasIntID: bool,
 *   hasStringID: bool,
 *   hasEnumID: bool,
 *   idName: string,
 *   idEnumName: string,
 * }
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
        $idField      = self::getIDField($schemaModel);
        $fields       = self::getFields($schemaModel);
        $values       = self::getValues($schemaModel);
        $dictionaries = self::getDictionaries($schemaModel);
        $imports      = self::getImports($schemaModel);

        $contents = Builder::render("Request", [
            "namespace"       => $schemaModel->namespace,
            "name"            => $schemaModel->name,
            "tableName"       => $schemaModel->tableName,

            "requestClass"    => $schemaModel->requestClass,
            "statusClass"     => $schemaModel->statusClass,
            "hasStatus"       => $schemaModel->hasStatus,

            "hasMultiID"      => self::hasMultiID($schemaModel),
            "hasFields"       => count($fields) > 0,
            "fields"          => $fields,
            "hasValues"       => count($values) > 0,
            "values"          => $values,
            "hasDictionaries" => count($dictionaries) > 0,
            "dictionaries"    => $dictionaries,

            "hasImports"      => count($imports) > 0,
            "imports"         => $imports,
        ] + $idField);
        return $contents;
    }

    /**
     * Returns the ID Field properties
     * @param SchemaModel $schemaModel
     * @return IDProperty
     */
    private static function getIDField(SchemaModel $schemaModel): array {
        $hasID       = false;
        $hasIntID    = false;
        $hasStringID = false;
        $hasEnumID   = false;
        $name        = "";
        $enumClass   = "";

        if ($schemaModel->hasID) {
            $hasID       = true;
            $hasIntID    = $schemaModel->idType === FieldType::Number;
            $hasStringID = $schemaModel->idType === FieldType::String;
            $hasEnumID   = $schemaModel->idType === FieldType::Enum;
            $name        = $schemaModel->idName;
            $enumClass   = $schemaModel->idEnumClass;
        } else {
            foreach ($schemaModel->requestedFields as $field) {
                if ($field->isID) {
                    $hasID       = true;
                    $hasIntID    = $field->type === ValueType::Number;
                    $hasStringID = $field->type === ValueType::String;
                    $hasEnumID   = $field->type === ValueType::Enum;
                    $name        = $field->name;
                    break;
                }
            }
        }

        return [
            "hasID"       => $hasID,
            "hasIntID"    => $hasIntID,
            "hasStringID" => $hasStringID,
            "hasEnumID"   => $hasEnumID,
            "idName"      => $name,
            "idEnumName"  => Strings::substringAfter($enumClass, "\\"),
        ];
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
     * Returns the Fields
     * @param SchemaModel $schemaModel
     * @return list<array{type:string,getter:string,name:string}>
     */
    private static function getFields(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $requested) {
            if (!$requested->isField()) {
                continue;
            }

            $type = $requested->type->getCodeType($requested->enumClass);

            switch ($requested->type) {
            case ValueType::Enum:
                $getter   = "{$type}::fromRequest(\$this->request, \"{$requested->name}\")";
                break;

            case ValueType::Date:
                $name = $requested->dateInput !== "" ? $requested->dateInput : $requested->name;
                if ($requested->dateType === DateType::None) {
                    $getter = "\$this->request->toDate(\"{$name}\")";
                    break;
                }
                $dateType = $requested->dateType->toString();
                $hour     = $requested->hourInput !== "" ? ", \"{$requested->hourInput}\"" : "";
                $getter   = "\$this->request->toDayMoment(\"{$name}\", DateType::{$dateType}{$hour})";
                break;

            case ValueType::Array:
                if ($requested->subClass !== "") {
                    $typeName = Strings::substringAfter($requested->subClass, "\\");
                    $getter   = "{$typeName}::fromList(\$this->request->getStrings(\"{$requested->name}\"))";
                } elseif (Strings::startsWith($requested->subType, "list<")) {
                    $typeName = Strings::substringBetween($requested->subType, "list<", ">");
                    $typeName = Strings::upperCaseFirst($typeName);
                    $getter   = "\$this->request->get{$typeName}s(\"{$requested->name}\")";
                } else {
                    $arrayFunc = self::getArrayFunction($requested->subType);
                    if ($arrayFunc === "") {
                        continue 2;
                    }
                    $getter = "Arrays::{$arrayFunc}(\$this->request->getJSONArray(\"{$requested->name}\"))";
                }
                break;

            default:
                $typeName = Strings::upperCaseFirst($type);
                $getter   = "\$this->request->get{$typeName}(\"{$requested->name}\")";
            }

            $result[] = [
                "type"    => $type,
                "subType" => $requested->subType,
                "getter"  => $getter,
                "name"    => $requested->name,
            ];
        }
        return $result;
    }

    /**
     * Returns the Array function for the given SubType
     * @param string $subType
     * @return string
     */
    private static function getArrayFunction(string $subType): string {
        return match ($subType) {
            "array<int,int>"       => "toIntsMap",
            "array<int,string>"    => "toIntStringMap",
            "array<string,string>" => "toStringsMap",
            "array<string,int>"    => "toStringIntMap",
            "array<string,float>"  => "toStringFloatMap",
            "array<string,mixed>"  => "toStringMixedMap",
            default                => "",
        };
    }

    /**
     * Returns the Values
     * @param SchemaModel $schemaModel
     * @return list<Property>
     */
    private static function getValues(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $requested) {
            if (!$requested->isValue()) {
                continue;
            }

            $type = $requested->getValueClass();
            if ($type === "") {
                continue;
            }

            $value = $requested->name;
            if ($requested->dateInput !== "") {
                $value = $requested->dateInput;
            }

            $extras = "";
            if ($requested->type === ValueType::Float) {
                $extras = ", {$requested->decimals}";
            } elseif ($requested->type === ValueType::Date) {
                $extras = ", \"{$requested->hourInput}\"";
                if ($requested->dateType !== DateType::None) {
                    $extras .= ", DateType::{$requested->dateType->toString()}";
                }
            } elseif ($requested->type === ValueType::Encrypt) {
                $extras = ", true";
            }

            $result[] = [
                "type"   => $type,
                "name"   => $requested->name,
                "value"  => $value,
                "extras" => $extras,
            ];
        }
        return $result;
    }

    /**
     * Returns the Dictionaries
     * @param SchemaModel $schemaModel
     * @return list<string>
     */
    private static function getDictionaries(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $requested) {
            if ($requested->isDictionary()) {
                $result[] = $requested->name;
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

        foreach ($schemaModel->requestedFields as $requested) {
            if ($requested->isField()) {
                if ($requested->type === ValueType::Enum) {
                    $result[$requested->enumClass] = 1;
                } elseif ($requested->type === ValueType::Date) {
                    $result[Date::class] = 1;
                }
                if ($requested->subClass !== "") {
                    $result[$requested->subClass] = 1;
                }
                if (Strings::startsWith($requested->subType, "array<")) {
                    $result[Arrays::class] = 1;
                }
            } elseif ($requested->isValue()) {
                $type = $requested->getValueClass();
                if ($type !== "") {
                    $result["Framework\IO\Value\\$type"] = 1;
                }
            }

            if ($requested->dateType !== DateType::None) {
                $result[DateType::class] = 1;
            }
        }
        return array_keys($result);
    }
}
