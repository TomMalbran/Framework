<?php
namespace {{namespace}};

use Framework\Framework;
use Framework\Database\Assign;
use Framework\Database\Query;
use Framework\File\File;

/**
 * The Media Schema
 */
class MediaSchema {

    /**
     * Updates the Paths in the Database
     * @param string $oldPath
     * @param string $newPath
     * @return boolean
     */
    public static function updatePaths(string $oldPath, string $newPath): bool {
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
            ${{query}} = Query::create("{{fieldName}}", "LIKE", "\"$old\"");
            $db->update("{{tableName}}", [
                "{{fieldName}}" => Assign::replace($old, $new),
            ], ${{query}});
            {{/isReplace}}
            {{^isReplace}}
            ${{query}} = Query::create("{{fieldName}}", "=", $old);
            $db->update("{{tableName}}", [
                "{{fieldName}}" => $new,
            ], ${{query}});
            {{/isReplace}}
        {{/fields}}
        }

        return true;
    }
}
