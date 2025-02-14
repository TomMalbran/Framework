<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\File\File;
use Framework\Database\Structure;
use Framework\Database\Field;
use Framework\Provider\Mustache;
use Framework\System\Package;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Generator
 */
class Generator {

    private static string $namespace;
    private static string $writePath;

    private static string $schemaTemplate;
    private static string $entityTemplate;
    private static string $columnTemplate;



    /**
     * Generates the Code for the Schemas
     * @param string  $namespace
     * @param string  $writePath
     * @param boolean $forFramework
     * @return integer
     */
    public static function generateCode(string $namespace, string $writePath, bool $forFramework): int {
        self::$namespace = $namespace;
        self::$writePath = $writePath;

        self::$schemaTemplate = Discovery::loadFrameTemplate("Schema.mu");
        self::$entityTemplate = Discovery::loadFrameTemplate("Entity.mu");
        self::$columnTemplate = Discovery::loadFrameTemplate("Column.mu");

        $schemas = Factory::getData();
        $created = 0;

        File::createDir(self::$writePath);
        File::emptyDir(self::$writePath);

        foreach ($schemas as $schemaKey => $schemaData) {
            $fromFramework = $schemaData["fromFramework"] ?? false;
            if (($forFramework && !$fromFramework) || (!$forFramework && $fromFramework)) {
                continue;
            }

            $structure = new Structure($schemaKey, $schemaData);
            if (!self::createSchema($structure)) {
                continue;
            }
            if (!self::createEntity($structure)) {
                continue;
            }
            if (!self::createColumn($structure)) {
                continue;
            }
            $created += 1;
        }

        $name = $forFramework ? "Framework" : "App";
        print("- Generated the $name codes -> $created schemas\n");
        return $created * 3;
    }



    /**
     * Creates the Schema file
     * @param Structure $structure
     * @return boolean
     */
    private static function createSchema(Structure $structure): bool {
        $fileName    = "{$structure->schema}Schema.php";
        $idType      = self::getFieldType($structure->idType);
        $fields      = self::getAllFields($structure);
        $uniques     = self::getFieldList($structure, "isUnique");
        $parents     = self::getFieldList($structure, "isParent");
        $subTypes    = self::getSubTypes($structure->subRequests);
        $editParents = $structure->hasPositions ? $parents : [];

        $contents    = Mustache::render(self::$schemaTemplate, [
            "appNamespace"     => Package::Namespace,
            "namespace"        => self::$namespace,
            "name"             => $structure->schema,
            "column"           => "{$structure->schema}Column",
            "entity"           => "{$structure->schema}Entity",
            "hasID"            => $structure->hasID,
            "idKey"            => $structure->idKey,
            "idName"           => $structure->idName,
            "idType"           => $idType,
            "idDocType"        => self::getDocType($idType),
            "hasIntID"         => $structure->hasID && $idType === "int",
            "idText"           => Strings::upperCaseFirst($structure->idName),
            "editType"         => $structure->hasID ? "Query|$idType" : "Query",
            "editDocType"      => $structure->hasID ? "Query|" . self::getDocType($idType) : "Query",
            "hasName"          => !empty($structure->nameKey) && !Arrays::contains($uniques, $structure->nameKey, "fieldName"),
            "nameKey"          => $structure->nameKey,
            "hasSelect"        => !empty($structure->nameKey),
            "hasPositions"     => $structure->hasPositions,
            "hasTimestamps"    => $structure->hasTimestamps,
            "hasStatus"        => $structure->hasStatus,
            "hasUsers"         => $structure->hasUsers,
            "hasFilters"       => $structure->hasFilters,
            "hasEncrypt"       => $structure->hasEncrypt,
            "canCreate"        => $structure->canCreate,
            "canEdit"          => $structure->canEdit,
            "canReplace"       => $structure->canEdit && !$structure->hasAutoInc,
            "canDelete"        => $structure->canDelete,
            "canRemove"        => $structure->canRemove,
            "processEntity"    => !empty($subTypes) || !empty($structure->processed) || $structure->hasStatus,
            "subTypes"         => $subTypes,
            "hasProcessed"     => !empty($structure->processed),
            "fields"           => $fields,
            "fieldsCreateList" => self::joinFields($fields, "fieldArgCreate", ", "),
            "fieldsEditList"   => self::joinFields($fields, "fieldArgEdit", ", "),
            "uniques"          => $uniques,
            "parents"          => $parents,
            "editParents"      => $editParents,
            "parentsList"      => self::joinFields($parents, "fieldParam"),
            "parentsArgList"   => self::joinFields($parents, "fieldArg"),
            "parentsNullList"  => self::joinFields($parents, "fieldArgNull", ", "),
            "parentsDefList"   => self::joinFields($parents, "fieldArgDefault", ", "),
            "parentsEditList"  => self::joinFields($editParents, "fieldArg", ", "),
            "hasParents"       => !empty($parents),
            "hasEditParents"   => $structure->hasPositions && !empty($parents),
            "hasMainQuery"     => $structure->hasFilters || !empty($parents),
        ]);

        $contents = self::alignParams($contents);
        $contents = Strings::replace($contents, "(, ", "(");
        return File::create(self::$writePath, $fileName, $contents);
    }

