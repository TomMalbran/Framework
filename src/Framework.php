<?php
namespace Framework;

use Framework\Router;
use Framework\Response;
use Framework\Auth\Auth;
use Framework\Config\Config;
use Framework\Config\Settings;
use Framework\Email\Template;
use Framework\File\File;
use Framework\File\Path;
use Framework\Log\ErrorLog;
use Framework\Schema\Factory;
use Framework\Schema\Database;
use Framework\Utils\JSON;

/**
 * The FrameWork Service
 */
class Framework {

    // The Data
    const RouteData     = "routes";
    const SchemaData    = "schemas";
    const KeyData       = "keys";
    const AccessData    = "access";
    const TokenData     = "tokens";
    const PathData      = "paths";
    const MediaData     = "media";
    const SettingsData  = "settings";
    const EmailData     = "emails";
    const LanguageData  = "languages";
    const StatusData    = "status";

    // The Directories
    const SourceDir     = "src";

    const DataDir       = "data";
    const MigrationsDir = "data/migrations";

    const PublicDir     = "public";
    const TemplatesDir  = "templates";
    const PartialsDir   = "partials";

    const FilesDir      = "files";
    const TempDir       = "temp";

    const NLSDir        = "nls";

    // Config
    const Namespace     = "App\\Controller\\";

    // Variables
    private static $framePath;
    private static $basePath;
    private static $baseDir;
    private static $db;


    /**
     * Sets the Basic data
     * @param string  $basePath
     * @param string  $baseDir
     * @param boolean $logErrors
     * @return void
     */
    public static function create(string $basePath, string $baseDir, bool $logErrors): void {
        self::$framePath = dirname(__FILE__, 2);
        self::$basePath  = $basePath;
        self::$baseDir   = $baseDir;

        if ($logErrors) {
            ErrorLog::init();
        }
    }



    /**
     * Returns the Framework Database
     * @return Database
     */
    public static function getDatabase() {
        if (empty(self::$db)) {
            $config   = Config::get("db");
            self::$db = new Database($config);
        }
        return self::$db;
    }

    /**
     * Returns the BasePath with the given dir
     * @param string  $dir          Optional.
     * @param string  $file         Optional.
     * @param boolean $forFramework Optional.
     * @return string
     */
    public static function getPath(string $dir = "", string $file = "", bool $forFramework = false): string {
        $path = "";
        if ($forFramework) {
            $path = File::getPath(self::$framePath, $dir, $file);
        } else {
            $path = File::getPath(self::$basePath, self::$baseDir, $dir, $file);
        }
        return File::removeLastSlash($path);
    }

    /**
     * Returns the FilesPath with the given file
     * @param string $file Optional.
     * @return string
     */
    public static function getFilesPath(string $file = ""): string {
        $path = File::getPath(self::$basePath, self::FilesDir, $file);
        return File::removeLastSlash($path);
    }

    /**
     * Loads a File from the App or defaults to the Framework
     * @param string $dir
     * @param string $file
     * @return string
     */
    public static function loadFile(string $dir, string $file) {
        $path   = self::getPath($dir, $file, false);
        $result = "";
        if (File::exists($path)) {
            $result = file_get_contents($path);
        }
        if (empty($result)) {
            $path   = self::getPath($dir, $file, true);
            $result = file_get_contents($path);
        }
        return $result;
    }

    /**
     * Loads a JSON File
     * @param string  $dir
     * @param string  $file
     * @param boolean $forFramework Optional.
     * @return array
     */
    public static function loadJSON(string $dir, string $file, bool $forFramework = false): array {
        $path = self::getPath($dir, "$file.json", $forFramework);
        return JSON::readFile($path, true);
    }

    /**
     * Loads a Data File
     * @param string $file
     * @return array
     */
    public static function loadData(string $file): array {
        return self::loadJSON(self::DataDir, $file);
    }

    /**
     * Saves a Data File
     * @param string $file
     * @param mixed  $contents
     * @return void
     */
    public function saveData(string $file, $contents): void {
        $path = self::getPath(self::DataDir, "$file.json");
        JSON::writeFile($path, $contents);
    }



    /**
     * Parses and returns the initial Request
     * @return object
     */
    public static function getRequest() {
        $request = $_REQUEST;
        $result  = [
            "route"    => !empty($request["route"])    ? $request["route"]         : "",
            "token"    => !empty($request["token"])    ? $request["token"]         : "",
            "jwt"      => !empty($request["jwt"])      ? $request["jwt"]           : "",
            "timezone" => !empty($request["timezone"]) ? (int)$request["timezone"] : 0,
            "params"   => [],
        ];

        if (!empty($request["params"])) {
            $result["params"] = json_decode($request["params"], true);
        } else {
            unset($request["route"]);
            unset($request["token"]);
            unset($request["jwt"]);
            unset($request["timezone"]);
            $result["params"] = $request;
        }
        return (object)$result;
    }

    /**
     * Requests an API function
     * @param string $route
     * @param array  $params Optional.
     * @return Response
     */
    public static function request(string $route, array $params = null): Response {
        // The Route doesn't exists
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
     * @param boolean  $sandbox   Optional.
     * @return void
     */
    public static function migrate(Database $db, bool $canDelete = false, bool $recreate = false, bool $sandbox = false): void {
        Factory::migrate($db, $canDelete);
        Settings::migrate($db);
        Template::migrate($db, $recreate, $sandbox);
        Path::ensurePaths();
    }
}
