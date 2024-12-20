<?php
namespace Framework\Provider;

use Framework\Config\Config;

/**
 * The Google Provider
 */
class Google {

    const BaseUrl = "https://www.googleapis.com/oauth2/v1/";



    /**
     * Returns the Data from the given Token
     * @param string $accessToken
     * @return array{}
     */
    public static function getAuthAccount(string $accessToken): array {
        if (empty($accessToken)) {
            return [];
        }

        $response = Curl::execute("GET", self::BaseUrl . "userinfo", null, [
            "Authorization" => "Bearer $accessToken",
        ]);
        if (empty($response["email"])) {
            return [];
        }

        return [
            "email"     => $response["email"],
            "firstName" => $response["given_name"],
            "lastName"  => !empty($response["family_name"]) ? $response["family_name"] : "",
        ];
    }

    /**
     * Returns the Email from the given Token
     * @param string $accessToken
     * @return string
     */
    public static function getAuthEmail(string $accessToken): string {
        $account = self::getAuthAccount($accessToken);
        if (!empty($account["email"])) {
            return $account["email"];
        }
        return "";
    }
}
