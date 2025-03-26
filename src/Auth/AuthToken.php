<?php
namespace Framework\Auth;

use Framework\Auth\RefreshToken;
use Framework\System\Config;
use Framework\Utils\Dictionary;
use Framework\Utils\Server;
use Framework\Schema\CredentialRefreshTokenEntity;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * The Auth Tokens
 */
class AuthToken {

    private static bool   $loaded    = false;
    private static string $algorithm = "HS256";
    private static string $secretKey = "";
    private static int    $shortTerm = 0;
    private static int    $longTerm  = 0;

    private static ?CredentialRefreshTokenEntity $tokenData = null;



    /**
     * Loads the Auth Token Config
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        JWT::$leeway = 1000;

        self::$loaded    = true;
        self::$secretKey = Config::getAuthKey();
        self::$shortTerm = Config::getAuthHours() * 3600;
        self::$longTerm  = Config::getAuthDays() * 24 * 3600;
        return true;
    }



    /**
     * Returns the API Token for the given JWT
     * @param string $accessToken
     * @return string
     */
    public static function getAPIToken(string $accessToken): string {
        $accessData = self::getAccessData($accessToken);
        return $accessData->getString("apiToken");
    }


    /**
     * Returns true if the Refresh Token or the Access Token is valid
     * @param string $accessToken
     * @param string $refreshToken
     * @return boolean
     */
    public static function isValid(string $accessToken, string $refreshToken): bool {
        self::load();
        if ($refreshToken !== "") {
            return !self::getOne($refreshToken)->isEmpty();
        }
        return self::isValidAccessToken($accessToken);
    }

    /**
     * Returns the Credentials for the given Refresh Token or JWT
     * @param string $accessToken
     * @param string $refreshToken
     * @return array{integer,integer}
     */
    public static function getCredentials(string $accessToken, string $refreshToken): array {
        $accessData = self::getAccessData($accessToken);
        if ($accessData->hasValue("credentialID")) {
            return [ $accessData->getInt("credentialID"), $accessData->getInt("adminID") ];
        }

        $refresh = self::getOne($refreshToken);
        if ($refresh->credentialID !== 0) {
            return [ $refresh->credentialID, 0 ];
        }

        return [ 0, 0 ];
    }



    /**
     * Returns true if the given Access Token is valid
     * @param string $accessToken
     * @return boolean
     */
    public static function isValidAccessToken(string $accessToken): bool {
        $accessData = self::getAccessData($accessToken);
        return $accessData->isEmpty();
    }

    /**
     * Returns the data of the Access Token
     * @param string $accessToken
     * @return Dictionary
     */
    private static function getAccessData(string $accessToken): Dictionary {
        self::load();
        try {
            $jwt = JWT::decode($accessToken, new Key(self::$secretKey, self::$algorithm));
        } catch (Exception $e) {
            return new Dictionary();
        }
        return new Dictionary($jwt->data);
    }

    /**
     * Creates an Access Token
     * @param array<string,mixed> $data
     * @return string
     */
    public static function createAccessToken(array $data): string {
        self::load();
        $time  = time();
        $token = [
            "iat"  => $time,                     // Issued at: time when the token was generated
            "nbf"  => $time + 10,                // Not before: 10 seconds
            "exp"  => $time + self::$shortTerm,  // Expire: In x hour
            "data" => $data,
        ];
        return JWT::encode($token, self::$secretKey, self::$algorithm);
    }



    /**
     * Returns true if the given Refresh Token exists
     * @param string $refreshToken
     * @return CredentialRefreshTokenEntity
     */
    private static function getOne(string $refreshToken): CredentialRefreshTokenEntity {
        if (self::$tokenData !== null && self::$tokenData->refreshToken === $refreshToken) {
            return self::$tokenData;
        }

        self::$tokenData = RefreshToken::get($refreshToken);
        return self::$tokenData;
    }

    /**
     * Returns all the Refresh Tokens for the given Credential
     * @param integer $credentialID
     * @return array<string,string|integer>[]
     */
    public static function getAllForCredential(int $credentialID): array {
        if (empty($credentialID)) {
            return [];
        }

        $list   = RefreshToken::getAllForCredential($credentialID);
        $result = [];

        foreach ($list as $elem) {
            $result[] = [
                "refreshToken" => $elem->refreshToken,
                "platform"     => Server::getPlatform($elem->userAgent),
                "time"         => $elem->createdTime,
            ];
        }
        return $result;
    }



    /**
     * Creates a Refresh Token
     * @param integer $credentialID
     * @return string
     */
    public static function createRefreshToken(int $credentialID): string {
        self::load();
        return RefreshToken::create($credentialID, self::$longTerm);
    }

    /**
     * Updates a Refresh Token
     * @param string $accessToken
     * @param string $refreshToken
     * @return string
     */
    public static function updateRefreshToken(string $accessToken, string $refreshToken): string {
        self::load();
        if (self::isValidAccessToken($accessToken)) {
            return "";
        }

        $currentToken = self::getOne($refreshToken);
        if ($currentToken->isEmpty() || $currentToken->modifiedTime > time() - 24 * 3600) {
            return "";
        }

        return RefreshToken::recreate($refreshToken, self::$longTerm);
    }

    /**
     * Deletes a Refresh Token
     * @param string $refreshToken
     * @return boolean
     */
    public static function deleteRefreshToken(string $refreshToken): bool {
        self::load();
        if (empty($refreshToken)) {
            return false;
        }

        return RefreshToken::remove($refreshToken);
    }

    /**
     * Deletes all the Refresh Tokens for the given Credential
     * @param integer $credentialID
     * @return boolean
     */
    public static function deleteAllForCredential(int $credentialID): bool {
        self::load();
        if (empty($credentialID)) {
            return false;
        }

        return RefreshToken::removeAll($credentialID);
    }

    /**
     * Deletes the old Refresh Tokens
     * @return boolean
     */
    public static function deleteOld(): bool {
        self::load();
        return RefreshToken::removeOld(self::$longTerm);
    }
}
