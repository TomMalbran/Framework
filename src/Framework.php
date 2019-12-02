<?php
namespace Framework;

use Framework\Data\Email;
use Framework\Config\Settings;
use Framework\Log\ErrorLog;
use Framework\Schema\Factory;
use Framework\Schema\Database;
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
    const AccessData   = "access";
    const TokenData    = "tokens";
    const PathData     = "paths";
    const SettingsData = "settings";
    const EmailData    = "emails";

    // The Directories
    const ServerDir    = "server";
    const SourceDir    = "server/src";
    const DataDir      = "server/data";
    const NLSDir       = "server/nls";
    const PublicDir    = "server/public";
    const FilesDir     = "files";
    const TempDir      = "temp";
    
    private static $framePath;
    private static $basePath;


    /**
     * Sets the Basic data
     * @param string  $basePath
     * @param boolean $logErrors
     * @return void
     */
    public static function create($basePath, $logErrors) {
        self::$framePath = dirname(__FILE__, 2);
        self::$basePath  = $basePath;

        if ($logErrors) {
            ErrorLog::init();
        }
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



    /**
     * Runs the Migrations for all the Framework
     * @param Database $db
     * @param boolean  $canDelete Optional.
     * @param boolean  $recreate  Optional.
     * @return void
     */
    public static function migrate(Database $db, $canDelete = false, $recreate = false) {
        Factory::migrate($db, $canDelete);
        Settings::migrate($db);
        Email::migrate($db, $recreate);
    }
}
