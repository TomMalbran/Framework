<?php
namespace Framework;

use Framework\Response;
use Framework\Auth\Auth;
use Framework\Builder\Builder;
use Framework\Core\Settings;
use Framework\Email\EmailTemplate;
use Framework\File\FilePath;
use Framework\Log\ErrorLog;
use Framework\Database\Database;
use Framework\Database\Migration;
use Framework\System\Router;
use Framework\System\Config;
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

        // Parse the Request
        $request      = new Request(withRequest: true, withFiles: true);
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
            header("Content-Type:application/json;charset=utf-8");
            $response = self::request($route, $request);
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
     * Returns the Framework Database
     * @return Database
     */
    public static function getDatabase(): Database {
        if (empty(self::$db)) {
            self::$db = new Database(
                Config::getDbHost(),
                Config::getDbUsername(),
                Config::getDbPassword(),
                Config::getDbDatabase(),
                Config::getDbCharset(),
            );
        }
        return self::$db;
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
        print("\nDATABASE MIGRATIONS\n");
        Migration::migrateData($canDelete);

        print("\nSETTINGS MIGRATIONS\n");
        Settings::migrateData();

        print("\nEMAIL MIGRATIONS\n");
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
