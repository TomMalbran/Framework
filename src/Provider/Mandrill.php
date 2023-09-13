<?php
namespace Framework\Provider;

use Framework\Config\Config;

/**
 * The Mandrill Provider
 */
class Mandrill {

    const BaseUrl = "https://mandrillapp.com/api/1.0";

    private static bool   $loaded = false;
    private static string $apiKey = "";


    /**
     * Creates the Mandrill Provider
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded = true;
        self::$apiKey = Config::get("mandrillKey");
        return false;
    }


    /**
     * Sends the Email
     * @param string $toEmail
     * @param string $fromEmail
     * @param string $fromName
     * @param string $subject
     * @param string $body
     * @return boolean
     */
    public static function sendEmail(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $body
    ): bool {
        self::load();

        $headers = [
            "Content-Type" => "application/json",
        ];
        $data = [
            "key"     => self::$apiKey,
            "message" => [
                "to"                  => [
                    [
                        "email" => $toEmail,
                        "type"  => "to",
                    ],
                ],
                "from_email"          => $fromEmail,
                "from_name"           => $fromName,
                "subject"             => $subject,
                "html"                => $body,
                "important"           => false,
                "track_opens"         => true,
                "track_clicks"        => true,
                "auto_text"           => false,
                "auto_html"           => true,
                "inline_css"          => null,
                "url_strip_qs"        => null,
                "preserve_recipients" => null,
                "view_content_link"   => null,
                "tracking_domain"     => null,
                "signing_domain"      => null,
                "return_path_domain"  => null,
            ],
            "async"   => false,
            "send_at" => date("Y-m-d H:i:s"),
        ];
        $response = Curl::post(self::BaseUrl . "/messages/send.json", $data, $headers, true);

        if (empty($response["status"])) {
            return $response["status"] === "sent";
        }
        return false;
    }
}
