<?php
namespace Framework\File;

use Framework\Framework;
use Framework\Data\Config;
use Framework\File\File;

/**
 * The Files Paths
 */
class Path {
    
    private static $loaded   = false;
    private static $data     = [];
    private static $basePath = null;
    private static $baseDir  = null;
    private static $url      = "";
    
    
    /**
     * Loads the Path Data
     * @return void
     */
    public static function load() {
        if (!self::$loaded) {
            self::$loaded   = true;
            self::$data     = Framework::loadData(Framework::PathData);
            self::$basePath = Framework::getPath(Framework::FilesDir);
            self::$baseDir  = Framework::FilesDir;
            self::$url      = Config::get("url");
        }
    }

    /**
     * Returns and loads the Paths
     * @param string $pathKey
     * @return string
     */
    private static function get($pathKey) {
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
    public static function getPath($pathKey, ...$pathParts) {
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
    public static function getUrl($pathKey, ...$pathParts) {
        $path = self::get($pathKey);
        if (!empty($path)) {
            return File::getPath(self::$url, self::$baseDir, $path, ...$pathParts);
        }
        return "";
    }
    


    /**
     * Returns the path used to store the temp files
     * @param integer $credentialID
     * @param boolean $create
     * @return string
     */
    public static function getTempPath($credentialID, $create = true) {
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
    public static function getTempUrl($credentialID) {
        return self::getPath(Framework::TempDir, $credentialID) . "/";
    }



    /**
     * Returns true if given file exists
     * @param string $pathKey
     * @param string ...$pathParts
     * @return boolean
     */
    public static function exists($pathKey, ...$pathParts) {
        $path = self::getPath($pathKey, ...$pathParts);
        return File::exists($path);
    }
}
