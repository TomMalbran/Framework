<?php
namespace Framework\Provider;

use Framework\Config\Config;
use Framework\File\Path;

use OneSignal\Config as OneSignalConfig;
use OneSignal\OneSignal as OneSignalAPI;
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * The Notification Provider
 */
class Notification {

    private static bool          $loaded = false;
    private static mixed         $config = null;
    private static ?OneSignalAPI $api    = null;


    /**
     * Creates the Notification Provider
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$config = Config::get("onesignal");

        if (!empty(self::$config->appId) && !empty(self::$config->restKey)) {
            $config         = new OneSignalConfig(self::$config->appId, self::$config->restKey);
            $httpClient     = new Psr18Client();
            $requestFactory = new Psr17Factory();
            $streamFactory  = new Psr17Factory();
            self::$api      = new OneSignalAPI($config, $httpClient, $requestFactory, $streamFactory);
        }
        return true;
    }



    /**
     * Send to All
     * @param string  $title
     * @param string  $body
     * @param integer $type
     * @param integer $id
     * @return string|null
     */
    public static function sendToAll(string $title, string $body, int $type, int $id): ?string {
        return self::send($title, $body, $type, $id, [
            "included_segments" => [ "All" ],
        ]);
    }

    /**
     * Send to Some
     * @param string   $title
     * @param string   $body
     * @param integer  $type
     * @param integer  $id
     * @param string[] $playerIDs
     * @return string|null
     */
    public static function sendToSome(string $title, string $body, int $type, int $id, array $playerIDs): ?string {
        if (empty($playerIDs)) {
            return null;
        }
        return self::send($title, $body, $type, $id, [
            "include_player_ids" => $playerIDs,
        ]);
    }

    /**
     * Send to Some
     * @param string  $title
     * @param string  $body
     * @param integer $type
     * @param integer $id
     * @param string  $playerID
     * @return string|null
     */
    public static function sendToOne(string $title, string $body, int $type, int $id, string $playerID): ?string {
        return self::send($title, $body, $type, $id, [
            "include_player_ids" => [ $playerID ],
        ]);
    }

    /**
     * Sends the Notification
     * @param string  $title
     * @param string  $body
     * @param integer $type
     * @param integer $id
     * @param array{} $params
     * @return string|null
     */
    private static function send(string $title, string $body, int $type, int $id, array $params): ?string {
        self::load();
        if (empty(self::$api)) {
            return null;
        }

        $icon = "";
        if (!empty(self::$config->icon)) {
            $icon = Path::getUrl("framework", self::$config->icon);
        }
        $response = self::$api->notifications()->add([
            "headings"       => [ "en" => $title ],
            "contents"       => [ "en" => $body  ],
            "large_icon"     => $icon,
            "ios_badgeType"  => "Increase",
            "ios_badgeCount" => 1,
            "data"           => [ "type" => $type, "id" => $id ],
        ] + $params);

        if (empty($response["id"])) {
            return null;
        }
        return $response["id"];
    }
}
