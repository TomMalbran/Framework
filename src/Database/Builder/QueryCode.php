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
            "namespace"  => $schemaModel->namespace,
            "name"       => $schemaModel->name,
            "tableName"  => $schemaModel->tableName,
            "idDbName"   => $schemaModel->idDbName,
            "column"     => "{$schemaModel->name}Column",
            "query"      => "{$schemaModel->name}Query",
            "status"     => "{$schemaModel->name}Status",
            "properties" => $properties,
            "statuses"   => self::getStatuses($schemaModel),
            "imports"    => self::getImports($schemaModel),
            "queries"    => self::getQueries($properties),
        ]);
        return $contents;
    }

    /**
     * Returns the Field properties for the Query
     * @param SchemaModel $schemaModel
     * @return array{}
     */
    private static function getProperties(SchemaModel $schemaModel): array {
        $nameLength = 0;
        $typeLength = 0;
        $list       = [];
        $result     = [];

        foreach ($schemaModel->fields as $field) {
            if (!$field->isStatus) {
                $list[] = [
                    "type"   => $field->type,
                    "column" => $field->name,
                    "name"   => $field->name,
                    "value"  => "{$schemaModel->tableName}.{$field->dbName}",
                ];
            }
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
                if (!$field->isStatus) {
                    $list[] = [
                        "type"   => $field->type,
                        "column" => $field->name,
                        "name"   => $field->prefixName,
                        "value"  => "{$tableName}.{$field->dbName}",
                    ];
                }
            }
        }

        foreach ($list as $property) {
            $property["queryType"] = self::getQueryType($property["type"]);
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
     * Returns the Query Statuses for the Query
     * @param SchemaModel $schemaModel
     * @return array{status:string,name:string,value:string}[]
     */
    private static function getStatuses(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($field->isStatus) {
                $result[] = [
                    "status" => "{$schemaModel->name}Status",
                    "name"   => $field->name,
                    "value"  => "{$schemaModel->tableName}.{$field->dbName}",
                ];
            }
        }
        foreach ($schemaModel->relations as $relation) {
            $tableName = $relation->getDbTableName();
            foreach ($relation->fields as $field) {
                if ($field->isStatus && $relation->relationModel !== null) {
                    $result[] = [
                        "status" => "{$relation->relationModelName}Status",
                        "name"   => $field->prefixName,
                        "value"  => "{$tableName}.{$field->dbName}",
                    ];
                }
            }
        }
        return $result;
    }

    /**
     * Returns the Query Imports for the Query
     * @param SchemaModel $schemaModel
     * @return string[]
     */
    private static function getImports(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->fields as $field) {
            if ($field->isStatus) {
                $name = "{$schemaModel->name}Status";
                $result["{$schemaModel->namespace}\\$name"] = 1;
            }
        }

        foreach ($schemaModel->relations as $relation) {
            foreach ($relation->fields as $field) {
                if ($field->isStatus && $relation->relationModel !== null) {
                    $name = "{$relation->relationModelName}Status";
                    $result["{$relation->relationModel->namespace}\\$name"] = 1;
                }
            }
        }

        return array_keys($result);
    }

    /**
     * Returns the Queries used in the Query
     * @param array<string,mixed>[] $properties
     * @return string[]
     */
    private static function getQueries(array $properties): array {
        $result = [];
        foreach ($properties as $property) {
            $queryType = Strings::toString($property["queryType"] ?? "");
            if ($queryType !== "Query" && !Arrays::contains($result, $queryType)) {
                $result[] = $queryType;
            }
        }
        return $result;
    }


    /**
     * Returns the Query Type for a Field Type
     * @param FieldType $type
     * @return string
     */
    private static function getQueryType(FieldType $type): string {
        return match ($type) {
            FieldType::Boolean => "BooleanQuery",
            FieldType::Number,
            FieldType::Float   => "NumberQuery",
            FieldType::String,
            FieldType::Text,
            FieldType::LongText,
            FieldType::JSON,
            FieldType::File    => "StringQuery",
            default            => "Query",
        };
    }
}
