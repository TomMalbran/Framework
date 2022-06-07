<?php
namespace Framework\Provider;

use Framework\Config\Config;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Exception;
use stdClass;

/**
 * The JWT Provider
 */
class JWT {

    private static $loaded    = false;
    private static $algorithm = "HS256";
    private static $secretKey = "";
    private static $longTerm  = 10 * 365 * 24 * 3600;
    private static $shortTerm = 30 * 60;


    /**
     * Loads the JWT Config
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            FirebaseJWT::$leeway = 1000;
            self::$loaded    = true;
            self::$secretKey = Config::get("authKey");
            self::$shortTerm = Config::get("authHours");
        }
    }



    /**
     * Creates a JWT Token
     * @param integer $time
     * @param array   $data
     * @param boolean $forLongTerm Optional.
     * @return string
     */
    public static function create(int $time, array $data, bool $forLongTerm = false): string {
        self::load();
        $length = $forLongTerm ? self::$longTerm : self::$shortTerm;
        $token  = [
            "iat"  => $time,            // Issued at: time when the token was generated
            "nbf"  => $time + 10,       // Not before: 10 seconds
            "exp"  => $time + $length,  // Expire: In x hour
            "data" => $data,
        ];
        return FirebaseJWT::encode($token, self::$secretKey, self::$algorithm);
    }

    /**
     * Returns true if the JWT Token is Valid
     * @param string $token
     * @return boolean
     */
    public static function isValid(string $token): bool {
        self::load();
        if (empty($token)) {
            return false;
        }
        try {
            FirebaseJWT::decode($token, new Key(self::$secretKey, self::$algorithm));
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Returns the JWT Token Data
     * @param string $token
     * @return object
     */
    public static function getData(string $token): object {
        self::load();
        try {
            $decode = FirebaseJWT::decode($token, new Key(self::$secretKey, self::$algorithm));
        } catch (Exception $e) {
            return new stdClass();
        }
        return (object)$decode->data;
    }
}
