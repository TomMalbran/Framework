<?php
namespace Framework;

/**
 * The FrameWork Service
 */
class Framework {

    // The Data
    const Schema   = "schemas";
    const Path     = "paths";
    const Key      = "keys";
    const Token    = "tokens";

    // The Directories
    const DataDir  = "data";
    const NLSDir   = "nls";
    const FilesDir = "files";
    const TempDir  = "temp";
    
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
        if (File::exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        return [];
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
     * @return object
     */
    public function saveData($file, $contents) {
        $path = self::getPath(self::DataDir, "$file.json");
        file_put_contents($path, Utils::jsonEncode((array)$contents, true));
    }
}
