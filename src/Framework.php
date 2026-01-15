<?php
namespace Framework;

use Framework\Response;
use Framework\Auth\Auth;
use Framework\Log\ErrorLog;
use Framework\Database\Database;
use Framework\System\Router;
use Framework\System\Config;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;

use Exception;

/**
 * The Framework Service
 */
class Framework {

    private static ?Database $db       = null;
    private static ?Request  $request  = null;
    private static ?Response $response = null;



    /**
     * Executes the Framework
     * @return boolean
     */
    public static function execute(): bool {
        ErrorLog::init();

        // Parse the Request
        $request      = self::getRequest();
        $route        = $request->getString("route");
        $token        = $request->getString("token");
        $accessToken  = $request->getString("xAccessToken");
        $refreshToken = $request->getString("xRefreshToken");
        $langcode     = $request->getString("xLangcode");
        $timezone     = $request->getInt("xTimezone");

        // Remove sensitive data
        $request->remove("route");
        $request->remove("token");
        $request->remove("xAccessToken");
        $request->remove("xRefreshToken");
        $request->remove("xLangcode");
        $request->remove("xTimezone");

        // The Route is required
        if ($route === "") {
            return false;
        }

        // Validate the API
        if ($token !== "") {
            Auth::validateAPI($token);

        // Validate the Credential
        } elseif ($accessToken !== "" || $refreshToken !== "") {
            Auth::validateCredential(
                $accessToken,
                $refreshToken,
                $langcode,
                $timezone,
            );
        }

        // Perform the Request
        try {
            $response = self::request($route, $request);
            self::output($response->toArray());
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
        if ($payload !== false && $payload !== "" && JSON::isValid($payload)) {
            $data = JSON::decodeAsDictionary($payload);
        }
        return $data;
    }

    /**
     * Requests an API function
     * @param string  $route
     * @param Request $request
     * @return Response
     */
    private static function request(string $route, Request $request): Response {
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
        $response = Router::call($route, $request);

        // Add the Token and return the Response
        $response->addTokens(Auth::getAccessToken(), Auth::getRefreshToken());
        return $response;
    }

    /**
     * Outputs the given data as JSON
     * @param array<string|integer,mixed> $data
     * @return void
     */
    public static function output(array $data): void {
        http_response_code(200);
        header("Content-Type: application/json;charset=utf-8");
        print(JSON::encode($data, true));
    }



    /**
     * Returns the Framework Database
     * @return Database
     */
    public static function getDatabase(): Database {
        if (self::$db === null) {
            self::$db = new Database(
                Config::getDbHost(),
                Config::getDbDatabase(),
                Config::getDbUsername(),
                Config::getDbPassword(),
                Config::getDbCharset(),
                Config::getDbPort(),
            );
        }
        return self::$db;
    }

    /**
     * Returns the current Request
     * @return Request
     */
    public static function getRequest(): Request {
        if (self::$request === null) {
            self::$request = new Request(withRequest: true, withFiles: true);
        }
        return self::$request;
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
}
