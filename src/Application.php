<?php
namespace Framework;

use Framework\Discovery\Composer;
use Framework\Discovery\Type\ComposerData;
use Framework\File\Storage;
use Framework\System\Config;
use Framework\Utils\Strings;

/**
 * The Application
 */
class Application {

    // The Composer Data of the App, read once and kept
    private static ?ComposerData $composer = null;

    // The directory the App sits in, which is where its composer file was found
    private static string $baseDir = "";



    /**
     * Loads the Application Composer Data
     * @return ComposerData
     */
    private static function load(): ComposerData {
        if (self::$composer !== null) {
            return self::$composer;
        }

        // Determine the Base Path
        $framePath = Storage::getDirectory(__FILE__, 2);
        if (Strings::contains($framePath, "vendor")) {
            $basePath = Strings::substringBefore($framePath, "/vendor");
            $baseDir  = Strings::substringAfter($basePath, "/");
        } else {
            $basePath = $framePath;
            $baseDir  = "";
        }

        // Read the Composer File
        self::$baseDir  = $baseDir;
        self::$composer = Composer::readFile($basePath);
        return self::$composer;
    }

    /**
     * Returns the Application Composer Data
     * @return ComposerData
     */
    public static function getComposer(): ComposerData {
        return self::load();
    }

    /**
     * Returns the Application Name
     * @return string
     */
    public static function getName(): string {
        return self::load()->name;
    }

    /**
     * Returns the Application Version
     * @return string
     */
    public static function getVersion(): string {
        return self::load()->version;
    }

    /**
     * Returns the Version of the given Library copied into the Source
     * @param string $name
     * @return string
     */
    public static function getLibraryVersion(string $name): string {
        return self::load()->getLibraryVersion($name);
    }

    /**
     * Returns the Application Namespace
     * @return string
     */
    public static function getNamespace(): string {
        return self::load()->namespace;
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
        return self::load()->sourceDir;
    }



    /**
     * Returns the path to the Index
     * @param string ...$pathParts
     * @return string
     */
    public static function getIndexPath(string ...$pathParts): string {
        $path = Storage::getDirectory(__FILE__, 2);
        if (Strings::contains($path, "vendor")) {
            $path = Strings::substringBefore($path, "/vendor");
            $path = Strings::substringBefore($path, "/", useFirst: false);
        }
        return Storage::parsePath($path, ...$pathParts);
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
     * Returns a Url for the given internal path
     * @param int|string ...$pathParts
     * @return string
     */
    public static function getUrl(int|string ...$pathParts): string {
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
