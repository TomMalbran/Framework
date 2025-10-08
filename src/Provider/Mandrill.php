<?php
namespace Framework\Provider;

use Framework\System\Config;
use Framework\Utils\Dictionary;

/**
 * The Mandrill Provider
 */
class Mandrill {

    private const BaseUrl = "https://mandrillapp.com/api/1.0/";


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
        string $body,
    ): bool {
        $message = [
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
        ];
        if ($replyTo !== "") {
            $message["headers"] = [
                "reply-to" => $replyTo,
            ];
        }

        $url      = self::BaseUrl . "messages/send.json";
        $headers  = [
            "Content-Type" => "application/json",
        ];
        $params   = [
            "key"     => Config::getMandrillKey(),
            "message" => $message,
            "async"   => false,
            "send_at" => date("Y-m-d H:i:s"),
        ];
        $result   = Curl::execute("POST", $url, $params, $headers, jsonBody: true);

        $response = new Dictionary($result);
        return $response->getString("status") === "sent";
    }
}