    /**
     * Returns the Sub Types from the Sub Requests
     * @param SubRequest[] $subRequests
     * @return array{}[]
     */
    private static function getSubTypes(array $subRequests): array {
        $result = [];
        foreach ($subRequests as $subRequest) {
            if (!Strings::contains($subRequest->type, "<", "[")) {
                $result[] = [
                    "name" => $subRequest->name,
                    "type" => $subRequest->type,
                ];
            }
        }
        return $result;
    }

    /**
     * Returns a list of all the Fields to set
     * @param Structure $structure
     * @return array{}[]
     */
    private static function getAllFields(Structure $structure): array {
        $skipKeys = [ "createdTime", "createdUser", "modifiedTime", "modifiedUser", "isDeleted" ];
        $result   = [];

        foreach ($structure->fields as $field) {
            if ($field->isID || Arrays::contains($skipKeys, $field->key)) {
                continue;
            }
            if ($structure->hasStatus && $field->key === "status") {
                continue;
            }
            $result[] = self::getField($field);
        }
        return $result;
    }

    /**
     * Returns a list of Fields with the given property
     * @param Structure $structure
     * @param string    $property
     * @return array{}[]
     */
    private static function getFieldList(Structure $structure, string $property): array {
        $result = [];
        foreach ($structure->fields as $field) {
            if ($field->{$property}) {
                $result[] = self::getField($field);
            }
        }
        return $result;
    }

    /**
     * Returns the Fields data for the Schema
     * @param Field $field
     * @return array{}
     */
    private static function getField(Field $field): array {
        $type      = self::getFieldType($field->type);
        $docType   = self::getDocType($type);
        $default   = self::getDefault($type);
        $canAssign = !$field->isID && !$field->isParent;
        $assignDoc = $canAssign ? "Assign|" : "";
        $param     = "\${$field->name}";

        return [
            "fieldKey"        => $field->key,
            "fieldName"       => $field->name,
            "fieldText"       => Strings::upperCaseFirst($field->name),
            "fieldDoc"        => "$docType $param",
            "fieldDocNull"    => "$docType|null $param",
            "fieldDocEdit"    => "$assignDoc$docType|null $param",
            "fieldParam"      => $param,
            "fieldArg"        => "$type $param",
            "fieldArgNull"    => "?$type $param",
            "fieldArgDefault" => "$type $param = $default",
            "fieldArgCreate"  => "?$type $param = null",
            "fieldArgEdit"    => ($canAssign ? "Assign|$type|null" : "?$type") . " $param = null",
        ];
    }

    /**
     * Summary of joinFields
     * @param array{}[] $fields
     * @param string    $key
     * @param string    $prefix Optional.
     * @return string
     */
    private static function joinFields(array $fields, string $key, string $prefix = ""): string {
        if (empty($fields)) {
            return "";
        }
        $list   = Arrays::createArray($fields, $key);
        $result = Strings::join($list, ", ");
        return $prefix . $result;
    }

    /**
     * Aligns the Params of the given content
     * @param string $contents
     * @return string
     */
    private static function alignParams(string $contents): string {
        $lines      = Strings::split($contents, "\n");
        $typeLength = 0;
        $varLength  = 0;
        $result     = [];

        foreach ($lines as $index => $line) {
            if (Strings::contains($line, "/**")) {
                [ $typeLength, $varLength ] = self::getLongestParam($lines, $index);
            } elseif (Strings::contains($line, "@param")) {
                $docType    = Strings::substringBetween($line, "@param ", " ");
                $docTypePad = Strings::padRight($docType, $typeLength);
                $line       = Strings::replace($line, $docType, $docTypePad);
                if (Strings::contains($line, "Optional.")) {
                    $varName    = Strings::substringBetween($line, "$docTypePad ", " Optional.");
                    $varNamePad = Strings::padRight($varName, $varLength);
                    $line       = Strings::replace($line, $varName, $varNamePad);
                }
            }
            $result[] = $line;
        }
        return Strings::join($result, "\n");
    }

