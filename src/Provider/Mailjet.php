<?php
namespace Framework\Provider;

use Framework\System\ConfigCode;

/**
 * The Mailjet Provider
 */
class Mailjet {

    const BaseUrl = "https://api.mailjet.com/v3.1/";

    private static bool   $loaded    = false;
    private static string $apiKey    = "";
    private static string $apiSecret = "";


    /**
     * Creates the Mailjet Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded    = true;
        self::$apiKey    = ConfigCode::getString("mailjetKey");
        self::$apiSecret = ConfigCode::getString("mailjetSecret");
        return false;
    }



    /**
     * Sends the Email
     * @param string $toEmail
     * @param string $fromEmail
     * @param string $fromName
     * @param string $replyTo
     * @param string $subject
     * @param string $body
     * @return boolean
     */
    public static function sendEmail(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $replyTo,
        string $subject,
        string $body
    ): bool {
        self::load();

        $message = [
            "From"     => [
                "Email" => $fromEmail,
                "Name"  => $fromName,
            ],
            "To"       => [
                [ "Email" => $toEmail ],
            ],
            "Subject"  => $subject,
            "HTMLPart" => $body,
        ];
        if (!empty($replyTo)) {
            $message["ReplyTo"] = [
                "Email" => $replyTo,
                "Name"  => $fromName,
            ];
        }

        $url      = self::BaseUrl . "send";
        $headers  = [ "Content-Type" => "application/json" ];
        $params   = [ "Messages" => [ $message ] ];

        $userPass = self::$apiKey . ":" . self::$apiSecret;
        $response = Curl::post($url, $params, $headers, $userPass, jsonBody: true);

        if (!empty($response["Messages"][0])) {
            return $response["Messages"][0]["Status"] == "success";
        }
        return $response["email"];
    }
}
