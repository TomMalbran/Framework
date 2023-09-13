<?php
namespace Framework\Provider;

use Framework\Config\Config;

/**
 * The Mailjet Provider
 */
class Mailjet {

    const BaseUrl = "https://api.mailjet.com/v3.1";

    private static bool   $loaded    = false;
    private static string $apiKey    = "";
    private static string $apiSecret = "";


    /**
     * Creates the Mailjet Provider
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded    = true;
        self::$apiKey    = Config::get("mailjetKey");
        self::$apiSecret = Config::get("mailjetSecret");
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

        $data = [
            "Messages" => [
				[
					"From"     => [
						"Email" => $fromEmail,
						"Name"  => $fromName,
					],
					"To"       => [
						[ "Email" => $toEmail ],
					],
					"Subject"  => $subject,
					"HTMLPart" => $body,
                ],
		    ],
        ];
        $headers = [
            "Content-Type" => "application/json",
        ];
        $userPass = self::$apiKey . ":" . self::$apiSecret;
        $response = Curl::post(self::BaseUrl . "/send", $data, $headers, true, $userPass);

        if (!empty($response["Messages"][0])) {
            return $response["Messages"][0]["Status"] == "success";
        }
        return $response["email"];
    }
}
