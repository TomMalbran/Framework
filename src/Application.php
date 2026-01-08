<?php
namespace Framework;

use Framework\Discovery\Package;
use Framework\File\File;
use Framework\System\Config;
use Framework\Utils\Strings;

/**
 * The Application
 */
class Application {

    /**
     * Checks if the Application is the Framework itself
     * @return boolean
     */
    public static function isFramework(): bool {
        $appPath   = self::getAppPath();
        $framePath = self::getFramePath();
        return $appPath === $framePath;
    }



    /**
     * Returns the BasePath
     * @param boolean $forFramework Optional.
     * @param boolean $forBackend   Optional.
     * @return string
     */
    public static function getBasePath(bool $forFramework = false, bool $forBackend = false): string {
        if ($forFramework) {
            return self::getFramePath();
        }
        if ($forBackend) {
            return self::getAppPath();
        }
        return self::getIndexPath();
    }

    /**
     * Returns the path to the Index
     * @param string ...$pathParts
     * @return string
     */
    public static function getIndexPath(string ...$pathParts): string {
        $path = self::getFramePath();
        if (Strings::contains($path, "vendor")) {
            $path = Strings::substringBefore($path, "/vendor");
            $path = Strings::substringBefore($path, "/", false);
        }
        return File::parsePath($path, ...$pathParts);
    }

    /**
     * Returns the path to the Framework
     * @param string ...$pathParts
     * @return string
     */
    public static function getFramePath(string ...$pathParts): string {
        $path = File::getDirectory(__FILE__, 2);
        return File::parsePath($path, ...$pathParts);
    }

    /**
     * Returns the path to the App
     * @param string ...$pathParts
     * @return string
     */
    public static function getAppPath(string ...$pathParts): string {
        return self::getIndexPath(Package::getAppBaseDir(), ...$pathParts);
    }

    /**
     * Returns the path to the Source Directory
     * @param string ...$pathParts
     * @return string
     */
    public static function getSourcePath(string ...$pathParts): string {
        return self::getAppPath(Package::getAppSourceDir(), ...$pathParts);
    }

    /**
     * Returns the path to the Strings Directory
     * @return string
     */
    public static function getStringsPath(): string {
        return self::getAppPath(Package::StringsDir);
    }

    /**
     * Returns the Namespace used in the Builder
     * @return string
     */
    public static function getBuildPath(): string {
        return self::getFramePath(Package::getAppSourceDir(), Package::SystemDir);
    }



    /**
     * Returns the url for the given internal path
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getApplUrl(string|int ...$pathParts): string {
        return Config::getUrl(Package::getAppBaseDir(), ...$pathParts);
    }

    /**
     * Returns the Environment
     * @return string
     */
    public static function getEnvironment(): string {
        $basePath = self::getBasePath(false);
        if (Strings::contains($basePath, "public_html")) {
            $environment = Strings::substringAfter($basePath, "domains/");
            $environment = Strings::substringBefore($environment, "/public_html");
            return $environment;
        }
        return "localhost";
    }
}
