<?php
namespace Framework\Provider;

use Framework\System\Config;
use Framework\Utils\Dictionary;
use Framework\Utils\Utils;

use Firebase\JWT\JWT;

/**
 * The Microsoft Provider
 */
class Microsoft {

    /**
     * Returns the Account from the given Token
     * @param string $idToken
     * @return array{email:string,firstName:string,lastName:string}|null
     */
    public static function getAuthAccount(string $idToken): ?array {
        if ($idToken === "") {
            return null;
        }

        $tokens = explode(".", $idToken);
        if (count($tokens) !== 3) {
            return null;
        }

        [ $head64, $body64, $crypto64 ] = $tokens;
        $header    = JWT::jsonDecode(JWT::urlsafeB64Decode($head64));
        $payload   = JWT::jsonDecode(JWT::urlsafeB64Decode($body64));
        $signature = JWT::urlsafeB64Decode($crypto64);

        if ($header === "" || $payload === "" || $signature === "") {
            return null;
        }
        $payloadData = new Dictionary($payload);

        if ($payloadData->getString("aud") !== Config::getMicrosoftClient()) {
            return null;
        }
        if ($payloadData->getInt("exp") < time()) {
            return null;
        }
        if (!$payloadData->hasValue("email") || !$payloadData->hasValue("name")) {
            return null;
        }

        // Split the Name
        [ $firstName, $lastName ] = Utils::parseName($payloadData->getString("name"));
        return [
            "email"     => $payloadData->getString("email"),
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
        if ($account === null || $account["email"] === "") {
            return "";
        }
        return $account["email"];
    }
}
