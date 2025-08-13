<?php
namespace Framework\Database\Builder;

use Framework\Discovery\Discovery;
use Framework\Database\SchemaModel;
use Framework\Builder\Builder;
use Framework\Utils\Strings;

/**
 * The Media Code
 */
class MediaCode {

    /**
     * Generates the Media code for all Schemas with File fields
     * @param SchemaModel[] $schemaModels
     * @return string
     */
    public static function getCode(array $schemaModels): string {
        $fields = [];
        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->fields as $field) {
                if ($field->isFile || $field->hasFile) {
                    $fields[] = [
                        "name"      => $schemaModel->name,
                        "query"     => Strings::lowerCaseFirst("{$schemaModel->name}Query"),
                        "tableName" => $schemaModel->tableName,
                        "fieldName" => $field->name,
                        "isReplace" => $field->isText || $field->isLongText || $field->isJSON,
                    ];
                }
            }
        }

        $contents = Builder::render("database/Media", [
            "namespace" => Discovery::getBuildNamespace(),
            "fields"    => $fields,
        ]);
        return $contents;
    }
}
