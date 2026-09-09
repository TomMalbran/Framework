<?php
namespace Framework\Database\Builder;

use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Builder\Builder;
use Framework\Utils\Strings;

/**
 * The Media Code
 * @phpstan-type MediaField array{
 *   name:      string,
 *   query:     string,
 *   tableName: string,
 *   fieldName: string,
 *   isSet:     bool,
 *   isReplace: bool,
 *   isJSON:    bool,
 * }
 */
class MediaCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $fields     = self::getFields(SchemaFactory::getData());
        $hasReplace = false;
        $hasJSON    = false;

        foreach ($fields as $field) {
            if ($field["isReplace"] || $field["isJSON"]) {
                $hasReplace = true;
            }
            if ($field["isJSON"]) {
                $hasJSON = true;
            }
        }

        // Builds the code
        return Builder::generateCode("MediaSchema", [
            "fields"     => $fields,
            "hasFields"  => count($fields) > 0,
            "hasReplace" => $hasReplace,
            "hasJSON"    => $hasJSON,
            "total"      => count($fields),
        ]);
    }

    /**
     * Returns a row for every field of the given Models that holds a file
     * @param list<SchemaModel> $schemaModels
     * @return list<MediaField>
     */
    public static function getFields(array $schemaModels): array {
        $result = [];

        foreach ($schemaModels as $schemaModel) {
            foreach ($schemaModel->fields as $field) {
                if (!$field->isFile && !$field->hasFile) {
                    continue;
                }

                // A file named inside text is replaced where it is written,
                // rather than set as the whole value of the column
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

                $result[] = [
                    "name"      => $schemaModel->name,
                    "query"     => Strings::lowerCaseFirst($schemaModel->queryClass),
                    "tableName" => $schemaModel->tableName,
                    "fieldName" => $field->name,
                    "isSet"     => !$isReplace && !$isJSON,
                    "isReplace" => $isReplace,
                    "isJSON"    => $isJSON,
                ];
            }
        }
        return $result;
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
