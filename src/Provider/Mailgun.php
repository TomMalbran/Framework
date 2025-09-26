<?php
// spell-checker: ignore  mailgun
namespace Framework\Provider;

use Framework\Provider\Type\DomainData;
use Framework\System\Config;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;
use Framework\Utils\Utils;

/**
 * The Mailgun Provider
 */
class Mailgun {

    private const BaseUrl = "https://api.mailgun.net";



    /**
     * Executes a Request
     * @param string              $method
     * @param string              $route
     * @param array<string,mixed> $params Optional.
     * @return Dictionary
     */
    private static function execute(string $method, string $route, array $params = []): Dictionary {
        $url      = self::BaseUrl . $route;
        $userPass = "api:" . Config::getMailgunKey();
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
        $params = [
            "from"    => "$fromName <$fromEmail>",
            "to"      => $toEmail,
            "subject" => $subject,
            "html"    => $body,
        ];
        if ($replyTo !== "") {
            $params["h:Reply-To"] = [
                "Email" => $replyTo,
                "Name"  => $fromName,
            ];
        }

        $domain   = Utils::getEmailDomain($fromEmail);
        $response = self::execute("POST", "/v3/$domain/messages", $params);
        return $response->hasValue("id");
    }



    /**
     * Returns a Domain
     * @param string  $domain
     * @return DomainData
     */
    public static function getDomain(string $domain): DomainData {
        $response = self::execute("GET", "/v4/domains/$domain", [
            "h:extended" => "true",
            "h:with_dns" => "true",
        ]);
        $result = new DomainData();
        $result->isEmpty = false;
        $result->domain  = $domain;

        foreach ($response->getList("sending_dns_records") as $record) {
            $type  = $record->getString("record_type");
            $name  = $record->getString("name");
            $value = $record->getString("value");

            if (Strings::contains($value, "spf")) {
                $result->spfType  = $type;
                $result->spfHost  = $name;
                $result->spfValue = $value;
            } elseif (Strings::contains($value, "rsa")) {
                $result->dkimType  = $type;
                $result->dkimHost  = $name;
                $result->dkimValue = $value;
            } elseif (Strings::contains($value, "mailgun")) {
                $result->trackingType  = $type;
                $result->trackingHost  = $name;
                $result->trackingValue = $value;
            }
        }
        return $result;
    }

    /**
     * Creates a Domain
     * @param string $domain
     * @return boolean
     */
    public static function createDomain(string $domain): bool {
        $response = self::execute("POST", "/v4/domains", [
            "name" => $domain,
        ]);
        return $response->hasValue("domain");
    }

    /**
     * Verifies a Domain
     * @param string $domain
     * @return boolean
     */
    public static function verifyDomain(string $domain): bool {
        $response = self::execute("PUT", "/v4/domains/$domain/verify");
        return $response->hasValue("domain");
    }

    /**
     * Deletes a Domain
     * @param string $domain
     * @return boolean
     */
    public static function deleteDomain(string $domain): bool {
        $response = self::execute("DELETE", "/v4/domains/$domain");
        return !$response->hasValue("message");
    }
}
