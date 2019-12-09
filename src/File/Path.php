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
            self::$basePath = Framework::getPath(Framework::FilesDir);
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
        if (!empty(self::$data[$pathKey])) {
            return self::$data[$pathKey];
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
     * Returns true if given file exists
     * @param string $pathKey
     * @param string ...$pathParts
     * @return boolean
     */
    public static function exists(string $pathKey, string ...$pathParts): bool {
        $path = self::getPath($pathKey, ...$pathParts);
        return File::exists($path);
    }
}
