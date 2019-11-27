<?php
namespace Framework;

use Framework\File\File;
use Framework\Utils\JSON;

/**
 * The FrameWork Service
 */
class Framework {

    // The Data
    const RouteData    = "routes";
    const SchemaData   = "schemas";
    const KeyData      = "keys";
    const PathData     = "paths";
    const TokenData    = "tokens";
    const SettingsData = "settings";
    const EmailData    = "emails";

    // The Directories
    const ServerDir    = "server";
    const DataDir      = "server/data";
    const NLSDir       = "server/nls";
    const PublicDir    = "server/public";
    const FilesDir     = "files";
    const TempDir      = "temp";
    
    private static $framePath;
    private static $basePath;


    /**
     * Sets the Basic data
     * @param string $basePath
     * @return void
     */
    public static function create($basePath) {
        self::$framePath = dirname(__FILE__, 2);
        self::$basePath  = $basePath;
    }



    /**
     * Returns the BasePath with the given dir
     * @param string  $dir      Optional.
     * @param string  $file     Optional.
     * @param boolean $forFrame Optional.
     * @return string
     */
    public static function getPath($dir = "", $file = "", $forFrame = false) {
        $base = $forFrame ? self::$framePath : self::$basePath;
        $path = File::getPath($base, $dir, $file);
        return File::removeLastSlash($path);
    }

    /**
     * Loads a JSON File
     * @param string  $dir
     * @param string  $file
     * @param boolean $forFrame Optional.
     * @return object
     */
    public static function loadFile($dir, $file, $forFrame = false) {
        $path = self::getPath($dir, "$file.json", $forFrame);
        return JSON::read($path);
    }

    /**
     * Loads a Data File
     * @param string $file
     * @return object
     */
    public static function loadData($file) {
        return self::loadFile(self::DataDir, $file);
    }

    /**
     * Saves a Data File
     * @param string $file
     * @param mixed  $contents
     * @return void
     */
    public function saveData($file, $contents) {
        $path = self::getPath(self::DataDir, "$file.json");
        JSON::write($path, $contents);
    }
}
