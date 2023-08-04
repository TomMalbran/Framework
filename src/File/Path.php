<?php
namespace Framework\File;

use Framework\Framework;
use Framework\Config\Config;
use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The Files Paths
 */
class Path {

    private static bool   $loaded = false;

    /** @var array{}[] */
    private static array  $data   = [];


    /**
     * Loads the Path Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$data   = Framework::loadData(Framework::PathData);
        return true;
    }

    /**
     * Returns and loads the Paths
     * @param string $pathKey
     * @return string
     */
    private static function get(string $pathKey): string {
        self::load();
        if (!empty(self::$data["paths"][$pathKey])) {
            return self::$data["paths"][$pathKey];
        }
        return "";
    }



    /**
     * Returns the base path used to store the files
     * @return string
     */
    public static function getBasePath(): string {
        return Framework::getFilesPath();
    }

    /**
     * Returns the directory used to store the files
     * @param string $pathKey
     * @param string ...$pathParts
     * @return string
     */
    public static function getDir(string $pathKey, string ...$pathParts): string {
        $path = self::get($pathKey);
        if (!empty($path)) {
            return File::getPath(Framework::FilesDir, $path, ...$pathParts);
        }
        return "";
    }

    /**
     * Returns the path used to store the files
     * @param string $pathKey
     * @param string ...$pathParts
     * @return string
     */
    public static function getPath(string $pathKey, string ...$pathParts): string {
        $path = self::get($pathKey);
        if (!empty($path)) {
            return Framework::getFilesPath($path, ...$pathParts);
        }
        return "";
    }

    /**
     * Returns the base url
     * @return string
     */
    public static function getBaseUrl(): string {
        return Config::getFileUrl(Framework::FilesDir);
    }

    /**
     * Returns the url for the given path
     * @param string $pathKey
     * @param string ...$pathParts
     * @return string
     */
    public static function getUrl(string $pathKey, string ...$pathParts): string {
        $path = self::get($pathKey);
        if (!empty($path)) {
            return Config::getFileUrl(Framework::FilesDir, $path, ...$pathParts);
        }
        return "";
    }

    /**
     * Returns true if given file exists
     * @param string $pathKey
     * @param string ...$pathParts
     * @return boolean
     */
    public static function exists(string $pathKey, string ...$pathParts): bool {
        $path = self::getPath($pathKey, ...$pathParts);
        return File::exists($path);
    }



    /**
     * Returns the path used to store the temp files
     * @param integer $credentialID
     * @param boolean $create
     * @return string
     */
    public static function getTempPath(int $credentialID, bool $create = true): string {
        $path   = self::getPath(Framework::TempDir, $credentialID);
        $exists = File::exists($path);

        if (!$exists && $create) {
            File::createDir($path);
            return $path;
        }
        return $exists ? $path : "";
    }

    /**
     * Creates an url to the files temp directory
     * @param integer $credentialID
     * @param string  ...$pathParts
     * @return string
     */
    public static function getTempUrl(int $credentialID, string ...$pathParts): string {
        return Config::getFileUrl(Framework::TempDir, $credentialID, ...$pathParts);
    }



    /**
     * Ensures that the Paths are created
     * @return boolean
     */
    public static function ensurePaths(): bool {
        self::load();
        $paths = [];

        if (empty(self::$data["paths"])) {
            return false;
        }
        foreach (array_keys(self::$data["paths"]) as $pathKey) {
            $path = self::getPath($pathKey);
            if (File::createDir($path)) {
                $paths[] = $pathKey;
            }
        }

        if (!empty($paths)) {
            print("<br>Added <i>" . count($paths) . " paths</i><br>");
            print(Strings::join($paths, ", ") . "<br>");
        } else {
            print("<br>No <i>paths</i> added<br>");
        }
        return true;
    }
}
