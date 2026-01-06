<?php
namespace Framework\Database\Builder;

use Framework\Discovery\DiscoveryCode;
use Framework\Database\SchemaFactory;
use Framework\Utils\Strings;

/**
 * The Media Code
 */
class MediaCode implements DiscoveryCode {

    /**
     * Returns the File Name to Generate
     * @return string
     */
    public static function getFileName(): string {
        return "MediaSchema";
    }

    /**
     * Returns the File Code to Generate
     * @return array<string,mixed>
     */
    public static function getFileCode(): array {
        $schemaModels = SchemaFactory::buildData(false);

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

        return [
            "fields" => $fields,
            "total"  => count($fields),
        ];
    }
}
