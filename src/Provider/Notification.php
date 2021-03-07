<?php
namespace Framework\Provider;

use Framework\Config\Config;
use Framework\File\Path;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Client\Common\HttpMethodsClient as HttpClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;

use OneSignal\Config as OneSignalConfig;
use OneSignal\OneSignal as OneSignalAPI;

/**
 * The Notification Provider
 */
class Notification {

    private $settings;

    private static $loaded = false;
    private static $config = null;
    private static $api    = null;


    /**
     * Creates the Notification Provider
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$config = Config::get("onesignal");

            if (!empty(self::$config->appId) && !empty(self::$config->restKey)) {
                $config = new OneSignalConfig();
                $config->setApplicationId(self::$config->appId);
                $config->setApplicationAuthKey(self::$config->restKey);

                $guzzle    = new GuzzleClient([]);
                $client    = new HttpClient(new GuzzleAdapter($guzzle), new GuzzleMessageFactory());
                self::$api = new OneSignalAPI($config, $client);
            }
        }
    }



    /**
     * Send to All
     * @param string  $title
     * @param string  $body
     * @param integer $type
     * @param integer $id
     * @return string
     */
    public static function sendToAll(string $title, string $body, int $type, int $id) {
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
     * @return string
     */
    public static function sendToSome(string $title, string $body, int $type, int $id, array $playerIDs) {
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
     * @return string
     */
    public static function sendToOne(string $title, string $body, int $type, int $id, string $playerID) {
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
     * @param array   $params
     * @return string
     */
    private static function send(string $title, string $body, int $type, int $id, array $params) {
        self::load();
        if (empty(self::$api)) {
            return null;
        }

        $icon = "";
        if (!empty(self::$config->icon)) {
            $icon = Path::getUrl("framework", self::$config->icon);
        }
        $response = self::$api->notifications->add([
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
