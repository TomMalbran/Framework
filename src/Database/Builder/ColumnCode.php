<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Builder\Builder;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Column Code
 */
class ColumnCode {

    /**
     * Returns the Column code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getCode(SchemaModel $schemaModel): string {
        $contents = Builder::render("Column", [
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
}
