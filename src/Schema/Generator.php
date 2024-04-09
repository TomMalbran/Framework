<?php
namespace Framework\Schema;

use Framework\Framework;
use Framework\File\File;
use Framework\Schema\Structure;
use Framework\Schema\Field;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Graph Generator
 */
class Generator {

    private static string $writePath;
    private static string $schemaText;
    private static string $entityText;


    /**
     * Generates the Classes
     * @return boolean
     */
    public static function migrate(): bool {
        self::$writePath  = Framework::getPath(Framework::SchemasDir);
        self::$schemaText = Framework::loadFile(Framework::TemplateDir, "Schema.mu");
        self::$entityText = Framework::loadFile(Framework::TemplateDir, "Entity.mu");

        $schemas = Factory::getData();
        $created = 0;

        File::createDir(self::$writePath);
        File::emptyDir(self::$writePath);

        foreach ($schemas as $schemaKey => $schemaData) {
            if (empty($schemaData["name"])) {
                continue;
            }

            $structure = new Structure($schemaKey, $schemaData);
            if (!self::createSchema($structure)) {
                continue;
            }
            if (!self::createEntity($structure)) {
                continue;
            }
            $created += 1;
        }

        if ($created > 0) {
            print("<br>Generated <i>$created schemas</i><br>");
        }
        return $created > 0;
    }



    /**
     * Creates the Schema file
     * @param Structure $structure
     * @return boolean
     */
    private static function createSchema(Structure $structure): bool {
        $fileName    = "{$structure->name}Schema.php";
        $idType      = self::getFieldType($structure->idType);
        $uniques     = self::getFieldList($structure, "isUnique");
        $parents     = self::getFieldList($structure, "isParent");
        $subTypes    = self::getSubTypes($structure->subRequests);
        $editParents = $structure->hasPositions ? $parents : [];

        $contents    = Mustache::render(self::$schemaText, [
            "namespace"       => Framework::Namespace,
            "name"            => $structure->name,
            "schemaName"      => $structure->key,
            "hasID"           => $structure->hasID,
            "idKey"           => $structure->idKey,
            "idName"          => $structure->idName,
            "idType"          => $idType,
            "idDocType"       => self::getDocType($idType),
            "idText"          => Strings::upperCaseFirst($structure->idName),
            "editType"        => $structure->hasID ? "Query|$idType" : "Query",
            "editDocType"     => $structure->hasID ? "Query|" . self::getDocType($idType) : "Query",
            "hasName"         => !empty($structure->nameKey) && !Arrays::contains($uniques, $structure->nameKey, "fieldName"),
            "nameKey"         => $structure->nameKey,
            "hasSelect"       => !empty($structure->nameKey),
            "hasPositions"    => $structure->hasPositions,
            "hasUsers"        => $structure->hasUsers,
            "hasFilters"      => $structure->hasFilters,
            "hasEncrypt"      => $structure->hasEncrypt,
            "canCreate"       => $structure->canCreate,
            "canEdit"         => $structure->canEdit,
            "canReplace"      => $structure->canEdit && !$structure->hasTimestamps,
            "canBatch"        => $structure->canEdit && !$structure->hasTimestamps,
            "canDelete"       => $structure->canDelete,
            "canRemove"       => $structure->canRemove,
            "processEntity"   => !empty($subTypes) || !empty($structure->processed),
            "subTypes"        => $subTypes,
            "hasProcessed"    => !empty($structure->processed),
            "uniques"         => $uniques,
            "parents"         => $parents,
            "editParents"     => $editParents,
            "parentsList"     => self::joinFields($parents, "fieldParam"),
            "parentsArgList"  => self::joinFields($parents, "fieldArg"),
            "parentsDefList"  => self::joinFields($parents, "fieldDefault", ", "),
            "parentsEditList" => self::joinFields($editParents, "fieldArg", ", "),
            "hasParents"      => !empty($parents),
            "hasEditParents"  => $structure->hasPositions && !empty($parents),
            "hasMainQuery"    => $structure->hasFilters || !empty($parents),
        ]);

        $contents = self::alignParams($contents);
        $contents = Strings::replace($contents, "(, ", "(");
        return File::create(self::$writePath, $fileName, $contents);
    }

    /**
     * Returns the Sub Types from the Sub Requests
     * @param array{} $subRequests
     * @return array{}[]
     */
    private static function getSubTypes(array $subRequests): array {
        $result = [];
        foreach ($subRequests as $name => $type) {
            if (!empty($type) && !Strings::contains($type, "<", "[")) {
                $result[] = [
                    "name" => $name,
                    "type" => $type,
                ];
            }
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
        $param   = "\${$field->name}";
        $type    = self::getFieldType($field->type);
        $default = self::getDefault($type);

        return [
            "fieldKey"     => $field->key,
            "fieldName"    => $field->name,
            "fieldText"    => Strings::upperCaseFirst($field->name),
            "fieldDoc"     => self::getDocType($type) . " $param",
            "fieldParam"   => $param,
            "fieldArg"     => "$type $param",
            "fieldDefault" => "$type $param = $default",
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
        $fileName   = "{$structure->name}Entity.php";
        $attributes = self::getAttributes($structure);
        $contents   = Mustache::render(self::$entityText, [
            "namespace"  => Framework::Namespace,
            "name"       => $structure->name,
            "attributes" => self::parseAttributes($attributes),
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
        foreach ($structure->processed as $field) {
            self::addAttribute($result, $field);
        }
        foreach ($structure->subRequests as $key => $type) {
            if (!empty($type) && !Strings::contains($type, "<", "[")) {
                $type .= "Entity[]";
            }
            $result[] = self::getTypeData($key, "array", $type);
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
        case Field::Binary:
        case Field::Number:
            $result[] = self::getTypeData($key, "int");
            break;
        case Field::Float:
            $result[] = self::getTypeData($key, "float");
            $result[] = self::getTypeData("{$key}Format", "string");
            $result[] = self::getTypeData("{$key}Int", "int");
            break;
        case Field::Price:
            $result[] = self::getTypeData($key, "float");
            $result[] = self::getTypeData("{$key}Format", "string");
            $result[] = self::getTypeData("{$key}Cents", "int");
            break;
        case Field::Date:
        case Field::Hour:
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
     * Returns the Field type for the Schema
     * @param string $type
     * @return string
     */
    private static function getFieldType(string $type): string {
        return match ($type) {
            Field::Boolean => "bool",
            Field::ID, Field::Binary, Field::Number, Field::Date, Field::Hour => "int",
            Field::Float, Field::Price => "float",
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
