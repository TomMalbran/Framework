<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Builder\Builder;
use Framework\Date\Date;
use Framework\Date\Type\DateType;
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
        foreach ($schemaModel->requestedFields as $requested) {
            if (!$requested->isNative) {
                continue;
            }

            $type = $requested->type->getCodeType($requested->enumClass);

            switch ($requested->type) {
            case FieldType::Enum:
                $getter   = "{$type}::fromRequest(\$this->request, \"{$requested->name}\")";
                break;

            case FieldType::Date:
                $dateType = $requested->dateType->toString();
                $name     = $requested->dateInput !== "" ? $requested->dateInput : $requested->name;
                $hour     = $requested->hourInput !== "" ? ", \"{$requested->hourInput}\"" : "";
                $getter   = "\$this->request->toDayMoment(\"{$name}\", DateType::{$dateType}{$hour})";
                break;

            case FieldType::Array:
                if ($requested->subClass !== "") {
                    $typeName = Strings::substringAfter($requested->subClass, "\\");
                    $getter   = "{$typeName}::fromList(\$this->request->getStrings(\"{$requested->name}\"))";
                } else {
                    $typeName = Strings::substringBetween($requested->subType, "list<", ">");
                    $typeName = Strings::upperCaseFirst($typeName);
                    $getter   = "\$this->request->get{$typeName}s(\"{$requested->name}\")";
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
     * Returns the Field properties
     * @param SchemaModel $schemaModel
     * @return list<Property>
     */
    private static function getProperties(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->requestedFields as $requested) {
            if (!$requested->hasValue) {
                continue;
            }

            $type = $requested->getValueType();
            if ($type === "") {
                continue;
            }

            $value = $requested->name;
            if ($requested->dateInput !== "") {
                $value = $requested->dateInput;
            }

            $extras = "";
            if ($requested->type === FieldType::Float) {
                $extras = ", {$requested->decimals}";
            } elseif ($requested->type === FieldType::Date) {
                $extras = ", \"{$requested->hourInput}\"";
                if ($requested->dateType !== DateType::None) {
                    $extras .= ", DateType::{$requested->dateType->toString()}";
                }
            } elseif ($requested->type === FieldType::Encrypt) {
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
     * Returns the Dictionary properties
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
            if ($requested->isNative && $requested->type === FieldType::Enum) {
                $result[$requested->enumClass] = 1;
            }
            if ($requested->isNative && $requested->subClass !== "") {
                $result[$requested->subClass] = 1;
            }
            if ($requested->isNative && $requested->type === FieldType::Date) {
                $result[Date::class] = 1;
            }
            if ($requested->dateType !== DateType::None) {
                $result[DateType::class] = 1;
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
}
