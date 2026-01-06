<?php
namespace Framework\Discovery;

use Framework\File\File;
use Framework\File\FileType;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Package
 */
class Package {

    // Frame Constants
    public const FrameNamespace   = "Framework\\";
    public const FrameSourceDir   = "src";
    public const FrameConfigDir   = "config";

    // Source Directories
    public const SystemDir        = "System";
    public const SchemaDir        = "Schema";
    public const ModelDir         = "Model";

    // Data Directories
    public const DataDir          = "data";
    public const DataFilesDir     = "data/files";
    public const TemplateDir      = "data/templates";
    public const MigrationsDir    = "data/migrations";
    public const LogDir           = "data/logs";
    public const EmailFile        = "email.html";

    // NLS Directories
    public const StringsDir       = "nls/strings";
    public const EmailsDir        = "nls/emails";
    public const NotificationsDir = "nls/notifications";

    // Other Directories
    public const FilesDir         = "files";
    public const FTPDir           = "public_ftp";


    // Composer Data
    private static bool   $loaded       = false;
    private static string $version      = "";
    private static string $appNamespace = "";
    private static string $appBaseDir   = "";
    private static string $appSourceDir = "";



    /**
     * Loads the Package Data
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        // Determine the Base Path
        $framePath = Discovery::getFramePath();
        if (Strings::contains($framePath, "vendor")) {
            $basePath   = Strings::substringBefore($framePath, "/vendor");
            $appBaseDir = Strings::substringAfter($basePath, "/");
        } else {
            $basePath   = $framePath;
            $appBaseDir = "";
        }

        // Read the Composer File
        $composer     = JSON::readFile($basePath, "composer.json");
        $version      = Strings::toString($composer["version"] ?? "0.1.0");
        $appNamespace = "";
        $appSourceDir = "";

        if (isset($composer["autoload"]) && is_array($composer["autoload"]) && is_array($composer["autoload"]["psr-4"])) {
            $psr          = $composer["autoload"]["psr-4"];
            $appNamespace = Strings::toString(key($psr));
            $appSourceDir = Strings::toString($psr[$appNamespace]);
        }


        // Save the Data
        self::$loaded       = true;
        self::$version      = $version;
        self::$appNamespace = $appNamespace;
        self::$appBaseDir   = $appBaseDir;
        self::$appSourceDir = $appSourceDir;
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
    public static function getAppNamespace(): string {
        self::load();
        return self::$appNamespace;
    }

    /**
     * Returns the Application Base Directory
     * @return string
     */
    public static function getAppBaseDir(): string {
        self::load();
        return self::$appBaseDir;
    }

    /**
     * Returns the Application Source Directory
     * @return string
     */
    public static function getAppSourceDir(): string {
        self::load();
        return self::$appSourceDir;
    }
}
