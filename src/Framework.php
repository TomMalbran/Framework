<?php
namespace Framework;

use Framework\Response;
use Framework\Auth\Auth;
use Framework\Builder\Builder;
use Framework\Core\Configs;
use Framework\Core\Settings;
use Framework\Email\EmailTemplate;
use Framework\File\FilePath;
use Framework\Log\ErrorLog;
use Framework\Database\Database;
use Framework\Database\Migration;
use Framework\System\Router;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;

use Exception;

/**
 * The FrameWork Service
 */
class Framework {

    private static Database  $db;
    private static ?Response $response = null;



    /**
     * Executes the Framework
     * @return boolean
     */
    public static function execute(): bool {
        ErrorLog::init();
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
     * Executes an Internal Request
     * @return Dictionary
     */
    public static function executeInternal(): Dictionary {
        ErrorLog::init();
        Auth::validateInternal();

        $data    = new Dictionary($_REQUEST);
        $payload = file_get_contents("php://input");
        if (!empty($payload) && JSON::isValid($payload)) {
            $data = JSON::decodeAsDictionary($payload);
        }
        return $data;
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
     * Parses and returns the initial Request
     * @return object
     */
    public static function getRequest(): object {
        $request = $_REQUEST;
        $result  = [
            "route"        => !empty($request["route"])         ? $request["route"]          : "",
            "token"        => !empty($request["token"])         ? $request["token"]          : "",
            "accessToken"  => !empty($request["xAccessToken"])  ? $request["xAccessToken"]   : "",
            "refreshToken" => !empty($request["xRefreshToken"]) ? $request["xRefreshToken"]  : "",
            "langcode"     => !empty($request["xLangcode"])     ? $request["xLangcode"]      : "",
            "timezone"     => !empty($request["xTimezone"])     ? (int)$request["xTimezone"] : 0,
            "params"       => [],
        ];

        if (!empty($request["params"])) {
            $result["params"] = json_decode($request["params"], true);
        } else {
            unset($request["route"]);
            unset($request["token"]);
            unset($request["xAccessToken"]);
            unset($request["xRefreshToken"]);
            unset($request["xLangcode"]);
            unset($request["xTimezone"]);

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
