<?php
// spell-checker: ignore  contactslist, managecontact, addforce
namespace Framework\Provider;

use Framework\System\Config;

/**
 * The Mailjet Provider
 */
class Mailjet {

    private const BaseUrl = "https://api.mailjet.com";


    /**
     * Executes a Request
     * @param string  $method
     * @param string  $route
     * @param array{} $params Optional.
     * @return array{}
     */
    private static function execute(string $method, string $route, array $params = []): array {
        $url      = self::BaseUrl . $route;
        $userPass = Config::getMailjetKey() . ":" . Config::getMailjetSecret();
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
        if ($replyTo !== "") {
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
        $contactList = Config::getMailjetList();
        if (empty($contactList)) {
            $response = self::execute("POST", "/v3/REST/contact", [
                "Name"  => "$firstName $lastName",
                "Email" => $email,
            ]);
        } else {
            $response = self::execute("POST", "/v3/REST/contactslist/$contactList/managecontact", [
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
        $contactList = Config::getMailjetList();
        if (!empty($contactList)) {
            self::execute("POST", "/v3/REST/contactslist/$contactList/managecontact", [
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
