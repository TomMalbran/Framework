<?php
namespace Framework\Provider;

use Framework\Config\Config;

use Firebase\JWT\JWT;

/**
 * The Microsoft Provider
 */
class Microsoft {

    private static bool  $loaded = false;
    private static mixed $config = null;


    /**
     * Creates the Microsoft Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded = true;
        self::$config = Config::get("microsoft");
        return false;
    }



    /**
     * Returns the Email from the given Token
     * @param string $idToken
     * @return string
     */
    public static function getAuthEmail(string $idToken): string {
        self::load();
        if (empty($idToken)) {
            return "";
        }


        $tokens = explode(".", $idToken);
        if (count($tokens) != 3) {
            return "";
        }

        [ $head64, $body64, $crypto64 ] = $tokens;
        $header    = JWT::jsonDecode(JWT::urlsafeB64Decode($head64));
        $payload   = JWT::jsonDecode(JWT::urlsafeB64Decode($body64));
        $signature = JWT::urlsafeB64Decode($crypto64);

        if ($header === null || $payload === null || $signature === null) {
            return "";
        }
        if ($payload->aud !== self::$config->client) {
            return "";
        }
        if ($payload->exp < time()) {
            return "";
        }
        return $payload->email;
    }
}