    /**
     * Returns the longest Param and Type of the current Doc comment
     * @param string[] $lines
     * @param integer  $index
     * @return integer[]
     */
    private static function getLongestParam(array $lines, int $index): array {
        $line       = $lines[$index];
        $typeLength = 0;
        $varLength  = 0;

        while (!Strings::contains($line, "*/")) {
            if (Strings::contains($line, "@param")) {
                $docType    = Strings::substringBetween($line, "@param ", " ");
                $typeLength = max($typeLength, Strings::length($docType));
                $varName    = Strings::substringBetween($line, "$docType ", " Optional.");
                $varLength  = max($varLength, Strings::length($varName));
            }
            $index += 1;
            $line   = $lines[$index];
        }
        return [ $typeLength, $varLength ];
    }



    /**
     * Creates the Entity file
     * @param Structure $structure
     * @return boolean
     */
    private static function createEntity(Structure $structure): bool {
        $fileName   = "{$structure->schema}Entity.php";
        $attributes = self::getAttributes($structure);
        $contents   = Mustache::render(self::$entityTemplate, [
            "appNamespace" => Package::Namespace,
            "namespace"    => self::$namespace,
            "name"         => $structure->schema,
            "subTypes"     => self::getSubTypes($structure->subRequests),
            "attributes"   => self::parseAttributes($attributes),
        ]);
        return File::create(self::$writePath, $fileName, $contents);
    }

    /**
     * Returns the Field attributes for the Entity
     * @param Structure $structure
     * @return array{}
     */
    private static function getAttributes(Structure $structure): array {
        $result = [];
        if ($structure->hasID) {
            $type     = self::getFieldType($structure->idType);
            $result[] = self::getTypeData("id", $type);
        }
        foreach ($structure->fields as $field) {
            self::addAttribute($result, $field);
        }
        if ($structure->hasStatus) {
            $result[] = self::getTypeData("statusName", "string");
            $result[] = self::getTypeData("statusColor", "string");
        }
        foreach ($structure->processed as $field) {
            self::addAttribute($result, $field);
        }
        foreach ($structure->expressions as $field) {
            self::addAttribute($result, $field);
        }
        foreach ($structure->joins as $join) {
            foreach ($join->fields as $field) {
                self::addAttribute($result, $field);
            }
            foreach ($join->merges as $merge) {
                $result[] = self::getTypeData($merge->key, "string");
            }
            foreach ($join->defaults as $key => $values) {
                $result[] = self::getTypeData($key, "string");
            }
        }
        foreach ($structure->counts as $count) {
            self::addAttribute($result, $count->field);
        }
        foreach ($structure->subRequests as $subRequest) {
            $type = $subRequest->type;
            if (!Strings::contains($type, "<", "[")) {
                $type .= "Entity[]";
            }
            $result[] = self::getTypeData($subRequest->name, "array", $type);
        }
        return $result;
    }

    /**
     * Adds the Field types for the Entity
     * @param object[] $result
     * @param Field    $field
     * @return boolean
     */
    private static function addAttribute(array &$result, Field $field): bool {
        $key = $field->prefixName;

        switch ($field->type) {
        case Field::Boolean:
            $result[] = self::getTypeData($key, "bool");
            break;
        case Field::ID:
        case Field::Number:
            $result[] = self::getTypeData($key, "int");
            break;
        case Field::Float:
            $result[] = self::getTypeData($key, "float");
            break;
        case Field::Date:
            $result[] = self::getTypeData($key, "int");
            $result[] = self::getTypeData("{$key}Date", "string");
            $result[] = self::getTypeData("{$key}Full", "string");
            break;
        case Field::JSON:
            $result[] = self::getTypeData($key, "mixed");
            break;
        case Field::CSV:
            $result[] = self::getTypeData($key, "string");
            $result[] = self::getTypeData("{$key}Parts", "array");
            $result[] = self::getTypeData("{$key}Count", "int");
            break;
        case Field::HTML:
            $result[] = self::getTypeData($key, "string");
            $result[] = self::getTypeData("{$key}Html", "string");
            break;
        case Field::File:
            $result[] = self::getTypeData($key, "string");
            $result[] = self::getTypeData("{$key}Url", "string");
            $result[] = self::getTypeData("{$key}Thumb", "string");
            break;
        default:
            $result[] = self::getTypeData($key, "string");
        }
        return true;
    }

