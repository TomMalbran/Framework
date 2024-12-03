<?php
namespace Framework\Provider;

use Framework\System\ConfigCode;

/**
 * The SendGrid Provider
 */
class SendGrid {

    const BaseUrl = "https://api.sendgrid.com/v3/";

    private static bool   $loaded = false;
    private static string $apiKey = "";


    /**
     * Creates the SendGrid Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded = true;
        self::$apiKey = ConfigCode::getString("sendGridKey");
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

        $url     = self::BaseUrl . "mail/send";
        $headers = [
            "Content-Type"  => "application/json",
            "Authorization" => "Bearer " . self::$apiKey,
        ];
        $params = [
            "personalizations" => [[ "to" => [[ "email" => $toEmail ]] ]],
            "from"             => [
                "email" => $fromEmail,
                "name"  => $fromName,
            ],
            "subject"          => $subject,
            "content"          => [
                [
                    "type"  => "text/html",
                    "value" => $body,
                ],
            ],
        ];
        if (!empty($replyTo)) {
            $params["reply_to"] = [
                "email" => $replyTo,
                "name"  => $fromName,
            ];
        }
        $response = Curl::execute("POST", $url, $params, $headers, jsonBody: true);

        return empty($response);
    }
}
