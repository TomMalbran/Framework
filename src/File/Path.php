<?php
namespace Framework\File;

use Framework\Framework;
use Framework\System\ConfigCode;
use Framework\File\File;
use Framework\File\FileType;
use Framework\Utils\Server;
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
     * Returns the path used to store the files
     * @param string ...$pathParts
     * @return string
     */
    public static function parsePath(string ...$pathParts): string {
        $result = Strings::join($pathParts, "/");
        while (Strings::contains($result, "//")) {
            $result = Strings::replace($result, "//", "/");
        }
        $result = self::removeLastSlash($result);
        return $result;
    }

    /**
     * Adds the last slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function addLastSlash(string $path): string {
        if (!Strings::endsWith($path, "/")) {
            return "$path/";
        }
        return $path;
    }

    /**
     * Adds the first slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function addFirstSlash(string $path): string {
        if (!Strings::startsWith($path, "/")) {
            return "/$path";
        }
        return $path;
    }

    /**
     * Removes the last slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function removeLastSlash(string $path): string {
        return Strings::stripEnd($path, "/");
    }

    /**
     * Removes the first slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function removeFirstSlash(string $path): string {
        return Strings::stripStart($path, "/");
    }



    /**
     * Returns the base path used to store the files
     * @param boolean $forFramework Optional.
     * @param boolean $forBackend   Optional.
     * @param boolean $forPrivate   Optional.
     * @return string
     */
    public static function getBasePath(bool $forFramework = false, bool $forBackend = false, bool $forPrivate = false): string {
        $result = Framework::getBasePath($forFramework, $forBackend);
        if ($forPrivate && !Server::isLocalHost()) {
            return dirname($result);
        }
        return $result;
    }

    /**
     * Returns the Private Path
     * @param string ...$pathParts
     * @return string
     */
    public static function forPrivate(string ...$pathParts): string {
        $basePath = self::getBasePath(forPrivate: true);
        return self::parsePath($basePath, ...$pathParts);
    }

    /**
     * Returns the FTP Path
     * @param string ...$pathParts
     * @return string
     */
    public static function forFTP(string ...$pathParts): string {
        $basePath = self::getBasePath(forPrivate: true);
        return self::parsePath($basePath, Framework::FTPDir, ...$pathParts);
    }

    /**
     * Returns the FilesPath with the given path parts
     * @param string ...$pathParts
     * @return string
     */
    public static function forFiles(string ...$pathParts): string {
        $basePath = self::getBasePath();
        return self::parsePath($basePath, Framework::FilesDir, ...$pathParts);
    }

    /**
     * Returns the Internal Files Path with the given path parts
     * @param string ...$pathParts
     * @return string
     */
    public static function forInternalFiles(string ...$pathParts): string {
        $basePath = self::getBasePath(forBackend: true);
        return self::parsePath($basePath, Framework::FilesDir, ...$pathParts);
    }

    /**
     * Returns the path used to store the files
     * @param string $pathKey
     * @param string ...$pathParts
     * @return string
     */
    public static function forPathKey(string $pathKey, string ...$pathParts): string {
        $path = self::get($pathKey);
        if (!empty($path)) {
            return self::forFiles($path, ...$pathParts);
        }
        return "";
    }



    /**
     * Returns the directory used for the internal files
     * @param string ...$pathParts
     * @return string
     */
    public static function getInternalDir(string ...$pathParts): string {
        $baseDir = Framework::getBaseDir();
        return self::parsePath($baseDir, Framework::FilesDir, ...$pathParts);
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
            return self::parsePath(Framework::FilesDir, $path, ...$pathParts);
        }
        return "";
    }



    /**
     * Returns the base url
     * @return string
     */
    public static function getBaseUrl(): string {
        return ConfigCode::getUrl("fileUrl", Framework::FilesDir);
    }

    /**
     * Returns the url for the given internal path
     * @param string ...$pathParts
     * @return string
     */
    public static function getInternalUrl(string ...$pathParts): string {
        $baseDir = Framework::getBaseDir();
        return ConfigCode::getUrl("url", $baseDir, Framework::FilesDir, ...$pathParts);
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
            return ConfigCode::getUrl("fileUrl", Framework::FilesDir, $path, ...$pathParts);
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
        $path = self::forPathKey($pathKey, ...$pathParts);
        return File::exists($path);
    }



    /**
     * Returns the path used to store the temp files
     * @param integer $credentialID
     * @param boolean $create
     * @return string
     */
    public static function getTempPath(int $credentialID, bool $create = true): string {
        $path   = self::forPathKey(Framework::TempDir, $credentialID);
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
        return ConfigCode::getUrl("fileUrl", Framework::TempDir, $credentialID, ...$pathParts);
    }



    /**
     * Ensures that all the directories are created
     * @param string $pathKey
     * @param string ...$pathParts
     * @return string
     */
    public static function ensureDir(string $pathKey, string ...$pathParts): string {
        $path        = trim(self::parsePath(...$pathParts));
        $pathParts   = Strings::split($path, "/");
        $totalParts  = count($pathParts);
        $partialPath = [];

        if (!FileType::isDir($path)) {
            $totalParts - 1;
        }
        for ($i = 0; $i < $totalParts; $i++) {
            $partialPath[] = $pathParts[$i];
            $fullPath      = self::forPathKey($pathKey, ...$partialPath);
            File::createDir($fullPath);
        }
        return self::forPathKey($pathKey, ...$pathParts);
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
            $path = self::forPathKey($pathKey);
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
