<?php
// spell-checker: ignore  contactslist, managecontact, addforce
namespace Framework\Provider;

use Framework\Provider\Type\DomainData;
use Framework\System\Config;
use Framework\Utils\Dictionary;

/**
 * The Mailjet Provider
 */
class Mailjet {

    private const BaseUrl = "https://api.mailjet.com";



    /**
     * Executes a Request
     * @param string              $method
     * @param string              $route
     * @param array<string,mixed> $params Optional.
     * @return Dictionary
     */
    private static function execute(string $method, string $route, array $params = []): Dictionary {
        $url      = self::BaseUrl . $route;
        $userPass = Config::getMailjetKey() . ":" . Config::getMailjetSecret();
        $headers  = [ "Content-Type" => "application/json" ];
        $response = Curl::execute($method, $url, $params, $headers, $userPass, jsonBody: true);
        return new Dictionary($response);
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
        return $response->getFirst("Messages")->getString("Status") === "success";
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
        if ($contactList === 0) {
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
        return !$response->hasValue("ErrorMessage");
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
        if ($response->hasValue("ErrorMessage")) {
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
        if ($contactList !== 0) {
            self::execute("POST", "/v3/REST/contactslist/$contactList/managecontact", [
                "Action" => "remove",
                "Email"  => $email,
            ]);
        }

        $response = self::execute("GET", "/v3/REST/contact/$email");
        $data     = $response->getFirst("Data");

        if (!$data->isEmpty()) {
            $contactID = $data->getString("ID");
            self::execute("DELETE", "/v4/contacts/$contactID");
        }
        return true;
    }



    /**
     * Returns a Domain
     * @param string $domain
     * @return DomainData
     */
    public static function getDomain(string $domain): DomainData {
        $response = self::execute("GET", "/v3/REST/sender/*@$domain");
        $data     = $response->getFirst("Data");
        $result   = new DomainData();
        if ($data->isEmpty()) {
            return $result;
        }

        // Get the DNS data
        $response = self::execute("GET", "/v3/REST/dns/$domain");
        $dnsData  = $response->getFirst("Data");

        // Basic Domain Data
        $result->isEmpty  = false;
        $result->isActive = $data->getString("Status") === "Active";
        $result->id       = $data->getString("ID");
        $result->domain   = $domain;

        // Owner DNS Record
        $result->ownerValid = $result->isActive;
        $result->ownerType  = "TXT";
        $result->ownerHost  = $dnsData->getString("OwnerShipTokenRecordName");
        $result->ownerValue = $dnsData->getString("OwnerShipToken");

        // SPF DNS Record
        $result->spfValid   = $dnsData->getString("SPFStatus")  === "OK";
        $result->spfType    = "TXT";
        $result->spfHost    = "@";
        $result->spfValue   = $dnsData->getString("SPFRecordValue");

        // DKIM DNS Record
        $result->dkimValid  = $dnsData->getString("DKIMStatus") === "OK";
        $result->dkimType   = "TXT";
        $result->dkimHost   = $dnsData->getString("DKIMRecordName");
        $result->dkimValue  = $dnsData->getString("DKIMRecordValue");

        return $result;
    }

    /**
     * Creates a Domain
     * @param string $name
     * @param string $domain
     * @return boolean
     */
    public static function createDomain(string $name, string $domain): bool {
        $response = self::execute("POST", "/v3/REST/sender", [
            "Name"            => $name,
            "Email"           => "*@$domain",
            "EmailType"       => "unknown",
            "IsDefaultSender" => "false",
        ]);
        return !$response->hasValue("ErrorMessage");
    }

    /**
     * Edits a Domain
     * @param string $domain
     * @param string $name
     * @return boolean
     */
    public static function editDomain(string $domain, string $name): bool {
        $response = self::execute("PUT", "/v3/REST/sender/*@$domain", [
            "Name" => $name,
        ]);
        return !$response->hasValue("ErrorMessage");
    }

    /**
     * Validates a Domain
     * @param string $domain
     * @return boolean
     */
    public static function validateDomain(string $domain): bool {
        $response = self::execute("POST", "/v3/REST/sender/*@$domain/validate");
        return $response->getString("GlobalError") === "";
    }

    /**
     * Checks a Domain DNS
     * @param integer $senderID
     * @return array{boolean,boolean} [SPF, DKIM]
     */
    public static function checkDomainDNS(string $domain): array {
        $response = self::execute("POST", "/v3/REST/dns/$domain/check");
        $data     = $response->getFirst("Data");
        return [
            $data->getString("SPFStatus")  === "OK",
            $data->getString("DKIMStatus") === "OK",
        ];
    }

    /**
     * Deletes a Domain
     * @param string $domain
     * @return boolean
     */
    public static function deleteDomain(string $domain): bool {
        $response = self::execute("DELETE", "/v3/REST/sender/*@$domain");
        return !$response->hasValue("ErrorMessage");
    }
}
