<?php
namespace Framework\Notification;

use Framework\Application;
use Framework\Provider\Curl;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Notification Provider
 */
class Notification {

    public const BaseUrl = "https://api.onesignal.com";


    /**
     * Send to All
     * @param string  $title
     * @param string  $message
     * @param string  $url
     * @param string  $dataType
     * @param integer $dataID
     * @return string|null
     */
    public static function sendToAll(string $title, string $message, string $url, string $dataType, int $dataID): ?string {
        return self::send($title, $message, $url, $dataType, $dataID, [
            "included_segments" => [ "All" ],
        ]);
    }

    /**
     * Send to Some
     * @param string   $title
     * @param string   $message
     * @param string   $url
     * @param string   $dataType
     * @param integer  $dataID
     * @param string[] $playerIDs
     * @return string|null
     */
    public static function sendToSome(string $title, string $message, string $url, string $dataType, int $dataID, array $playerIDs): ?string {
        if (count($playerIDs) === 0) {
            return null;
        }

        $params = [
            "include_subscription_ids" => $playerIDs,
        ];
        if (Config::isNotificationUseAlias()) {
            $params = [
                "include_aliases" => [
                    "onesignal_id" => $playerIDs,
                ],
            ];
        }

        return self::send($title, $message, $url, $dataType, $dataID, $params);
    }

    /**
     * Sends the Notification
     * @param string              $title
     * @param string              $message
     * @param string              $url
     * @param string              $dataType
     * @param integer             $dataID
     * @param array<string,mixed> $params
     * @return string|null
     */
    private static function send(string $title, string $message, string $url, string $dataType, int $dataID, array $params): ?string {
        if (!Config::isNotificationActive()) {
            return null;
        }

        $icon = Config::getNotificationIcon();
        if ($icon !== "") {
            $icon = Application::getApplUrl($icon);
        }

        $fullUrl = $url;
        if (!Strings::startsWith($url, "http")) {
            $fullUrl = Config::getUrl($url);
        }

        $data = [
            "app_id"         => Config::getOnesignalAppId(),
            "target_channel" => "push",
            "headings"       => [ "en" => $title ],
            "contents"       => [ "en" => $message ],
            "url"            => $fullUrl,
            "large_icon"     => $icon,
            "ios_badgeType"  => "Increase",
            "ios_badgeCount" => 1,
            "data"           => [
                "type"   => $dataType,
                "dataID" => $dataID,
            ],
        ] + $params;

        $headers = [
            "Content-Type"  => "application/json; charset=utf-8",
            "Authorization" => "Basic " . Config::getOnesignalRestKey(),
        ];
        $response = Curl::execute("POST", self::BaseUrl . "/notifications", $data, $headers, jsonBody: true);

        if (!is_array($response) || !isset($response["id"]) || Arrays::isEmpty($response["id"])) {
            return null;
        }
        return Strings::toString($response["id"]);
    }
}
