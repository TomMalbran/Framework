<?php
namespace {{namespace}};

use Framework\File\Storage;{{#hasFields}}
use Framework\Database\Query\Query;
use Framework\Database\Query\Operator;{{/hasFields}}{{#hasReplace}}
use Framework\Database\Query\Assign;{{/hasReplace}}

/**
 * The Media Schema
 */
class MediaSchema {

    /**
     * Updates the Paths in the Database
     * @param string $oldPath
     * @param string $newPath
     * @return void
     */
    public static function updatePaths(string $oldPath, string $newPath): void {
        if ($oldPath === $newPath || $oldPath === "") {
            return;
        }

        $files = [
            [
                "old" => $oldPath,
                "new" => $newPath,
            ],
            [
                "old" => Storage::addFirstSlash($oldPath),
                "new" => Storage::addFirstSlash($newPath),
            ],
        ];

        foreach ($files as $file) {
            $old = $file["old"];
            $new = $file["new"];

            if ($newPath === "") {
                self::removePath($old);
            } else {
                self::replacePath($old, $new);
            }
        }
    }

    /**
     * Replaces the Path in the Database
     * @param string $old
     * @param string $new
     * @return void
     */
    private static function replacePath(string $old, string $new): void {
    {{#fields}}

        // Replace the File Path in {{name}}.{{fieldName}}
        {{#isSet}}
        Query::update("{{tableName}}")
            ->set("{{fieldName}}", $new)
            ->where("{{fieldName}}", Operator::Equal, $old)
            ->execute();
        {{/isSet}}
        {{#isReplace}}
        Query::update("{{tableName}}")
            ->set("{{fieldName}}", Assign::replace($old, $new))
            ->where("{{fieldName}}", Operator::Like, "\"$old\"")
            ->execute();
        {{/isReplace}}
        {{#isJSON}}
        Query::update("{{tableName}}")
            ->set("{{fieldName}}", Assign::jsonReplace($old, $new))
            ->whereExp("JSON_VALID(`{{fieldName}}`) AND JSON_SEARCH(`{{fieldName}}`, 'one', ?) IS NOT NULL", $old)
            ->execute();
        {{/isJSON}}
    {{/fields}}
    }

    /**
     * Removes the Path from the Database
     * @param string $old
     * @return void
     */
    private static function removePath(string $old): void {
    {{#fields}}

        // Remove the File Path in {{name}}.{{fieldName}}
        {{#isSet}}
        Query::update("{{tableName}}")
            ->set("{{fieldName}}", "")
            ->where("{{fieldName}}", Operator::Equal, $old)
            ->execute();
        {{/isSet}}
        {{#isReplace}}
        Query::update("{{tableName}}")
            ->set("{{fieldName}}", Assign::replace($old, ""))
            ->where("{{fieldName}}", Operator::Like, "\"$old\"")
            ->execute();
        {{/isReplace}}
        {{#isJSON}}
        Query::update("{{tableName}}")
            ->set("{{fieldName}}", Assign::jsonRemove($old))
            ->whereExp("JSON_VALID(`{{fieldName}}`) AND JSON_SEARCH(`{{fieldName}}`, 'one', ?) IS NOT NULL", $old)
            ->execute();
        {{/isJSON}}
    {{/fields}}
    }
}
