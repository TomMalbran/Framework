<?php
namespace Framework;

use Framework\Response;
use Framework\Auth\Auth;
use Framework\Builder\Builder;
use Framework\Core\Configs;
use Framework\Core\Settings;
use Framework\Email\EmailTemplate;
use Framework\File\File;
use Framework\File\FilePath;
use Framework\Log\ErrorLog;
use Framework\Database\Database;
use Framework\Database\Generator;
use Framework\Database\Migration;
use Framework\System\Access;
use Framework\System\Router;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

use Exception;

/**
 * The FrameWork Service
 */
class Framework {

    const Namespace      = "App\\";

    // The Data
    const RouteData      = "routes";
    const SchemaData     = "schemas";
    const MigrationsData = "migrations";
    const KeyData        = "keys";
    const StatusData     = "status";
    const AccessData     = "access";
    const FilesData      = "files";
    const SettingsData   = "settings";
    const LanguageData   = "languages";

    // The Directories
    const SourceDir      = "src";

    const DataDir        = "data";
    const TemplateDir    = "data/templates";
    const LogDir         = "data/logs";
    const MigrationsDir  = "data/migrations";

    const PublicDir      = "public";
    const TemplatesDir   = "templates";
    const PartialsDir    = "partials";

    const FilesDir       = "files";
    const FTPDir         = "public_ftp";

    const NLSDir         = "nls";
    const StringsDir     = "nls/strings";
    const EmailsDir      = "nls/emails";
    const SchemasDir     = "src/Schema";
    const RouteDir       = "src/Route";
    const SystemDir      = "src/System";

    // Variables
    private static string    $framePath;
    private static string    $basePath;
    private static string    $baseDir;
    private static Database  $db;
    private static ?Response $response = null;



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
        } elseif (!empty($request->accessToken) || !empty($request->refreshToken)) {
            Auth::validateCredential(
                $request->accessToken,
                $request->refreshToken,
                $request->langcode,
                $request->timezone,
            );
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
            $config   = Configs::getObject("db");
            self::$db = new Database($config);
        }
        return self::$db;
    }

    /**
     * Returns the Environment
     * @return string
     */
    public static function getEnvironment(): string {
        $basePath = self::getBasePath(false);
        if (Strings::contains($basePath, "public_html")) {
            $environment = Strings::substringAfter($basePath, "domains/");
            $environment = Strings::substringBefore($environment, "/public_html");
            return $environment;
        }
        return "localhost";
    }

    /**
     * Returns the BaseDir
     * @return string
     */
    public static function getBaseDir(): string {
        return self::$baseDir;
    }

    /**
     * Returns the BasePath
     * @param boolean $forFramework Optional.
     * @param boolean $forBackend   Optional.
     * @return string
     */
    public static function getBasePath(bool $forFramework = false, bool $forBackend = false): string {
        if ($forFramework) {
            return self::$framePath;
        }
        if ($forBackend) {
            return File::parsePath(self::$basePath, self::$baseDir);
        }
        return self::$basePath;
    }

    /**
     * Returns the Base Path with the given dir
     * @param string  $dir          Optional.
     * @param string  $file         Optional.
     * @param boolean $forFramework Optional.
     * @return string
     */
    public static function getPath(string $dir = "", string $file = "", bool $forFramework = false): string {
        if ($forFramework) {
            return File::parsePath(self::$framePath, $dir, $file);
        }
        return File::parsePath(self::$basePath, self::$baseDir, $dir, $file);
    }



    /**
     * Finds the Classes in the given Directory
     * @param string  $dir         Optional.
     * @param boolean $skipIgnored Optional.
     * @return array<string,string>
     */
    public static function findClasses(string $dir = "", bool $skipIgnored = false): array {
        $sourcePath = Framework::getPath(Framework::SourceDir);
        $basePath   = Framework::getPath(Framework::SourceDir, $dir);
        $files      = File::getFilesInDir($basePath, true);
        $result     = [];

        foreach ($files as $file) {
            if (!Strings::endsWith($file, ".php")) {
                continue;
            }

            // Skip some ignored directories
            if ($skipIgnored && Strings::contains($file, "/Schema/", "/System/")) {
                continue;
            }

            $className = Strings::replace($file, [ $sourcePath, ".php" ], "");
            $className = Strings::substringAfter($className, "/", true);
            $className = Strings::replace($className, "/", "\\");
            $className = "\\" . Framework::Namespace . $className;

            if (empty($dir)) {
                $result[] = $className;
            } else {
                $classKey = Strings::substringAfter($className, "\\");
                $result[$classKey] = $className;
            }
        }
        return $result;
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
            "accessToken"  => !empty($request["accessToken"])  ? $request["accessToken"]   : "",
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
            unset($request["accessToken"]);
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

        // Grab the Access Name for the given Route
        $accessName = Router::getAccessName($route);
        $accessName = Access::from($accessName);

        // The route requires login and the user is Logged Out
        if (Auth::requiresLogin($accessName)) {
            return Response::logout();
        }

        // The Provided Access Name is lower than the Required One
        if (!Auth::grant($accessName)) {
            return Response::error("GENERAL_ERROR_PATH");
        }

        // Perform the Request
        $request  = new Request($params, $_FILES);
        $response = Router::call($route, $request);

        // Add the Token and return the Response
        $response->addTokens(Auth::getAccessToken(), Auth::getRefreshToken());
        return $response;
    }

    /**
     * Stores a Response
     * @param Response|null $response Optional.
     * @return boolean
     */
    public static function setResponse(?Response $response = null): bool {
        self::$response = $response;
        return true;
    }

    /**
     * Returns the stored Response
     * @return Response|null
     */
    public static function getResponse(): ?Response {
        return self::$response;
    }



    /**
     * Generates the Codes for the Framework
     * @return boolean
     */
    public static function generateCode(): bool {
        Generator::generateCode();
        Builder::generateCode();
        return true;
    }

    /**
     * Migrates the Data for the Framework
     * @param boolean $canDelete Optional.
     * @return boolean
     */
    public static function migrateData(bool $canDelete = false): bool {
        Migration::migrateData($canDelete);
        Settings::migrateData();
        EmailTemplate::migrateData();
        return true;
    }

    /**
     * Ensures that the Paths are created for the Framework
     * @return boolean
     */
    public static function ensurePaths(): bool {
        FilePath::ensurePaths();
        return true;
    }
}
