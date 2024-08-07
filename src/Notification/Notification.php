<?php
namespace Framework\Notification;

use Framework\System\ConfigCode;
use Framework\Provider\Curl;
use Framework\File\Path;

/**
 * The Notification Provider
 */
class Notification {

    public const BaseUrl = "https://onesignal.com/api/v1";

    private static bool   $loaded   = false;
    private static bool   $isActive = false;
    private static string $icon     = "";
    private static string $appID    = "";
    private static string $restKey  = "";


    /**
     * Creates the Notification Provider
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        self::$loaded   = true;
        self::$isActive = ConfigCode::getBoolean("notificationIsActive");
        self::$icon     = ConfigCode::getString("notificationIcon");

        self::$appID    = ConfigCode::getString("onesignalAppId");
        self::$restKey  = ConfigCode::getString("onesignalRestKey");
        return true;
    }



    /**
     * Send to All
     * @param string  $title
     * @param string  $body
     * @param string  $url
     * @param string  $type
     * @param integer $dataID
     * @return string|null
     */
    public static function sendToAll(string $title, string $body, string $url, string $type, int $dataID): ?string {
        return self::send($title, $body, $type, $url, $dataID, [
            "included_segments" => [ "All" ],
        ]);
    }

    /**
     * Send to Some
     * @param string   $title
     * @param string   $body
     * @param string   $url
     * @param string   $type
     * @param integer  $dataID
     * @param string[] $playerIDs
     * @return string|null
     */
    public static function sendToSome(string $title, string $body, string $url, string $type, int $dataID, array $playerIDs): ?string {
        if (empty($playerIDs)) {
            return null;
        }
        return self::send($title, $body, $url, $type, $dataID, [
            "include_player_ids" => $playerIDs,
        ]);
    }

    /**
     * Send to Some
     * @param string  $title
     * @param string  $body
     * @param string  $url
     * @param string  $type
     * @param integer $dataID
     * @param string  $playerID
     * @return string|null
     */
    public static function sendToOne(string $title, string $body, string $url, string $type, int $dataID, string $playerID): ?string {
        return self::send($title, $body, $url, $type, $dataID, [
            "include_player_ids" => [ $playerID ],
        ]);
    }

    /**
     * Sends the Notification
     * @param string  $title
     * @param string  $body
     * @param string  $url
     * @param string  $type
     * @param integer $dataID
     * @param array{} $params
     * @return string|null
     */
    private static function send(string $title, string $body, string $url, string $type, int $dataID, array $params): ?string {
        self::load();
        if (!self::$isActive) {
            return null;
        }

        $icon = "";
        if (!empty(self::$icon)) {
            $icon = Path::forInternalFiles(self::$icon);
        }

        $data = [
            "app_id"         => self::$appID,
            "headings"       => [ "en" => $title ],
            "contents"       => [ "en" => $body  ],
            "url"            => ConfigCode::getUrl("url", $url),
            "large_icon"     => $icon,
            "ios_badgeType"  => "Increase",
            "ios_badgeCount" => 1,
            "data"           => [
                "type"   => $type,
                "dataID" => $dataID,
            ],
        ] + $params;

        $headers = [
            "Content-Type"  => "application/json; charset=utf-8",
            "Authorization" => "Basic " . self::$restKey,
        ];
        $response = Curl::post(self::BaseUrl . "/notifications", $data, $headers, jsonBody: true);

        if (empty($response["id"])) {
            return null;
        }
        return $response["id"];
    }
}
