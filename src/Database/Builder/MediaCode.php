<?php
namespace Framework\Database\Builder;

use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Database\SchemaFactory;
use Framework\Database\Model\FieldType;
use Framework\Builder\Builder;
use Framework\Utils\Strings;

/**
 * The Media Code
 */
class MediaCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $schemaModels = SchemaFactory::getData();

        $fields     = [];
        $hasReplace = false;
        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->fields as $field) {
                if (!$field->isFile && !$field->hasFile) {
                    continue;
                }

                $isJSON    = false;
                $isReplace = (
                    $field->type === FieldType::Text ||
                    $field->type === FieldType::LongText ||
                    $field->type === FieldType::JSON
                );
                if ($field->jsonFiles) {
                    $isJSON    = true;
                    $isReplace = false;
                }

                $fields[]  = [
                    "name"      => $schemaModel->name,
                    "query"     => Strings::lowerCaseFirst($schemaModel->queryClass),
                    "tableName" => $schemaModel->tableName,
                    "fieldName" => $field->name,
                    "isSet"     => !$isReplace && !$isJSON,
                    "isReplace" => $isReplace,
                    "isJSON"    => $isJSON,
                ];
                if ($isReplace || $isJSON) {
                    $hasReplace = true;
                }
            }
        }

        // Builds the code
        return Builder::generateCode("MediaSchema", [
            "fields"     => $fields,
            "hasFields"  => count($fields) > 0,
            "hasReplace" => $hasReplace,
            "total"      => count($fields),
        ]);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }
}
