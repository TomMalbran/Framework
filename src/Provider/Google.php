<?php
namespace Framework\Provider;

use Framework\Config\Config;

/**
 * The Google Provider
 */
class Google {

    private const BaseUrl = "https://www.googleapis.com/oauth2/v1/";


    /**
     * Returns the Data from the given Token
     * @param string $accessToken
     * @return array{email:string,firstName:string,lastName:string}|null
     */
    public static function getAuthAccount(string $accessToken): ?array {
        if ($accessToken === "") {
            return null;
        }

        /** @var array<string,string> */
        $response = Curl::execute("GET", self::BaseUrl . "userinfo", null, [
            "Authorization" => "Bearer $accessToken",
        ]);
        if (!isset($response["email"])) {
            return null;
        }

        return [
            "email"     => $response["email"],
            "firstName" => $response["given_name"]  ?? "",
            "lastName"  => $response["family_name"] ?? "",
        ];
    }

    /**
     * Returns the Email from the given Token
     * @param string $accessToken
     * @return string
     */
    public static function getAuthEmail(string $accessToken): string {
        $account = self::getAuthAccount($accessToken);
        if (isset($account["email"])) {
            return $account["email"];
        }
        return "";
    }
}
