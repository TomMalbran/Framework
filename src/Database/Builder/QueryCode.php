<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Builder\Builder;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Query Code
 */
class QueryCode {

    /**
     * Returns the Query code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getCode(SchemaModel $schemaModel): string {
        $properties = self::getProperties($schemaModel);
        $contents   = Builder::render("Query", [
            "namespace"   => $schemaModel->namespace,
            "name"        => $schemaModel->name,
            "tableName"   => $schemaModel->tableName,
            "idDbName"    => $schemaModel->idDbName,
            "columnClass" => $schemaModel->columnClass,
            "queryClass"  => $schemaModel->queryClass,
            "properties"  => $properties,
            "imports"     => self::getImports($schemaModel),
            "queries"     => self::getQueries($properties),
        ]);
        return $contents;
    }

    /**
     * Returns the Field properties for the Query
     * @param SchemaModel $schemaModel
     * @return list<array{fieldType:FieldType,status:string,column:string,name:string,value:string}>
     */
    private static function getProperties(SchemaModel $schemaModel): array {
        $nameLength = 0;
        $list       = [];
        $result     = [];

        foreach ($schemaModel->fields as $field) {
            $list[] = [
                "fieldType" => $field->type,
                "status"    => $field->isStatus ? $schemaModel->statusClass : "",
                "column"    => $field->name,
                "name"      => $field->name,
                "value"     => "{$schemaModel->tableName}.{$field->dbName}",
            ];
        }

        foreach ($schemaModel->expressions as $expression) {
            $list[] = [
                "fieldType" => $expression->type,
                "status"    => "",
                "column"    => $expression->name,
                "name"      => $expression->name,
                "value"     => $expression->name,
            ];
        }

        foreach ($schemaModel->relations as $relation) {
            $tableName = $relation->getDbTableName();
            foreach ($relation->fields as $field) {
                $list[] = [
                    "fieldType" => $field->type,
                    "status"    => $field->isStatus ? "{$relation->relationModelName}Status" : "",
                    "column"    => $field->name,
                    "name"      => $field->prefixName,
                    "value"     => "{$tableName}.{$field->dbName}",
                ];
            }
        }

        foreach ($list as $property) {
            $property["type"] = self::getWhereType($property["fieldType"], $property["status"]);
            if ($property["type"] !== "") {
                $nameLength = max($nameLength, Strings::length($property["name"]));
                $result[]   = $property;
            }
        }

        foreach ($result as $index => $property) {
            $result[$index]["propName"] = Strings::padRight($property["name"], $nameLength);
        }
        return $result;
    }

    /**
     * Returns the Query Imports for the Query
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
     * Returns the Queries used in the Query
     * @param list<array<string,mixed>> $properties
     * @return list<string>
     */
    private static function getQueries(array $properties): array {
        $result = [];
        foreach ($properties as $property) {
            $type = Strings::toString($property["type"] ?? "");
            if ($type !== "Query" && !Strings::endsWith($type, "StatusWhere") &&
                !Arrays::contains($result, $type)
            ) {
                $result[] = $type;
            }
        }
        return $result;
    }


    /**
     * Returns the Where Type for a Field Type
     * @param FieldType $type
     * @param string    $status
     * @return string
     */
    private static function getWhereType(FieldType $type, string $status): string {
        if ($status !== "") {
            return "{$status}Where";
        }
        return match ($type) {
            FieldType::Date    => "DateWhere",
            FieldType::Enum    => "EnumWhere",
            FieldType::JSON,
            FieldType::Array   => "StringWhere",

            FieldType::Boolean => "BooleanWhere",

            FieldType::Number,
            FieldType::Float   => "NumberWhere",

            FieldType::String,
            FieldType::Text,
            FieldType::LongText,
            FieldType::File,
            FieldType::Encrypt => "StringWhere",
        };
    }
}
