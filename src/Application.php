<?php
namespace Framework;

use Framework\Discovery\Composer;
use Framework\File\File;
use Framework\System\Config;
use Framework\Utils\Strings;

/**
 * The Application
 */
class Application {

    // Composer Data
    private static bool   $loaded    = false;
    private static string $version   = "";
    private static string $namespace = "";
    private static string $baseDir   = "";
    private static string $sourceDir = "";



    /**
     * Loads the Application Composer Data
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        // Determine the Base Path
        $framePath = File::getDirectory(__FILE__, 2);
        if (Strings::contains($framePath, "vendor")) {
            $basePath = Strings::substringBefore($framePath, "/vendor");
            $baseDir  = Strings::substringAfter($basePath, "/");
        } else {
            $basePath = $framePath;
            $baseDir  = "";
        }

        // Read the Composer File
        $composer = Composer::readFile($basePath);

        // Save the Data
        self::$loaded    = true;
        self::$version   = $composer["version"];
        self::$namespace = $composer["namespace"];
        self::$baseDir   = $baseDir;
        self::$sourceDir = $composer["sourceDir"];
        return true;
    }

    /**
     * Returns the Application Version
     * @return string
     */
    public static function getVersion(): string {
        self::load();
        return self::$version;
    }

    /**
     * Returns the Application Namespace
     * @return string
     */
    public static function getNamespace(): string {
        self::load();
        return self::$namespace;
    }

    /**
     * Returns the Application Base Directory
     * @return string
     */
    public static function getBaseDir(): string {
        self::load();
        return self::$baseDir;
    }

    /**
     * Returns the Application Source Directory
     * @return string
     */
    public static function getSourceDir(): string {
        self::load();
        return self::$sourceDir;
    }



    /**
     * Returns the path to the Index
     * @param string ...$pathParts
     * @return string
     */
    public static function getIndexPath(string ...$pathParts): string {
        $path = File::getDirectory(__FILE__, 2);
        if (Strings::contains($path, "vendor")) {
            $path = Strings::substringBefore($path, "/vendor");
            $path = Strings::substringBefore($path, "/", false);
        }
        return File::parsePath($path, ...$pathParts);
    }

    /**
     * Returns the path to the Base Directory
     * @param string ...$pathParts
     * @return string
     */
    public static function getBasePath(string ...$pathParts): string {
        return self::getIndexPath(self::getBaseDir(), ...$pathParts);
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
     * Returns an Url for the given internal path
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getUrl(string|int ...$pathParts): string {
        return Config::getUrl(self::getBaseDir(), ...$pathParts);
    }

    /**
     * Returns the Environment
     * @return string
     */
    public static function getEnvironment(): string {
        $basePath = self::getIndexPath();
        if (Strings::contains($basePath, "public_html")) {
            $environment = Strings::substringAfter($basePath, "domains/");
            $environment = Strings::substringBefore($environment, "/public_html");
            return $environment;
        }
        return "localhost";
    }
}
