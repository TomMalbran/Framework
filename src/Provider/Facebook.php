<?php
namespace Framework\Provider;

use Framework\Config\Config;

/**
 * The Facebook Provider
 */
class Facebook {

    const BaseUrl = "https://graph.facebook.com/v16.0/";

    private static bool  $loaded = false;
    private static mixed $config = null;


    /**
     * Creates the Facebook Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded = true;
        self::$config = Config::get("meta");
        return false;
    }



    /**
     * Returns the Email from the given Token
     * @param string $accessToken
     * @return string
     */
    public static function getAuthEmail(string $accessToken): string {
        self::load();
        if (empty($accessToken)) {
            return "";
        }

        $response = Curl::get(self::BaseUrl . "/me", [
            "fields"       => "email",
            "access_token" => $accessToken,
        ]);

        if (empty($response["email"])) {
            return "";
        }
        return $response["email"];
    }
}
