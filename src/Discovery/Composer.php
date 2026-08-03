<?php
namespace Framework\Discovery;

use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Composer
 */
class Composer {

    /**
     * Reads the Composer Data
     * @param string $basePath
     * @return array{name:string,version:string,namespace:string,sourceDir:string}
     */
    public static function readFile(string $basePath): array {
        $composer  = JSON::readFile($basePath, "composer.json");
        $name      = Strings::toString($composer["name"] ?? "");
        $version   = Strings::toString($composer["version"] ?? "0.1.0");
        $namespace = "";
        $sourceDir = "";

        // The name is "vendor/package", so the vendor is used as the Project name
        $name = Strings::substringBefore($name, "/");
        $name = Strings::replace($name, [ "-", "_" ], " ");
        $name = Strings::toTitleCase($name);

        if (isset($composer["autoload"]) &&
            is_array($composer["autoload"]) &&
            isset($composer["autoload"]["psr-4"]) &&
            is_array($composer["autoload"]["psr-4"])
        ) {
            $psr       = $composer["autoload"]["psr-4"];
            $namespace = Strings::toString(key($psr));
            $sourceDir = Strings::toString($psr[$namespace] ?? "");
        }

        return [
            "name"      => $name,
            "version"   => $version,
            "namespace" => $namespace,
            "sourceDir" => $sourceDir,
        ];
    }
}
