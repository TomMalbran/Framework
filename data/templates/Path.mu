<?php
namespace {{codeSpace}};

use Framework\File\FilePath;

/**
 * The Paths
 */
class Path {

{{#paths}}
    /**
     * Returns the Dir for the {{title}} files
     * @param string ...$pathParts
     * @return string
     */
    public static function get{{title}}Dir(string ...$pathParts): string {
        return FilePath::getDir("{{name}}", ...$pathParts);
    }

    /**
     * Returns the Path for the {{title}} files
     * @param string ...$pathParts
     * @return string
     */
    public static function get{{title}}Path(string ...$pathParts): string {
        return FilePath::getPath("{{name}}", ...$pathParts);
    }

    /**
     * Returns the Url for the {{title}} files
     * @param string ...$pathParts
     * @return string
     */
    public static function get{{title}}Url(string ...$pathParts): string {
        return FilePath::getUrl("{{name}}", ...$pathParts);
    }
{{/paths}}
}
