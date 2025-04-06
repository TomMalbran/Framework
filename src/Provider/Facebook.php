<?php
namespace Framework\Provider;

use Framework\Utils\Utils;

/**
 * The Facebook Provider
 */
class Facebook {

    private const BaseUrl = "https://graph.facebook.com/v16.0/";



    /**
     * Returns the Account from the given Token
     * @param string $accessToken
     * @return array{email:string,firstName:string,lastName:string}|null
     */
    public static function getAuthAccount(string $accessToken): ?array {
        if ($accessToken === "") {
            return null;
        }

        /** @var array<string,string> */
        $response = Curl::execute("GET", self::BaseUrl . "/me", [
            "fields"       => "email,name,first_name,last_name",
            "access_token" => $accessToken,
        ]);
        if (!isset($response["email"])) {
            return null;
        }

        $firstName = $response["first_name"] ?? "";
        $lastName  = $response["last_name"]  ?? "";
        $name      = $response["name"]       ?? "";

        if ($firstName === "" && $lastName === "" && $name !== "") {
            [ $firstName, $lastName ] = Utils::parseName($name);
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
        if (isset($account["email"])) {
            return $account["email"];
        }
        return "";
    }
}
