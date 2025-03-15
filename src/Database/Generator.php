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

    /**
     * Generates the Code for the Schemas
     * @param string  $baseNamespace
     * @param string  $writePath
     * @param boolean $forFramework
     * @return integer
     */
    public static function generateCode(string $baseNamespace, string $writePath, bool $forFramework): int {
        $schemas = Factory::getData();
        $created = 0;

        File::createDir($writePath);
        File::emptyDir($writePath);

        $folders = self::generateFolders($schemas, $writePath, $forFramework);

        foreach ($schemas as $schemaKey => $schemaData) {
            $fromFramework = $schemaData["fromFramework"] ?? false;
            if (($forFramework && !$fromFramework) || (!$forFramework && $fromFramework)) {
                continue;
            }

            $structure = new Structure($schemaKey, $schemaData);
            $namespace = "{$baseNamespace}Schema";
            $path      = $writePath;

            if (!$forFramework) {
                $folder     = $folders[$schemaKey];
                $path      .= "/$folder";
                $namespace .= "\\$folder";
            }

            $schemaName = "{$structure->schema}Schema.php";
            $schemaCode = self::getSchemaCode($structure, $namespace, $folders);
            File::create($path, $schemaName, $schemaCode);

            $entityName = "{$structure->schema}Entity.php";
            $entityCode = self::getEntityCode($structure, $namespace, $folders);
            File::create($path, $entityName, $entityCode);

            $columnName = "{$structure->schema}Column.php";
            $columnCode = self::getColumnCode($structure, $namespace);
            File::create($path, $columnName, $columnCode);

            $queryName = "{$structure->schema}Query.php";
            $queryCode = self::getQueryCode($structure, $namespace);
            File::create($path, $queryName, $queryCode);

            $created += 1;
        }

        $name = $forFramework ? "Framework" : "App";
        print("- Generated the $name codes -> $created schemas\n");
        return $created * 4;
    }

    /**
     * Generates the Folders for the Schemas
     * @param array{} $schemas
     * @param string  $writePath
     * @param boolean $forFramework
     * @return array{}
     */
    private static function generateFolders(array $schemas, string $writePath, bool $forFramework): array {
        $groups  = [];
        $folders = [];

        foreach ($schemas as $schemaKey => $schemaData) {
            $fromFramework = $schemaData["fromFramework"] ?? false;
            if ($fromFramework) {
                continue;
            }

            $found = false;
            foreach ($groups as $group) {
                if (Strings::startsWith($schemaKey, $group)) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $folders[$schemaKey] = $group;
                continue;
            }

            $groups[]            = $schemaKey;
            $folders[$schemaKey] = $schemaKey;

            if (!$forFramework) {
                File::createDir("$writePath/$schemaKey");
            }
        }
        return $folders;
    }



    /**
     * Returns the Schema code
     * @param Structure            $structure
     * @param string               $namespace
     * @param array<string,string> $folders
     * @return string
     */
    private static function getSchemaCode(Structure $structure, string $namespace, array $folders): string {
        $idType      = self::getFieldType($structure->idType);
        $fields      = self::getAllFields($structure);
        $uniques     = self::getFieldList($structure, "isUnique");
        $parents     = self::getFieldList($structure, "isParent");
        $subTypes    = self::getSubTypes($structure->subRequests, $folders);
        $editParents = $structure->hasPositions ? $parents : [];
        $queryName   = "{$structure->schema}Query";

        $template    = Discovery::loadFrameTemplate("Schema.mu");
        $contents    = Mustache::render($template, [
            "appNamespace"     => Package::Namespace,
            "namespace"        => $namespace,
            "name"             => $structure->schema,
            "column"           => "{$structure->schema}Column",
            "entity"           => "{$structure->schema}Entity",
            "query"            => $queryName,
            "hasID"            => $structure->hasID,
            "idKey"            => $structure->idKey,
            "idName"           => $structure->idName,
            "idType"           => $idType,
            "idDocType"        => self::getDocType($idType),
            "hasIntID"         => $structure->hasID && $idType === "int",
            "idText"           => Strings::upperCaseFirst($structure->idName),
            "editType"         => $structure->hasID ? "$queryName|$idType" : $queryName,
            "editDocType"      => $structure->hasID ? "$queryName|" . self::getDocType($idType) : $queryName,
            "convertType"      => $structure->hasID ? "Query|$idType" : "Query",
            "convertDocType"   => $structure->hasID ? "Query|" . self::getDocType($idType) : "Query",
            "hasName"          => $structure->nameKey !== "" && !Arrays::contains($uniques, $structure->nameKey, "fieldName"),
            "nameKey"          => $structure->nameKey,
            "hasSelect"        => $structure->nameKey !== "",
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
            "canConvert"       => $structure->canEdit || $structure->canDelete || $structure->canRemove,
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
        return Strings::replace($contents, "(, ", "(");
    }

    /**
     * Returns the Sub Types from the Sub Requests
     * @param SubRequest[]         $subRequests
     * @param array<string,string> $folders
     * @return array{}[]
     */
    private static function getSubTypes(array $subRequests, array $folders): array {
        $result = [];
        foreach ($subRequests as $subRequest) {
            if (!Strings::contains($subRequest->type, "<", "[")) {
                $result[] = [
                    "name"   => $subRequest->name,
                    "type"   => $subRequest->type,
                    "folder" => $folders[$subRequest->type] ?? "",
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
            "fieldParamQuery" => $type === "bool" ? "$param === true ? 1 : 0" : $param,
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
     * Returns the Entity code
     * @param Structure            $structure
     * @param string               $namespace
     * @param array<string,string> $folders
     * @return string
     */
    private static function getEntityCode(Structure $structure, string $namespace, array $folders): string {
        $template = Discovery::loadFrameTemplate("Entity.mu");
        $contents = Mustache::render($template, [
            "appNamespace" => Package::Namespace,
            "namespace"    => $namespace,
            "name"         => $structure->schema,
            "id"           => $structure->idName,
            "subTypes"     => self::getSubTypes($structure->subRequests, $folders),
            "attributes"   => self::getAttributes($structure),
        ]);
        return $contents;
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

        return self::parseAttributes($result);
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
     * Returns the Column code
     * @param Structure $structure
     * @param string    $namespace
     * @return string
     */
    private static function getColumnCode(Structure $structure, string $namespace): string {
        $template = Discovery::loadFrameTemplate("Column.mu");
        $contents = Mustache::render($template, [
            "namespace" => $namespace,
            "name"      => $structure->schema,
            "columns"   => self::getColumns($structure),
        ]);
        return $contents;
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
                    "value"    => "{$table}.{$field->key}",
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
     * Returns the Query code
     * @param Structure $structure
     * @param string    $namespace
     * @return string
     */
    private static function getQueryCode(Structure $structure, string $namespace): string {
        $template = Discovery::loadFrameTemplate("Query.mu");
        $contents = Mustache::render($template, [
            "namespace"  => $namespace,
            "name"       => $structure->schema,
            "column"     => "{$structure->schema}Column",
            "query"      => "{$structure->schema}Query",
            "properties" => self::getProperties($structure),
        ]);
        $contents = self::alignParams($contents);
        return $contents;
    }

    /**
     * Returns the Field properties for the Query
     * @param Structure $structure
     * @return array{}
     */
    private static function getProperties(Structure $structure): array {
        $nameLength = 0;
        $typeLength = 0;
        $list       = [];
        $result     = [];

        foreach ($structure->fields as $field) {
            $list[] = [
                "type"   => $field->type,
                "column" => $field->name,
                "name"   => $field->name,
                "value"  => "{$structure->table}.{$field->key}",
            ];
        }

        foreach ($structure->expressions as $expression) {
            $list[] = [
                "type"   => $expression->type,
                "column" => $expression->name,
                "name"   => $expression->prefixName,
                "value"  => $expression->key,
            ];
        }

        foreach ($structure->joins as $join) {
            foreach ($join->fields as $field) {
                $table  = $join->asTable ?: $join->table;
                $list[] = [
                    "type"   => $field->type,
                    "column" => $field->name,
                    "name"   => $field->prefixName,
                    "value"  => "{$table}.{$field->key}",
                ];
            }
        }

        foreach ($list as $property) {
            $type = $property["type"];
            if ($property["column"] === "status") {
                $property["type"] = "StatusQuery";
            } else {
                $property["type"] = match($type) {
                    Field::Boolean => "BooleanQuery",
                    Field::ID,
                    Field::Number,
                    Field::Date    => "NumberQuery",
                    Field::Float   => "FloatQuery",
                    Field::String,
                    Field::Text,
                    Field::LongText,
                    Field::JSON,
                    Field::HTML    => "StringQuery",
                    default        => "",
                };
            }
            if ($property["type"] !== "") {
                $nameLength = max($nameLength, Strings::length($property["name"]));
                $typeLength = max($typeLength, Strings::length($property["type"]));

                $result[] = $property;
            }
        }

        foreach ($result as $index => $property) {
            $result[$index]["propName"]  = Strings::padRight("{$property["name"]};", $nameLength + 1);
            $result[$index]["propType"]  = Strings::padRight($property["type"], $typeLength);
            $result[$index]["constName"] = Strings::padRight($property["name"], $nameLength);
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
