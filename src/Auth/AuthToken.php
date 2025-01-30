<?php
namespace Framework\Auth;

use Framework\Auth\RefreshToken;
use Framework\System\ConfigCode;
use Framework\Utils\Server;
use Framework\Schema\CredentialRefreshTokenEntity;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * The Auth Tokens
 */
class AuthToken {

    private static bool   $loaded     = false;
    private static bool   $useRefresh = false;
    private static string $algorithm  = "HS256";
    private static string $secretKey  = "";
    private static int    $shortTerm  = 0;
    private static int    $longTerm   = 0;

    private static CredentialRefreshTokenEntity $tokenData;



    /**
     * Loads the Auth Token Config
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        JWT::$leeway = 1000;

        self::$loaded     = true;
        self::$useRefresh = ConfigCode::getBoolean("authUseRefresh");
        self::$secretKey  = ConfigCode::getString("authKey");
        self::$shortTerm  = ConfigCode::getFloat("authHours") * 3600;
        self::$longTerm   = ConfigCode::getFloat("authDays") * 24 * 3600;
        return true;
    }



    /**
     * Returns true if the Refresh Token or the JWT Token is valid
     * @param string $jwtToken
     * @param string $refreshToken
     * @return boolean
     */
    public static function isValid(string $jwtToken, string $refreshToken): bool {
        self::load();
        $jwtData = self::getJWT($jwtToken);
        if (empty($jwtData)) {
            return false;
        }

        if (self::$useRefresh && !empty($refreshToken)) {
            return !self::getOne($refreshToken)->isEmpty();
        }
        return false;
    }

    /**
     * Returns the API Token for the given JWT
     * @param string $jwtToken
     * @return string
     */
    public static function getAPIToken(string $jwtToken): string {
        self::load();
        $jwtData = self::getJWT($jwtToken);
        return !empty($jwtData->apiToken) ? $jwtData->apiToken : "";
    }

    /**
     * Returns the Credentials for the given Refresh Token or JWT
     * @param string $jwtToken
     * @param string $refreshToken
     * @return integer[]
     */
    public static function getCredentials(string $jwtToken, string $refreshToken): array {
        self::load();
        $jwtData = self::getJWT($jwtToken);
        if (!empty($jwtData->credentialID)) {
            return [ $jwtData->credentialID, $jwtData->adminID ];
        }

        if (self::$useRefresh) {
            $credentialID = self::getOne($refreshToken)->credentialID;
            if (!empty($credentialID)) {
                return [ $credentialID, 0 ];
            }
        }
        return [ 0, 0 ];
    }



    /**
     * Returns the JWT Token Data
     * @param string $token
     * @return object
     */
    public static function getJWT(string $token): object {
        self::load();
        try {
            $decode = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
        } catch (Exception $e) {
            return (object)[];
        }
        return (object)$decode->data;
    }

    /**
     * Creates a JWT Token
     * @param array{} $data
     * @return string
     */
    public static function createJWT(array $data): string {
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
        if (!empty(self::$tokenData) && self::$tokenData->refreshToken === $refreshToken) {
            return self::$tokenData;
        }

        self::$tokenData = RefreshToken::get($refreshToken);
        return self::$tokenData;
    }

    /**
     * Returns all the Refresh Tokens for the given Credential
     * @param integer $credentialID
     * @return array{}[]
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
        if (!self::$useRefresh) {
            return "";
        }

        return RefreshToken::create($credentialID, self::$longTerm);
    }

    /**
     * Updates a Refresh Token
     * @param string $refreshToken
     * @return string
     */
    public static function updateRefreshToken(string $refreshToken): string {
        self::load();
        if (!self::$useRefresh) {
            return "";
        }

        $token = self::getOne($refreshToken);
        if ($token->isEmpty() || $token->modifiedTime < time() - 24 * 3600) {
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
        if (!self::$useRefresh || empty($refreshToken)) {
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
        if (!self::$useRefresh || empty($credentialID)) {
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
        if (!self::$useRefresh) {
            return false;
        }

        return RefreshToken::removeOld(self::$longTerm);
    }
}
