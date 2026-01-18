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
     * @param string|int ...$pathParts
     * @return string
     */
    public static function get{{title}}Dir(string|int ...$pathParts): string {
        return FilePath::getDir("{{name}}", ...$pathParts);
    }

    /**
     * Returns the Path for the {{title}} files
     * @param string|int ...$pathParts
     * @return string
     */
    public static function get{{title}}Path(string|int ...$pathParts): string {
        return FilePath::getPath("{{name}}", ...$pathParts);
    }

    /**
     * Returns the Url for the {{title}} files
     * @param string|int ...$pathParts
     * @return string
     */
    public static function get{{title}}Url(string|int ...$pathParts): string {
        return FilePath::getUrl("{{name}}", ...$pathParts);
    }
{{/paths}}
}
