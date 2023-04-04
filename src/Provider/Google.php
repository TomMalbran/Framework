<?php
namespace Framework\Provider;

use Framework\Config\Config;

use Google\Client;
use Google\Service\Oauth2;

/**
 * The Google Provider
 */
class Google {

    private static bool    $loaded = false;
    private static mixed   $config = null;
    private static ?Client $client = null;


    /**
     * Creates the Google Provider
     * @param string $scopes Optional.
     * @return Client
     */
    public static function load(string $scopes = ""): Client {
        if (self::$loaded) {
            return self::$client;
        }

        self::$loaded = true;
        self::$config = Config::get("google");

        self::$client = new Client([
            "client_id"     => self::$config->client,
            "client_secret" => self::$config->secret,
            "access_type"   => "offline",
            "scopes"        => $scopes,
        ]);
        return self::$client;
    }


    /**
     * Returns the Email from the given Token
     * @param string $accessToken
     * @param string $idToken
     * @return string
     */
    public static function getAuthEmail(string $accessToken, string $idToken): string {
        self::load();
        if (!empty($accessToken)) {
            self::$client->setAccessToken($accessToken);
            $oauth   = new Oauth2(self::$client);
            $account = $oauth->userinfo->get();
            if (!empty($account["email"])) {
                return $account["email"];
            }
        }

        if (!empty($idToken)) {
            $result = self::$client->verifyIdToken($idToken);
            if (!empty($result["email"])) {
                return $result["email"];
            }
        }

        return "";
    }
}
