<?php
namespace Framework\Provider;

use Framework\Utils\Utils;

/**
 * The Facebook Provider
 */
class Facebook {

    const BaseUrl = "https://graph.facebook.com/v16.0/";



    /**
     * Returns the Account from the given Token
     * @param string $accessToken
     * @return array{email:string,firstName:string,lastName:string}|null
     */
    public static function getAuthAccount(string $accessToken): ?array {
        if (empty($accessToken)) {
            return null;
        }

        $response = Curl::execute("GET", self::BaseUrl . "/me", [
            "fields"       => "email,name,first_name,last_name",
            "access_token" => $accessToken,
        ]);
        if (empty($response["email"])) {
            return null;
        }

        $firstName = !empty($response["first_name"]) ? $response["first_name"] : "";
        $lastName  = !empty($response["last_name"])  ? $response["last_name"]  : "";
        if (empty($firstName) && empty($lastName) && !empty($response["name"])) {
            [ $firstName, $lastName ] = Utils::parseName($response["name"]);
        }

        return [
            "email"     => $response["email"],
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
