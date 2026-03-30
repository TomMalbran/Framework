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
            "namespace"   => $schemaModel->namespace,
            "name"        => $schemaModel->name,
            "statusClass" => $schemaModel->statusClass,
            "statuses"    => self::getList($schemaModel),
            "values"      => self::getValues($schemaModel),
        ]);
        return $contents;
    }

    /**
     * Returns the Status Where code
     * @param SchemaModel $schemaModel
     * @return string
     */
    public static function getWhereCode(SchemaModel $schemaModel): string {
        $contents = Builder::render("StatusWhere", [
            "namespace"        => $schemaModel->namespace,
            "name"             => $schemaModel->name,
            "statusClass"      => $schemaModel->statusClass,
            "statusWhereClass" => "{$schemaModel->statusClass}Where",
        ]);
        return $contents;
    }



    /**
     * Generates the Status list
     * @param SchemaModel $schemaModel
     * @return list<array{name:string,color:string}>
     */
    private static function getList(SchemaModel $schemaModel): array {
        $result = [];
        if (count($schemaModel->states) > 0) {
            foreach ($schemaModel->states as $state) {
                $result[] = [
                    "name"  => $state->name,
                    "color" => $state->color->getColor(),
                ];
            }
        } else {
            foreach (Status::getValues() as $statusName => $statusColor) {
                $result[] = [
                    "name"  => $statusName,
                    "color" => $statusColor,
                ];
            }
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