    /**
     * Returns the Type and Default
     * @param string $name
     * @param string $type
     * @param string $subType
     * @return object
     */
    private static function getTypeData(string $name, string $type, string $subType = ""): object {
        return (object)[
            "name"    => $name,
            "type"    => $type,
            "subType" => $subType,
            "default" => self::getDefault($type),
        ];
    }

    /**
     * Parses the Attributes
     * @param array{} $attributes
     * @return string[]
     */
    private static function parseAttributes(array $attributes): array {
        $nameLength = 0;
        $typeLength = 0;
        $result     = [];
        $parsed     = [];

        foreach ($attributes as $attribute) {
            $nameLength = max($nameLength, Strings::length($attribute->name));
            $typeLength = max($typeLength, Strings::length($attribute->type));
        }

        foreach ($attributes as $attribute) {
            if (!empty($parsed[$attribute->name])) {
                continue;
            }
            $result[] = [
                "type"    => Strings::padRight($attribute->type, $typeLength),
                "name"    => Strings::padRight($attribute->name, $nameLength),
                "default" => $attribute->default,
                "subType" => $attribute->subType,
            ];
            $parsed[$attribute->name] = true;
        }
        return $result;
    }



    /**
     * Creates the Column file
     * @param Structure $structure
     * @return boolean
     */
    private static function createColumn(Structure $structure): bool {
        $fileName = "{$structure->schema}Column.php";
        $contents = Mustache::render(self::$columnTemplate, [
            "namespace" => self::$namespace,
            "name"      => $structure->schema,
            "columns"   => self::getColumns($structure),
        ]);
        return File::create(self::$writePath, $fileName, $contents);
    }

    /**
     * Returns the Field columns for the Column
     * @param Structure $structure
     * @return array{}
     */
    private static function getColumns(Structure $structure): array {
        $addSpace = true;
        $result   = [];

        foreach ($structure->fields as $field) {
            $result[] = [
                "name"     => Strings::upperCaseFirst($field->name),
                "value"    => "{$structure->table}.{$field->key}",
                "addSpace" => false,
            ];
            if ($field->type === Field::File) {
                $result[] = [
                    "name"     => Strings::upperCaseFirst("{$field->name}Url"),
                    "value"    => "{$field->name}Url",
                    "addSpace" => false,
                ];
            }
        }

        foreach ($structure->expressions as $expression) {
            $expName = Strings::upperCaseFirst($expression->prefixName);
            if (!Arrays::contains($result, $expName, "name")) {
                $result[] = [
                    "name"     => $expName,
                    "value"    => $expression->key,
                    "addSpace" => $addSpace,
                ];
            }
            $addSpace = false;
        }

        foreach ($structure->joins as $join) {
            $addSpace = true;
            foreach ($join->fields as $field) {
                $table    = $join->asTable ?: $join->table;
                $result[] = [
                    "name"     => Strings::upperCaseFirst($field->prefixName),
                    "value"    => "{$table}.{$field->name}",
                    "addSpace" => $addSpace,
                ];
                $addSpace = false;
            }
            foreach ($join->merges as $merge) {
                $mergeName = Strings::upperCaseFirst($merge->key);
                if (!Arrays::contains($result, $mergeName, "name")) {
                    $result[] = [
                        "name"     => $mergeName,
                        "value"    => $merge->key,
                        "addSpace" => false,
                    ];
                }
            }
        }


        $nameLength = 0;
        foreach ($result as $index => $column) {
            $nameLength = max($nameLength, Strings::length($column["name"]));
        }
        foreach ($result as $index => $column) {
            $result[$index]["name"] = Strings::padRight($column["name"], $nameLength);
        }
        return $result;
    }



    /**
     * Returns the Field type for the Schema
     * @param string $type
     * @return string
     */
    private static function getFieldType(string $type): string {
        return match ($type) {
            Field::Boolean => "bool",
            Field::ID, Field::Number, Field::Date => "int",
            Field::Float => "float",
            default => "string",
        };
    }

    /**
     * Converts a PHP Type to a Document Type
     * @param string $type
     * @return string
     */
    private static function getDocType(string $type): string {
        return match ($type) {
            "bool"  => "boolean",
            "int"   => "integer",
            default => $type,
        };
    }

    /**
     * Converts a PHP Type to a Document Type
     * @param string $type
     * @return string
     */
    private static function getDefault(string $type): string {
        return match ($type) {
            "boolean", "bool" => "false",
            "integer", "int"  => "0",
            "float"           => "0",
            "string"          => '""',
            "array"           => '[]',
            default           => "null",
        };
    }
}
