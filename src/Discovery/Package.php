<?php
namespace Framework\Discovery;

use Framework\Application;
use Framework\File\File;

/**
 * The Framework Package
 */
class Package {

    // Framework Constants
    public const Namespace   = "Framework\\";
    public const ConfigDir   = "config";
    public const TemplateDir = "data/templates";

    // Source Directories
    public const SystemDir   = "System";
    public const SchemaDir   = "Schema";
    public const ModelDir    = "Model";


    // Composer Data
    private static bool   $loaded    = false;
    private static string $version   = "";
    private static string $sourceDir = "";



    /**
     * Loads the Framework Composer Data
     * @return bool
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        // Read the Composer File
        $basePath = self::getBasePath();
        $composer = Composer::readFile($basePath);

        // Save the Data
        self::$loaded    = true;
        self::$version   = $composer["version"];
        self::$sourceDir = $composer["sourceDir"];
        return true;
    }

    /**
     * Returns the Framework Version
     * @return string
     */
    public static function getVersion(): string {
        self::load();
        return self::$version;
    }

    /**
     * Returns the Framework Source Directory
     * @return string
     */
    public static function getSourceDir(): string {
        self::load();
        return self::$sourceDir;
    }



    /**
     * Checks if the Application is the Framework itself
     * @return bool
     */
    public static function isFramework(): bool {
        $appPath   = Application::getBasePath();
        $framePath = self::getBasePath();
        return $appPath === $framePath;
    }

    /**
     * Returns the base path to the Framework
     * @param string ...$pathParts
     * @return string
     */
    public static function getBasePath(string ...$pathParts): string {
        $path = File::getDirectory(__FILE__, 3);
        return File::parsePath($path, ...$pathParts);
    }

    /**
     * Returns the path to the Source Directory
     * @param string ...$pathParts
     * @return string
     */
    public static function getSourcePath(string ...$pathParts): string {
        return self::getBasePath(self::getSourceDir(), ...$pathParts);
    }

    /**
     * Returns the Path used to store the Built files
     * @return string
     */
    public static function getBuildPath(): string {
        return self::getSourcePath(self::SystemDir);
    }
}
