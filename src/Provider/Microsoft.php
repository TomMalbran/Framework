<?php
namespace Framework\Provider;

use Framework\System\Config;
use Framework\Utils\Utils;

use Firebase\JWT\JWT;

/**
 * The Microsoft Provider
 */
class Microsoft {

    /**
     * Returns the Account from the given Token
     * @param string $idToken
     * @return array<string,string>|null
     */
    public static function getAuthAccount(string $idToken): ?array {
        if (empty($idToken)) {
            return null;
        }

        $tokens = explode(".", $idToken);
        if (count($tokens) != 3) {
            return null;
        }

        [ $head64, $body64, $crypto64 ] = $tokens;
        $header    = JWT::jsonDecode(JWT::urlsafeB64Decode($head64));
        $payload   = JWT::jsonDecode(JWT::urlsafeB64Decode($body64));
        $signature = JWT::urlsafeB64Decode($crypto64);

        if ($header === null || $payload === null || $signature === null) {
            return null;
        }
        if ($payload->aud !== Config::getMicrosoftClient()) {
            return null;
        }
        if ($payload->exp < time()) {
            return null;
        }
        if (empty($payload->email) || empty($payload->name)) {
            return null;
        }

        // Split the Name
        [ $firstName, $lastName ] = Utils::parseName($payload->name);
        return [
            "email"     => $payload->email,
            "firstName" => $firstName,
            "lastName"  => $lastName,
        ];
    }

    /**
     * Returns the Email from the given Token
     * @param string $idToken
     * @return string
     */
    public static function getAuthEmail(string $idToken): string {
        $account = self::getAuthAccount($idToken);
        if (!empty($account["email"])) {
            return $account["email"];
        }
        return "";
    }
}
