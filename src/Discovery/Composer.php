<?php
namespace Framework\Discovery;

use Framework\Discovery\Type\ComposerData;
use Framework\Discovery\Type\LibraryData;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Composer
 */
class Composer {

    /**
     * Reads the Composer Data
     * @param string $basePath
     * @return ComposerData
     */
    public static function readFile(string $basePath): ComposerData {
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

        return new ComposerData(
            name:      $name,
            version:   $version,
            namespace: $namespace,
            sourceDir: $sourceDir,
            libraries: self::getLibraries($composer),
        );
    }

    /**
     * Returns the Libraries copied into the Source
     * @param array<int|string,mixed> $composer
     * @return array<string,LibraryData>
     */
    private static function getLibraries(array $composer): array {
        $extra = $composer["extra"] ?? null;
        if (!is_array($extra) || !isset($extra["libraries"]) || !is_array($extra["libraries"])) {
            return [];
        }

        $result = [];
        foreach ($extra["libraries"] as $name => $data) {
            $library = LibraryData::create($name, $data);
            if ($library !== null) {
                $result[$library->name] = $library;
            }
        }
        return $result;
    }
}
