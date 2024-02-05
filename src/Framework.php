<?php
namespace Framework;

use Framework\Response;
use Framework\Route\Router;
use Framework\Auth\Auth;
use Framework\Config\Config;
use Framework\Config\Settings;
use Framework\Email\EmailTemplate;
use Framework\File\File;
use Framework\File\Path;
use Framework\File\MediaFile;
use Framework\Log\ErrorLog;
use Framework\Schema\Factory;
use Framework\Schema\Database;
use Framework\Schema\Generator;
use Framework\Utils\JSON;
use Exception;

/**
 * The FrameWork Service
 */
class Framework {

    // The Data
    const RouteData      = "routes";
    const DispatchData   = "dispatches";
    const SchemaData     = "schemas";
    const MigrationsData = "migrations";
    const KeyData        = "keys";
    const AccessData     = "access";
    const TokenData      = "tokens";
    const PathData       = "paths";
    const MediaData      = "media";
    const SettingsData   = "settings";
    const LanguageData   = "languages";
    const StatusData     = "status";

    // The Directories
    const SourceDir      = "src";

    const DataDir        = "data";
    const LogDir         = "data/logs";
    const MigrationsDir  = "data/migrations";

    const PublicDir      = "public";
    const TemplatesDir   = "templates";
    const PartialsDir    = "partials";

    const FilesDir       = "files";
    const TempDir        = "temp";

    const NLSDir         = "nls";
    const StringsDir     = "nls/strings";
    const EmailsDir      = "nls/emails";
    const SchemasDir     = "src/Schema";

    // Variables
    private static string   $framePath;
    private static string   $basePath;
    private static string   $baseDir;
    private static Database $db;


    /**
     * Sets the Basic data
     * @param string  $basePath
     * @param string  $baseDir
     * @param boolean $logErrors
     * @return boolean
     */
    public static function create(string $basePath, string $baseDir, bool $logErrors): bool {
        self::$framePath = dirname(__FILE__, 2);
        self::$basePath  = $basePath;
        self::$baseDir   = $baseDir;

        if ($logErrors) {
            ErrorLog::init();
        }
        return true;
    }

    /**
     * Executes the Framework
     * @return boolean
     */
    public static function execute(): bool {
        $request = self::getRequest();

        // The Route is required
        if (empty($request->route)) {
            return false;
        }

        // Validate the API
        if (!empty($request->token)) {
            Auth::validateAPI($request->token);

        // Validate the Credential
        } elseif (!empty($request->jwt) || !empty($request->refreshToken)) {
            Auth::validateCredential($request->jwt, $request->refreshToken, $request->langcode, $request->timezone);
        }

        // Perform the Request
        try {
            header("Content-Type:application/json;charset=utf-8");
            $response = self::request($request->route, $request->params);
            $response->print();
            return true;
        } catch (Exception $e) {
            http_response_code(400);
            print($e->getMessage());
            return false;
        }
    }



    /**
     * Returns the Framework Database
     * @return Database
     */
    public static function getDatabase(): Database {
        if (empty(self::$db)) {
            $config   = Config::getObject("db");
            self::$db = new Database($config);
        }
        return self::$db;
    }

    /**
     * Returns the BasePath
     * @param boolean $forFramework Optional.
     * @return string
     */
    public static function getBasePath(bool $forFramework = false): string {
        if ($forFramework) {
            return self::$framePath;
        }
        return self::$basePath;
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
     * @param string ...$pathParts
     * @return string
     */
    public static function getFilesPath(string ...$pathParts): string {
        $path = File::getPath(self::$basePath, self::FilesDir, ...$pathParts);
        return File::removeLastSlash($path);
    }

    /**
     * Loads a File from the App or defaults to the Framework
     * @param string $dir
     * @param string $file
     * @return string
     */
    public static function loadFile(string $dir, string $file): string {
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
     * @param boolean $asArray      Optional.
     * @return array{}|object
     */
    public static function loadJSON(string $dir, string $file, bool $forFramework = false, bool $asArray = true): array|object {
        $path = self::getPath($dir, "$file.json", $forFramework);
        return JSON::readFile($path, $asArray);
    }

    /**
     * Loads a Data File
     * @param string  $file
     * @param boolean $asArray Optional.
     * @return array{}|object
     */
    public static function loadData(string $file, bool $asArray = true): array|object {
        return self::loadJSON(self::DataDir, $file, false, $asArray);
    }

    /**
     * Saves a Data File
     * @param string          $file
     * @param string[]|string $contents
     * @return boolean
     */
    public static function saveData(string $file, array|string $contents): bool {
        $path = self::getPath(self::DataDir, "$file.json");
        return JSON::writeFile($path, $contents);
    }

    /**
     * Logs a JSON File
     * @param string $file
     * @param mixed  $contents
     * @return boolean
     */
    public static function logFile(string $file, mixed $contents): bool {
        $path = self::getPath(self::LogDir);
        File::createDir($path);
        return File::write("$path/$file.json", JSON::encode($contents, true));
    }



    /**
     * Parses and returns the initial Request
     * @return object
     */
    public static function getRequest(): object {
        $request = $_REQUEST;
        $result  = [
            "route"        => !empty($request["route"])        ? $request["route"]         : "",
            "token"        => !empty($request["token"])        ? $request["token"]         : "",
            "jwt"          => !empty($request["jwt"])          ? $request["jwt"]           : "",
            "refreshToken" => !empty($request["refreshToken"]) ? $request["refreshToken"]  : "",
            "langcode"     => !empty($request["langcode"])     ? $request["langcode"]      : "",
            "timezone"     => !empty($request["timezone"])     ? (int)$request["timezone"] : 0,
            "params"       => [],
        ];

        if (!empty($request["params"])) {
            $result["params"] = json_decode($request["params"], true);
        } else {
            unset($request["route"]);
            unset($request["token"]);
            unset($request["jwt"]);
            unset($request["refreshToken"]);
            unset($request["langcode"]);
            unset($request["timezone"]);
            $result["params"] = $request;
        }
        return (object)$result;
    }

    /**
     * Requests an API function
     * @param string  $route
     * @param array{} $params Optional.
     * @return Response
     */
    public static function request(string $route, array $params = []): Response {
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
        $request  = new Request($params, $_FILES);
        $response = Router::call($route, $request);

        // Return an Empty Response
        if (empty($response) || !($response instanceof Response)) {
            return Response::empty();
        }

        // Add the Token and return the Response
        $response->addTokens(Auth::getToken(), Auth::getRefreshToken());
        return $response;
    }

    /**
     * Runs the Migrations for all the Framework
     * @param boolean $canDelete Optional.
     * @param boolean $withPaths Optional.
     * @return boolean
     */
    public static function migrate(bool $canDelete = false, bool $withPaths = true): bool {
        $factMigrated = Factory::migrate($canDelete);
        $settMigrated = Settings::migrate();
        $tempMigrated = EmailTemplate::migrate();
        $genMigrated  = Generator::migrate();

        if ($withPaths) {
            $pathMigrated  = Path::ensurePaths();
            $mediaMigrated = MediaFile::ensurePaths();
        } else {
            $pathMigrated  = true;
            $mediaMigrated = true;
        }
        return $factMigrated || $settMigrated || $tempMigrated || $pathMigrated || $mediaMigrated || $genMigrated;
    }
}
