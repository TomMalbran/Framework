<?php
// spell-checker: ignore  contactslist, managecontact, addforce
namespace Framework\Provider;

use Framework\System\ConfigCode;

/**
 * The Mailjet Provider
 */
class Mailjet {

    const BaseUrl = "https://api.mailjet.com";

    private static bool   $loaded      = false;
    private static string $apiKey      = "";
    private static string $apiSecret   = "";
    private static int    $contactList = 0;


    /**
     * Creates the Mailjet Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded      = true;
        self::$apiKey      = ConfigCode::getString("mailjetKey");
        self::$apiSecret   = ConfigCode::getString("mailjetSecret");
        self::$contactList = ConfigCode::getInt("mailjetList");
        return false;
    }

    /**
     * Executes a Request
     * @param string  $method
     * @param string  $route
     * @param array{} $params Optional.
     * @return array{}
     */
    private static function execute(string $method, string $route, array $params = []): array {
        self::load();
        $url      = self::BaseUrl . $route;
        $userPass = self::$apiKey . ":" . self::$apiSecret;
        $headers  = [ "Content-Type" => "application/json" ];
        return Curl::execute($method, $url, $params, $headers, $userPass, jsonBody: true);
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

        $response = self::execute("POST", "/v3.1/send", [
            "Messages" => [ $message ],
        ]);

        if (!empty($response["Messages"][0])) {
            return $response["Messages"][0]["Status"] == "success";
        }
        return false;
    }



    /**
     * Creates a Contact
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @return boolean
     */
    public static function createContact(string $firstName, string $lastName, string $email): bool {
        self::load();
        if (empty(self::$contactList)) {
            $response = self::execute("POST", "/v3/REST/contact", [
                "Name"  => "$firstName $lastName",
                "Email" => $email,
            ]);
        } else {
            $response = self::execute("POST", "/v3/REST/contactslist/" . self::$contactList . "/managecontact", [
                "Action" => "addforce",
                "Name"   => "$firstName $lastName",
                "Email"  => $email,
            ]);
        }

        return !empty($response["ErrorMessage"]);
    }

    /**
     * Edits a Contact
     * @param string $firstName
     * @param string $lastName
     * @param string $oldEmail
     * @param string $newEmail
     * @return boolean
     */
    public static function editContact(string $firstName, string $lastName, string $oldEmail, string $newEmail): bool {
        $response = self::execute("PUT", "/v3/REST/contact/$oldEmail", [
            "Name"  => "$firstName $lastName",
            "Email" => $newEmail,
        ]);
        if (empty($response["ErrorMessage"])) {
            return true;
        }

        return self::createContact($firstName, $lastName, $newEmail);
    }

    /**
     * Deletes a Contact
     * @param string $email
     * @return boolean
     */
    public static function deleteContact(string $email): bool {
        self::load();
        if (!empty(self::$clientList)) {
            self::execute("POST", "/v3/REST/contactslist/" . self::$contactList . "/managecontact", [
                "Action" => "remove",
                "Email"  => $email,
            ]);
        }

        $response = self::execute("GET", "/v3/REST/contact/$email");
        if (!empty($response["Data"][0])) {
            $contactID = $response["Data"][0]["ID"];
            self::execute("DELETE", "/v4/contacts/$contactID");
        }

        return true;
    }
}
