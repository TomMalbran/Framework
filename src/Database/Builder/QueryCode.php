<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Builder\Builder;
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
        $contents = Builder::render("database/Query", [
            "namespace"   => $schemaModel->namespace,
            "name"        => $schemaModel->name,
            "column"      => "{$schemaModel->name}Column",
            "query"       => "{$schemaModel->name}Query",
            "status"      => "{$schemaModel->name}Status",
            "properties"  => self::getProperties($schemaModel),
            "statuses"    => self::getStatuses($schemaModel),
            "subRequests" => self::getSubRequests($schemaModel),
            "imports"     => self::getImports($schemaModel),
        ]);
        $contents = Builder::alignParams($contents);
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
            $property["queryType"] = match($property["type"]) {
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
     * Returns the Sub Request fields for the Query
     * @param SchemaModel $schemaModel
     * @return array{name:string,subQuery:string,schemaField:string,relatedField:string,tableName:string}[]
     */
    private static function getSubRequests(SchemaModel $schemaModel): array {
        $result = [];
        foreach ($schemaModel->subRequests as $subRequest) {
            $tableName = $subRequest->getDbTableName();
            $result[]  = [
                "name"         => Strings::replace($subRequest->modelName, $schemaModel->name, ""),
                "subQuery"     => "{$subRequest->modelName}Query",
                "schemaField"  => "{$schemaModel->tableName}.{$schemaModel->idDbName}",
                "relatedField" => "{$tableName}.{$schemaModel->idDbName}",
                "tableName"    => $tableName,
            ];
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

        foreach ($schemaModel->subRequests as $subRequest) {
            if ($subRequest->namespace !== $schemaModel->namespace) {
                $name = "{$subRequest->modelName}Query";
                $result["{$subRequest->namespace}\\$name"] = 1;
            }
        }

        return array_keys($result);
    }
}
