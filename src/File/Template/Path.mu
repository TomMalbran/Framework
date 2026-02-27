<?php
namespace {{namespace}};

use Framework\File\FilePath;

/**
 * The Paths
 */
class Path {
{{#paths}}

    /**
     * Returns the Dir for the {{title}} files
     * @param int|string ...$pathParts
     * @return string
     */
    public static function get{{title}}Dir(int|string ...$pathParts): string {
        return FilePath::getDir("{{name}}", ...$pathParts);
    }

    /**
     * Returns the Path for the {{title}} files
     * @param int|string ...$pathParts
     * @return string
     */
    public static function get{{title}}Path(int|string ...$pathParts): string {
        return FilePath::getPath("{{name}}", ...$pathParts);
    }

    /**
     * Returns the Url for the {{title}} files
     * @param int|string ...$pathParts
     * @return string
     */
    public static function get{{title}}Url(int|string ...$pathParts): string {
        return FilePath::getUrl("{{name}}", ...$pathParts);
    }
{{/paths}}
}
