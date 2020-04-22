<?php
namespace Framework\File;

use Framework\Framework;
use Framework\Config\Config;
use Framework\File\File;

/**
 * The Files Paths
 */
class Path {
    
    private static $loaded   = false;
    private static $data     = [];
    private static $basePath = null;
    private static $baseDir  = null;
    
    
    /**
     * Loads the Path Data
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            self::$loaded   = true;
            self::$data     = Framework::loadData(Framework::PathData);
            self::$basePath = Framework::getFilesPath();
            self::$baseDir  = Framework::FilesDir;
        }
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
        return null;
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
            return File::getPath(self::$basePath, $path, ...$pathParts);
        }
        return "";
    }

    /**
     * Returns the path to be used in urls
     * @param string $pathKey
     * @param string ...$pathParts
     * @return string
     */
    public static function getUrl(string $pathKey, string ...$pathParts): string {
        $path = self::get($pathKey);
        if (!empty($path)) {
            return Config::getUrl(self::$baseDir, $path, ...$pathParts);
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
     * @return string
     */
    public static function getTempUrl(int $credentialID): string {
        return self::getPath(Framework::TempDir, $credentialID) . "/";
    }



    /**
     * Ensures that the Paths are created
     * @return void
     */
    public static function ensurePaths() {
        self::load();
        $paths = [];

        foreach (array_keys(self::$data["paths"]) as $pathKey) {
            $path = self::getPath($pathKey);
            if (File::createDir($path)) {
                $paths[] = $pathKey;
            }
            if ($pathKey == "source" || $pathKey == "thumbs") {
                foreach (self::$data["directories"] as $pathDir) {
                    $path = self::getPath($pathKey, $pathDir);
                    if (File::createDir($path)) {
                        $paths[] = "$pathKey/$pathDir";
                    }
                }
            }
        }

        if (!empty($paths)) {
            print("<br>Added <i>" . count($paths) . " paths</i><br>");
            print(implode($paths, ", ") . "<br>");
        } else {
            print("<br>No <i>paths</i> added<br>");
        }
    }
}
