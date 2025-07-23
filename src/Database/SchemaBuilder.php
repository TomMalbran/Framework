<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;
use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\File\File;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Schema Builder
 */
class SchemaBuilder {

    /**
     * Generates the Code for the Schemas
     * @param boolean $forFramework
     * @return integer
     */
    public static function generateCode(bool $forFramework): int {
        $schemaModels = SchemaFactory::buildData($forFramework);
        $created      = 0;

        foreach ($schemaModels as $schemaModel) {
            if (!$schemaModel->fromFramework) {
                File::createDir($schemaModel->path);
                File::emptyDir($schemaModel->path);
            }
        }

        foreach ($schemaModels as $schemaModel) {
            $schemaName = "{$schemaModel->name}Schema.php";
            $schemaCode = self::getSchemaCode($schemaModel);
            File::create($schemaModel->path, $schemaName, $schemaCode);

            $entityName = "{$schemaModel->name}Entity.php";
            $entityCode = self::getEntityCode($schemaModel);
            File::create($schemaModel->path, $entityName, $entityCode);

            $columnName = "{$schemaModel->name}Column.php";
            $columnCode = self::getColumnCode($schemaModel);
            File::create($schemaModel->path, $columnName, $columnCode);

            $queryName = "{$schemaModel->name}Query.php";
            $queryCode = self::getQueryCode($schemaModel);
            File::create($schemaModel->path, $queryName, $queryCode);

            $created += 1;
        }

        $name = $forFramework ? "Framework" : "App";
        print("- Generated the $name codes -> $created schemas\n");
        return $created * 4;
    }



    /**
     * Returns the Schema code
     * @param SchemaModel $schemaModel
     * @return string
     */
    private static function getSchemaCode(SchemaModel $schemaModel): string {
        $mainFields  = $schemaModel->toBuildData("mainFields");
        $expressions = $schemaModel->toBuildData("expressions");
        $counts      = $schemaModel->toBuildData("counts");
        $relations   = $schemaModel->toBuildData("relations");
        $idType      = self::getFieldType($schemaModel->idType);
        $fields      = self::getAllFields($schemaModel);
        $uniques     = self::getSomeFields($schemaModel, isUnique: true);
        $parents     = self::getSomeFields($schemaModel, isParent: true);
        $subTypes    = self::getSubTypes($schemaModel);
        $hasVirtual  = count($schemaModel->virtualFields) > 0;
        $hasParents  = count($parents) > 0;
        $editParents = $schemaModel->hasPositions ? $parents : [];
        $queryName   = "{$schemaModel->name}Query";

        $template    = Discovery::loadFrameTemplate("Schema.mu");
        $contents    = Mustache::render($template, [
            "namespace"          => $schemaModel->namespace,
            "name"               => $schemaModel->name,
            "table"              => $schemaModel->tableName,
            "column"             => "{$schemaModel->name}Column",
            "entity"             => "{$schemaModel->name}Entity",
            "query"              => $queryName,
            "hasID"              => $schemaModel->hasID,
            "idName"             => $schemaModel->idName,
            "idDbName"           => $schemaModel->idDbName,
            "idType"             => $idType,
            "idDocType"          => self::getDocType($idType),
            "hasIntID"           => $schemaModel->hasID && $idType === "int",
            "idText"             => Strings::upperCaseFirst($schemaModel->idName),
            "editType"           => $schemaModel->hasID ? "$queryName|$idType" : $queryName,
            "editDocType"        => $schemaModel->hasID ? "$queryName|" . self::getDocType($idType) : $queryName,
            "convertType"        => $schemaModel->hasID ? "Query|$idType" : "Query",
            "convertDocType"     => $schemaModel->hasID ? "Query|" . self::getDocType($idType) : "Query",
            "hasPositions"       => $schemaModel->hasPositions,
            "hasPositionsValue"  => $schemaModel->hasPositions ? "true" : "false",
            "hasTimestamps"      => $schemaModel->hasTimestamps,
            "hasTimestampsValue" => $schemaModel->hasTimestamps ? "true" : "false",
            "hasStatus"          => $schemaModel->hasStatus,
            "hasStatusValue"     => $schemaModel->hasStatus ? "true" : "false",
            "hasUsers"           => $schemaModel->hasUsers,
            "hasUsersValue"      => $schemaModel->hasUsers ? "true" : "false",
            "hasEncrypt"         => $schemaModel->hasEncrypt(),
            "canCreate"          => $schemaModel->canCreate,
            "canCreateValue"     => $schemaModel->canCreate ? "true" : "false",
            "canEdit"            => $schemaModel->canEdit,
            "canEditValue"       => $schemaModel->canEdit ? "true" : "false",
            "canReplace"         => $schemaModel->canEdit && !$schemaModel->hasAutoInc,
            "canDelete"          => $schemaModel->canDelete,
            "canDeleteValue"     => $schemaModel->canDelete ? "true" : "false",
            "processEntity"      => count($subTypes) > 0 || $hasVirtual || $schemaModel->hasStatus,
            "subTypes"           => $subTypes,
            "hasVirtual"         => $hasVirtual,
            "mainFields"         => $mainFields,
            "hasExpressions"     => count($expressions) > 0,
            "expressions"        => $expressions,
            "hasCounts"          => count($counts) > 0,
            "counts"             => $counts,
            "hasRelations"       => count($relations) > 0,
            "relations"          => $relations,
            "fields"             => $fields,
            "fieldsCreateList"   => self::joinFields($fields, "fieldArgCreate", ", "),
            "fieldsEditList"     => self::joinFields($fields, "fieldArgEdit", ", "),
            "uniques"            => $uniques,
            "parents"            => $parents,
            "editParents"        => $editParents,
            "parentsList"        => self::joinFields($parents, "fieldParam"),
            "parentsArgList"     => self::joinFields($parents, "fieldArg"),
            "parentsNullList"    => self::joinFields($parents, "fieldArgNull", ", "),
            "parentsDefList"     => self::joinFields($parents, "fieldArgDefault", ", "),
            "parentsEditList"    => self::joinFields($editParents, "fieldArg", ", "),
            "hasParents"         => $hasParents,
            "hasEditParents"     => $schemaModel->hasPositions && $hasParents,
        ]);

        $contents = self::alignParams($contents);
        return Strings::replace($contents, "(, ", "(");
    }

