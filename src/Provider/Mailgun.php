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
        $response = Curl::execute($method, $url, $params, userPass: $userPass);
        return new Dictionary($response);
    }



    /**
     * Sends the Email (used for external calls)
     * @param string $toEmail
     * @param string $fromEmail
     * @param string $fromName
     * @param string $subject
     * @param string $body
     * @param string $replyTo   Optional.
     * @return string
     */
    public static function send(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $body,
        string $replyTo = "",
    ): string {
        $params = [
            "from"    => "$fromName <{$fromEmail}>",
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
        $uuid     = $response->getString("id");
        $uuid     = Strings::replace($uuid, [ "<", ">" ], "");
        return $uuid;
    }

    /**
     * Sends the Email (used for the Email class)
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
        $result = self::send($toEmail, $fromEmail, $fromName, $subject, $body, $replyTo);
        return $result !== "";
    }



    /**
     * Returns a Domain
     * @param string $domain
     * @return DomainData
     */
    public static function getDomain(string $domain): DomainData {
        $response = self::execute("GET", "/v4/domains/$domain", [
            "h:extended" => "true",
            "h:with_dns" => "true",
        ]);
        return self::generateDomainData($response);
    }

    /**
     * Generates a Domain Data object from the API response
     * @param Dictionary $response
     * @return DomainData
     */
    private static function generateDomainData(Dictionary $response): DomainData {
        $result = new DomainData();
        if (!$response->hasValue("domain")) {
            return $result;
        }

        $result->isEmpty = false;
        $result->domain  = $response->getDict("domain")->getString("name");

        foreach ($response->getList("sending_dns_records") as $record) {
            $type    = $record->getString("record_type");
            $name    = $record->getString("name");
            $value   = $record->getString("value");
            $isValid = $record->getString("valid") === "valid";

            if (Strings::contains($value, "spf")) {
                $result->spfType  = $type;
                $result->spfHost  = $name;
                $result->spfValue = $value;
                $result->spfValid = $isValid;
            } elseif (Strings::contains($value, "rsa")) {
                $result->dkimType  = $type;
                $result->dkimHost  = $name;
                $result->dkimValue = $value;
                $result->dkimValid = $isValid;
            } elseif (Strings::contains($value, "mailgun")) {
                $result->trackingType  = $type;
                $result->trackingHost  = $name;
                $result->trackingValue = $value;
                $result->trackingValid = $isValid;
            }
        }

        // Finally get the DMARC record
        $response = self::execute("GET", "/v1/dmarc/records/$result->domain");
        if ($response->hasValue("entry")) {
            $result->dmarcType  = "TXT";
            $result->dmarcHost  = "_dmarc.{$result->domain}";
            $result->dmarcValue = $response->getString("entry");
            $result->dmarcValid = $response->getBool("configured");
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
     * Sets the Tracking for a Domain
     * @param string  $domain
     * @param boolean $tracking
     * @return boolean
     */
    public static function setDomainTracking(string $domain, bool $tracking): bool {
        $response = self::execute("PUT", "/v3/domains/$domain/tracking/open", [
            "active" => $tracking ? "true" : "false",
        ]);
        if (!$response->hasValue("open")) {
            return false;
        }

        $response = self::execute("PUT", "/v3/domains/$domain/tracking/unsubscribe", [
            "active" => $tracking ? "true" : "false",
        ]);
        return $response->hasValue("unsubscribe");
    }

    /**
     * Sets the Webhooks for a Domain
     * @param string $domain
     * @param string $url
     * @return boolean
     */
    public static function setDomainWebhooks(string $domain, string $url): bool {
        $types = [ "delivered", "opened", "permanent_fail", "unsubscribed" ];
        foreach ($types as $type) {
            $response = self::execute("POST", "/v3/$domain/webhooks", [
                "id"  => $type,
                "url" => $url,
            ]);
            if (!$response->hasValue("webhook")) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verifies a Domain
     * @param string $domain
     * @return DomainData
     */
    public static function verifyDomain(string $domain): DomainData {
        $response = self::execute("PUT", "/v4/domains/$domain/verify");
        return self::generateDomainData($response);
    }

    /**
     * Deletes a Domain
     * @param string $domain
     * @return boolean
     */
    public static function deleteDomain(string $domain): bool {
        $response = self::execute("DELETE", "/v3/domains/$domain");
        return Strings::contains($response->getString("message"), "deleted");
    }
}
