<?php
namespace {{namespace}};

use Framework\Framework;{{#hasFields}}
use Framework\Database\Query\Query;
use Framework\Database\Query\Operator;{{/hasFields}}{{#hasReplace}}
use Framework\Database\Query\Assign;{{/hasReplace}}
use Framework\File\File;

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
        $db    = Framework::getDatabase();
        $files = [
            [
                "old" => $oldPath,
                "new" => $newPath,
            ],
            [
                "old" => File::addFirstSlash($oldPath),
                "new" => File::addFirstSlash($newPath),
            ],
        ];

        foreach ($files as $file) {
            $old = $file["old"];
            $new = $file["new"];
        {{#fields}}

            // Replace the File Path in {{name}}.{{fieldName}}
            {{#isReplace}}
            Query::update("{{tableName}}")
                ->set("{{fieldName}}", Assign::replace($old, $new))
                ->where("{{fieldName}}", Operator::Like, "\"$old\"")
                ->execute($db);
            {{/isReplace}}
            {{^isReplace}}
            Query::update("{{tableName}}")
                ->set("{{fieldName}}", $new)
                ->where("{{fieldName}}", Operator::Equal, $old)
                ->execute($db);
            {{/isReplace}}
        {{/fields}}
        }
    }
}