    /**
     * Returns the Sub Types from the Sub Requests
     * @param SchemaModel $schemaModel
     * @return array{name:string,type:string,namespace:string}[]
     */
    private static function getSubTypes(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->subRequests as $subRequest) {
            if ($subRequest->type === "") {
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
     * Returns a list of all the Fields to set
     * @param SchemaModel $schemaModel
     * @return array<string,string>[]
     */
    private static function getAllFields(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->mainFields as $field) {
            if (!$field->isAutoInc()) {
                $result[] = self::getField($field);
            }
        }
        foreach ($schemaModel->extraFields as $field) {
            if ($field->name === "position") {
                $result[] = self::getField($field);
            }
        }
        return $result;
    }

    /**
     * Returns a list of Fields with the given property
     * @param SchemaModel $schemaModel
     * @param boolean     $isUnique    Optional.
     * @param boolean     $isParent    Optional.
     * @return array<string,string>[]
     */
    private static function getSomeFields(SchemaModel $schemaModel, bool $isUnique = false, bool $isParent = false): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($isUnique && $field->isUnique) {
                $result[] = self::getField($field);
            } elseif ($isParent && $field->isParent) {
                $result[] = self::getField($field);
            }
        }
        return $result;
    }

    /**
     * Returns the Fields data for the Schema
     * @param Field $field
     * @return array<string,string>
     */
    private static function getField(Field $field): array {
        $type      = self::getFieldType($field->type);
        $docType   = self::getDocType($type);
        $default   = self::getDefault($type);
        $canAssign = !$field->isID && !$field->isParent;
        $assignDoc = $canAssign ? "Assign|" : "";
        $param     = "\${$field->name}";

        return [
            "fieldKey"        => $field->dbName,
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
        if (count($fields) === 0) {
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
     * @param SchemaModel $schemaModel
     * @return string
     */
    private static function getEntityCode(SchemaModel $schemaModel): string {
        $template = Discovery::loadFrameTemplate("Entity.mu");
        $contents = Mustache::render($template, [
            "namespace"  => $schemaModel->namespace,
            "name"       => $schemaModel->name,
            "id"         => $schemaModel->idName,
            "subTypes"   => self::getSubTypes($schemaModel),
            "attributes" => self::getAttributes($schemaModel),
        ]);
        return $contents;
    }

    /**
     * Returns the Field attributes for the Entity
     * @param SchemaModel $schemaModel
     * @return array{name:string,type:string,subType:string,default:string}[]
     */
    private static function getAttributes(SchemaModel $schemaModel): array {
        $result = [];
        if ($schemaModel->hasID) {
            $type     = self::getFieldType($schemaModel->idType);
            $result[] = self::getTypeData("id", $type);
        }

        foreach ($schemaModel->fields as $field) {
            self::addAttribute($result, $field->name, $field->type);
        }
        foreach ($schemaModel->virtualFields as $field) {
            self::addAttribute($result, $field->name, $field->type);
        }
        if ($schemaModel->hasStatus) {
            $result[] = self::getTypeData("statusName", "string");
            $result[] = self::getTypeData("statusColor", "string");
        }

        foreach ($schemaModel->expressions as $expression) {
            self::addAttribute($result, $expression->name, $expression->type);
        }
        foreach ($schemaModel->counts as $count) {
            self::addAttribute($result, $count->name, FieldType::Number);
        }
        foreach ($schemaModel->relations as $relation) {
            foreach ($relation->fields as $field) {
                self::addAttribute($result, $field->prefixName, $field->type);
            }
        }
        foreach ($schemaModel->subRequests as $subRequest) {
            $type = $subRequest->type;
            if ($type === "") {
                $type = "{$subRequest->modelName}Entity[]";
            }
            $result[] = self::getTypeData($subRequest->name, "array", $type);
        }

        return self::parseAttributes($result);
    }

    /**
     * Adds the Field types for the Entity
     * @param array{name:string,type:string,subType:string,default:string}[] $result
     * @param string                                                         $fieldKey
     * @param FieldType                                                      $fieldType
     * @return boolean
     */
    private static function addAttribute(array &$result, string $fieldKey, FieldType $fieldType): bool {
        switch ($fieldType) {
        case FieldType::Boolean:
            $result[] = self::getTypeData($fieldKey, "bool");
            break;
        case FieldType::Number:
            $result[] = self::getTypeData($fieldKey, "int");
            break;
        case FieldType::Float:
            $result[] = self::getTypeData($fieldKey, "float");
            break;
        case FieldType::JSON:
            $result[] = self::getTypeData($fieldKey, "mixed");
            break;
        case FieldType::File:
            $result[] = self::getTypeData($fieldKey, "string");
            $result[] = self::getTypeData("{$fieldKey}Url", "string");
            $result[] = self::getTypeData("{$fieldKey}Thumb", "string");
            break;
        default:
            $result[] = self::getTypeData($fieldKey, "string");
        }
        return true;
    }

    /**
     * Returns the Type and Default
     * @param string $name
     * @param string $type
     * @param string $subType
     * @return array{name:string,type:string,subType:string,default:string}
     */
    private static function getTypeData(string $name, string $type, string $subType = ""): array {
        return [
            "name"    => $name,
            "type"    => $type,
            "subType" => $subType,
            "default" => self::getDefault($type),
        ];
    }

    /**
     * Parses the Attributes
     * @param array{name:string,type:string,subType:string,default:string}[] $attributes
     * @return array{name:string,type:string,subType:string,default:string}[]
     */
    private static function parseAttributes(array $attributes): array {
        $nameLength = 0;
        $typeLength = 0;
        $result     = [];
        $parsed     = [];

        foreach ($attributes as $attribute) {
            $nameLength = max($nameLength, Strings::length($attribute["name"]));
            $typeLength = max($typeLength, Strings::length($attribute["type"]));
        }

        foreach ($attributes as $attribute) {
            if (isset($parsed[$attribute["name"]])) {
                continue;
            }
            $result[] = [
                "name"    => Strings::padRight($attribute["name"], $nameLength),
                "type"    => Strings::padRight($attribute["type"], $typeLength),
                "subType" => $attribute["subType"],
                "default" => $attribute["default"],
            ];
            $parsed[$attribute["name"]] = true;
        }
        return $result;
    }



    /**
     * Returns the Column code
     * @param SchemaModel $schemaModel
     * @return string
     */
    private static function getColumnCode(SchemaModel $schemaModel): string {
        $template = Discovery::loadFrameTemplate("Column.mu");
        $contents = Mustache::render($template, [
            "namespace" => $schemaModel->namespace,
            "name"      => $schemaModel->name,
            "columns"   => self::getColumns($schemaModel),
        ]);
        return $contents;
    }

    /**
     * Returns the Field columns for the Column
     * @param SchemaModel $schemaModel
     * @return array{}
     */
    private static function getColumns(SchemaModel $schemaModel): array {
        $addSpace = true;
        $result   = [];

        foreach ($schemaModel->fields as $field) {
            $result[] = [
                "name"     => Strings::upperCaseFirst($field->name),
                "value"    => "{$schemaModel->tableName}.{$field->dbName}",
                "addSpace" => false,
            ];
            if ($field->type === FieldType::File) {
                $result[] = [
                    "name"     => Strings::upperCaseFirst("{$field->name}Url"),
                    "value"    => "{$field->name}Url",
                    "addSpace" => false,
                ];
            }
        }

        foreach ($schemaModel->expressions as $expression) {
            $expName = Strings::upperCaseFirst($expression->name);
            if (!Arrays::contains($result, $expName, "name")) {
                $result[] = [
                    "name"     => $expName,
                    "value"    => $expression->name,
                    "addSpace" => $addSpace,
                ];
            }
            $addSpace = false;
        }

        foreach ($schemaModel->relations as $relation) {
            $addSpace  = true;
            $tableName = $relation->getDbTableName();
            foreach ($relation->fields as $field) {
                $result[] = [
                    "name"     => Strings::upperCaseFirst($field->prefixName),
                    "value"    => "{$tableName}.{$field->dbName}",
                    "addSpace" => $addSpace,
                ];
                $addSpace = false;
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
     * @param SchemaModel $schemaModel
     * @return string
     */
    private static function getQueryCode(SchemaModel $schemaModel): string {
        $template = Discovery::loadFrameTemplate("Query.mu");
        $contents = Mustache::render($template, [
            "namespace"  => $schemaModel->namespace,
            "name"       => $schemaModel->name,
            "column"     => "{$schemaModel->name}Column",
            "query"      => "{$schemaModel->name}Query",
            "properties" => self::getQueryProperties($schemaModel),
        ]);
        $contents = self::alignParams($contents);
        return $contents;
    }

    /**
     * Returns the Field properties for the Query
     * @param SchemaModel $schemaModel
     * @return array{}
     */
    private static function getQueryProperties(SchemaModel $schemaModel): array {
        $nameLength = 0;
        $typeLength = 0;
        $list       = [];
        $result     = [];

        foreach ($schemaModel->fields as $field) {
            $list[] = [
                "type"   => $field->type,
                "column" => $field->name,
                "name"   => $field->name,
                "value"  => "{$schemaModel->tableName}.{$field->dbName}",
            ];
        }

        foreach ($schemaModel->expressions as $expression) {
            $list[] = [
                "type"   => $expression->type,
                "column" => $expression->name,
                "name"   => $expression->name,
                "value"  => $expression->name,
            ];
        }

        foreach ($schemaModel->relations as $relation) {
            $tableName = $relation->getDbTableName();
            foreach ($relation->fields as $field) {
                $list[] = [
                    "type"   => $field->type,
                    "column" => $field->name,
                    "name"   => $field->prefixName,
                    "value"  => "{$tableName}.{$field->dbName}",
                ];
            }
        }

        foreach ($list as $property) {
            $type = $property["type"];
            if ($property["column"] === "status") {
                $property["queryType"] = "StatusQuery";
            } else {
                $property["queryType"] = match($type) {
                    FieldType::Boolean => "BooleanQuery",
                    FieldType::Number,
                    FieldType::Float   => "NumberQuery",
                    FieldType::String,
                    FieldType::Text,
                    FieldType::LongText,
                    FieldType::JSON,
                    FieldType::File    => "StringQuery",
                    default            => "",
                };
            }
            if ($property["queryType"] !== "") {
                $nameLength = max($nameLength, Strings::length($property["name"]));
                $typeLength = max($typeLength, Strings::length($property["queryType"]));

                $result[] = $property;
            }
        }

        foreach ($result as $index => $property) {
            $result[$index]["propName"]  = Strings::padRight("{$property["name"]};", $nameLength + 1);
            $result[$index]["propType"]  = Strings::padRight($property["queryType"], $typeLength);
            $result[$index]["constName"] = Strings::padRight($property["name"], $nameLength);
        }
        return $result;
    }



    /**
     * Returns the Field type for the Schema
     * @param FieldType $type
     * @return string
     */
    private static function getFieldType(FieldType $type): string {
        return match ($type) {
            FieldType::Boolean => "bool",
            FieldType::Number  => "int",
            FieldType::Float   => "float",
            default            => "string",
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
