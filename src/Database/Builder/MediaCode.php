<?php
namespace Framework\Database\Builder;

use Framework\Discovery\DiscoveryBuilder;
use Framework\Database\SchemaFactory;
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
        $schemaModels = SchemaFactory::buildData(false);

        $fields     = [];
        $hasReplace = false;
        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->fields as $field) {
                if ($field->isFile || $field->hasFile) {
                    $isReplace = $field->isText || $field->isLongText || $field->isJSON;
                    $fields[]  = [
                        "name"      => $schemaModel->name,
                        "query"     => Strings::lowerCaseFirst("{$schemaModel->name}Query"),
                        "tableName" => $schemaModel->tableName,
                        "fieldName" => $field->name,
                        "isReplace" => $isReplace,
                    ];
                    if ($isReplace) {
                        $hasReplace = true;
                    }
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
