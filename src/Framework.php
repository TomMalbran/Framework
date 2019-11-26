<?php
namespace Framework;

use Framework\File\File;
use Framework\Utils\JSON;

/**
 * The FrameWork Service
 */
class Framework {

    // The Data
    const RouteData  = "routes";
    const SchemaData = "schemas";
    const PathData   = "paths";
    const KeyData    = "keys";
    const TokenData  = "tokens";

    // The Directories
    const ServerDir  = "server";
    const DataDir    = "server/data";
    const NLSDir     = "server/nls";
    const PublicDir  = "server/public";
    const FilesDir   = "files";
    const TempDir    = "temp";
    
    private static $basePath;


    /**
     * Sets the Basic data
     * @param string $basePath
     * @return void
     */
    public static function create($basePath) {
        self::$basePath = $basePath;
    }

    /**
     * Returns the BasePath with the given dir
     * @param string $dir  Optional.
     * @param string $file Optional.
     * @return string
     */
    public static function getPath($dir = "", $file = "") {
        $path = File::getPath(self::$basePath, $dir, $file);
        return File::removeLastSlash($path);
    }

    /**
     * Loads a JSON File
     * @param string $dir
     * @param string $file
     * @return object
     */
    public static function loadFile($dir, $file) {
        $path = self::getPath($dir, "$file.json");
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
