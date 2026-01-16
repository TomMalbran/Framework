<?php
namespace Framework\Database\Builder;

use Framework\Database\SchemaModel;
use Framework\Database\Status\Status;
use Framework\Builder\Builder;
use Framework\Utils\Strings;

/**
 * The Status Code
 */
class StatusCode {

    /**
     * Returns the Status code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getCode(SchemaModel $schemaModel): string {
        $contents = Builder::render("Status", [
            "namespace" => $schemaModel->namespace,
            "name"      => $schemaModel->name,
            "status"    => "{$schemaModel->name}Status",
            "statuses"  => self::getList($schemaModel),
            "values"    => self::getValues($schemaModel),
        ]);
        return $contents;
    }

    /**
     * Generates the Status list
     * @param SchemaModel $schemaModel
     * @return array{name:string,color:string,constant:string}[]
     */
    private static function getList(SchemaModel $schemaModel): array {
        $result    = [];
        $maxLength = 0;

        if (count($schemaModel->states) > 0) {
            foreach ($schemaModel->states as $state) {
                $result[] = [
                    "name"     => $state->name,
                    "color"    => $state->color->getColor(),
                    "constant" => "",
                ];
                $maxLength = max($maxLength, Strings::length($state->name));
            }
        } else {
            foreach (Status::getValues() as $statusName => $statusColor) {
                $result[] = [
                    "name"     => $statusName,
                    "color"    => $statusColor,
                    "constant" => "",
                ];
                $maxLength = max($maxLength, Strings::length($statusName));
            }
        }

        foreach ($result as $index => $elem) {
            $result[$index]["constant"] = Strings::padRight($elem["name"], $maxLength);
        }
        return $result;
    }

    /**
     * Generates the Status values
     * @param SchemaModel $schemaModel
     * @return string
     */
    private static function getValues(SchemaModel $schemaModel): string {
        $result = [];
        if (count($schemaModel->states) > 0) {
            foreach ($schemaModel->states as $state) {
                if (!$state->isHidden) {
                    $result[] = $state->name;
                }
            }
        } else {
            foreach (Status::getValues() as $statusName => $statusColor) {
                $result[] = $statusName;
            }
        }
        return "self::" . Strings::join($result, ", self::");
    }
}
