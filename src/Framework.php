<?php
namespace Framework;

use Framework\Router;
use Framework\Response;
use Framework\Auth\Auth;
use Framework\Data\Email;
use Framework\Config\Settings;
use Framework\File\File;
use Framework\Log\ErrorLog;
use Framework\Schema\Factory;
use Framework\Schema\Database;
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
    
    // Config
    const Namespace    = "App\\Controller\\";

    // Variables
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
     * Calls an API function
     * @param string $route
     * @param array  $params Optional.
     * @return Response
     */
    public static function callRoute($route, array $params = null) {
        // The Route doesn´t exists
        if (!Router::has($route)) {
            return Response::error("GENERAL_ERROR_PATH");
        }

        // Grab the Access Level for the given Route
        $accessLevel = Router::getAccess($route);
        
        // The route requires login and the user is Logged Out
        if (Auth::requiresLogin($accessLevel)) {
            return Response::logout();
        }
        
        // The Provided Access Level is lower than the Required One
        if (!Auth::grant($accessLevel)) {
            return Response::error("GENERAL_ERROR_PATH");
        }
        
        // Perform the Request
        $response = Router::call($route, $params);
        
        // Return an Empty Response
        if (empty($response)) {
            return Response::empty();
        }

        // Add the Token and return the Response
        $token = Auth::getToken();
        $response->addToken($token);
        return $response;
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
