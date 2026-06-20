<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Database\Type\RequestedType;
use Framework\Builder\Builder;
use Framework\Date\Date;
use Framework\Date\Type\DateType;
use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Requested Code
 * @phpstan-type IDProperty array{
 *   hasID: bool,
 *   hasIntID: bool,
 *   hasStringID: bool,
 *   hasEnumID: bool,
 *   idName: string,
 *   idEnumName: string,
 * }
 * @phpstan-type Field array{
 *   name: string,
 *   type: string,
 *   subType: string,
 *   docType: string,
 *   argType: string,
 *   getter: string,
 *   setter: string,
 *   default: string,
 * }
 */
class RequestedCode {

    /**
     * Returns the Request code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getCode(SchemaModel $schemaModel): string {
        $idField      = self::getIDField($schemaModel);
        $fields       = self::getFields($schemaModel);
        $dictionaries = self::getDictionaries($schemaModel);
        $entityFields = self::getEntityFields($schemaModel);
        $imports      = self::getImports($schemaModel);

        $contents = Builder::render("Request", [
            "namespace"       => $schemaModel->namespace,
            "name"            => $schemaModel->name,
            "tableName"       => $schemaModel->tableName,
            "requestClass"    => $schemaModel->requestClass,
            "entityClass"     => $schemaModel->entityClass,

            "hasMultiID"      => self::hasMultiID($schemaModel),
            "hasFields"       => count($fields) > 0,
            "fields"          => $fields,
            "hasDictionaries" => count($dictionaries) > 0,
            "dictionaries"    => $dictionaries,
            "hasEntityFields" => count($entityFields) > 0,
            "entityFields"    => $entityFields,

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
                    $hasIntID    = $field->type === RequestedType::Number;
                    $hasStringID = $field->type === RequestedType::String;
                    $hasEnumID   = $field->type === RequestedType::Enum;
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
     * @return list<Field>
     */
    private static function getFields(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $requested) {
            if ($requested->type === RequestedType::Dictionary) {
                continue;
            }

            $name    = $requested->name;
            $type    = $requested->type->getCodeType($requested->enumClass);
            $docType = $requested->subType !== "" ? $requested->subType : $type;
            $argType = $type;
            $getter  = "";
            $setter  = "\${$requested->name}";
            $default = $requested->type->getDefaultValue($requested->enumClass);

            switch ($requested->type) {
            case RequestedType::Enum:
                $getter = "{$type}::fromValue(\$instance->request->getString(\"{$requested->name}\"))";  // phpcs:ignore
                break;

            case RequestedType::Date:
                $docType = "Date|null";
                $argType = "?Date";

                if ($requested->hourInput !== "") {
                    $default = "null";
                }
                $dateType = "DateType::{$requested->dateType->toString()}";
                if ($requested->dateType === DateType::None) {
                    $dateType = "DateType::None";
                }

                $date     = $requested->dateInput !== "" ? $requested->dateInput : $name;
                $hour     = $requested->hourInput;
                $timeZone = $requested->useTimeZone ? "useTimeZone: true" : "useTimeZone: false";
                $getter   = "\$instance->request->getDate(\"{$date}\", \"{$hour}\", {$dateType}, {$timeZone})";  // phpcs:ignore
                $setter   = "\${$requested->name} === null ? Date::empty() : \${$name}";
                break;

            case RequestedType::Status:
                $type    = $schemaModel->statusClass;
                $docType = $schemaModel->statusClass;
                $argType = $docType;
                $default = "{$schemaModel->statusClass}::None";
                $getter  = "{$schemaModel->statusClass}::fromValue(\$instance->request->getString(\"{$name}\"))";  // phpcs:ignore
                break;

            case RequestedType::Array:
                if ($requested->subClass !== "") {
                    $typeName = Strings::substringAfter($requested->subClass, "\\");
                    $getter   = "{$typeName}::fromList(\$instance->request->getStrings(\"{$name}\"))";  // phpcs:ignore
                } elseif (Strings::startsWith($requested->subType, "list<")) {
                    $typeName = Strings::substringBetween($requested->subType, "list<", ">");
                    $typeName = Strings::upperCaseFirst($typeName);
                    $getter   = "\$instance->request->get{$typeName}s(\"{$name}\")";
                } else {
                    $arrayFunc = self::getArrayFunction($requested->subType);
                    if ($arrayFunc === "") {
                        continue 2;
                    }
                    $getter = "Arrays::{$arrayFunc}(\$instance->request->getJSONArray(\"{$name}\"))";  // phpcs:ignore
                }
                break;

            default:
                $typeName = Strings::upperCaseFirst($type);
                $getter   = "\$instance->request->get{$typeName}(\"{$name}\")";
            }

            $result[] = [
                "name"    => $name,
                "type"    => $type,
                "subType" => $requested->subType,
                "docType" => $docType,
                "argType" => $argType,
                "getter"  => $getter,
                "setter"  => $setter,
                "default" => $default,
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
     * Returns the Dictionaries
     * @param SchemaModel $schemaModel
     * @return list<string>
     */
    private static function getDictionaries(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $requested) {
            if ($requested->type === RequestedType::Dictionary) {
                $result[] = $requested->name;
            }
        }
        return $result;
    }

    /**
     * Returns the Entity Fields
     * @param SchemaModel $schemaModel
     * @return list<string>
     */
    private static function getEntityFields(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $requested) {
            if ($schemaModel->isRequested($requested->name)) {
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
            switch ($requested->type) {
            case RequestedType::Enum:
                if ($requested->enumClass !== "") {
                    $result[$requested->enumClass] = 1;
                }
                break;
            case RequestedType::Date:
                $result[Date::class] = 1;
                $result[DateType::class] = 1;
                break;
            case RequestedType::File:
                $result[File::class] = 1;
                break;
            case RequestedType::Status:
                $result["{$schemaModel->namespace}\\{$schemaModel->statusClass}"] = 1;
                break;
            default:
            }

            if ($requested->subClass !== "") {
                $result[$requested->subClass] = 1;
            }
            if (Strings::startsWith($requested->subType, "array<")) {
                $result[Arrays::class] = 1;
            }
            if ($schemaModel->isRequested($requested->name)) {
                $result["{$schemaModel->namespace}\\{$schemaModel->entityClass}"] = 1;
            }
        }
        return array_keys($result);
    }
}
