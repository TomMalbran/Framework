<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\Config\Config;
use Framework\Config\Settings;
use Framework\Provider\Google;
use Framework\Utils\Strings;

use Google\Client;
use Google\Service\Gmail;

/**
 * The Email Reader Provider
 */
class EmailReader {

    private static bool    $loaded  = false;
    private static string  $url     = "";
    private static mixed   $db      = null;

    private static ?Client $client  = null;
    private static ?Gmail  $service = null;


    /**
     * Loads the Email Reader Config
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$url    = Config::get("url");
        self::$db     = Framework::getDatabase();
        self::$loaded = true;
        return true;
    }

    /**
     * Loads the Email Client Config
     * @param string $redirectUri Optional.
     * @return boolean
     */
    public static function loadClient(string $redirectUri = ""): bool {
        self::load();

        if (empty(self::$client)) {
            self::$client = Google::load(Gmail::GMAIL_READONLY);

            if (!empty($redirectUri)) {
                self::$client->setPrompt("consent");
                self::$client->setRedirectUri(self::$url . $redirectUri);
                return true;
            }
        }

        if (empty(self::$service)) {
            $token = Settings::getCoreData(self::$db, "google");
            if (!empty($token)) {
                self::$client->setAccessToken($token);
                if (self::$client->isAccessTokenExpired() && self::$client->getRefreshToken()) {
                    self::$client->fetchAccessTokenWithRefreshToken(self::$client->getRefreshToken());
                }
                self::$service = new Gmail(self::$client);
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the Google Auth Url
     * @param string $redirectUri
     * @return string
     */
    public static function getAuthUrl(string $redirectUri): string {
        self::loadClient($redirectUri);
        return self::$client->createAuthUrl();
    }

    /**
     * Returns the Google Refresh Token
     * @param string $redirectUri
     * @param string $code
     * @return array{}
     */
    public static function getAuthToken(string $redirectUri, string $code): array {
        self::loadClient($redirectUri);
        $token = self::$client->fetchAccessTokenWithAuthCode($code);
        Settings::setCoreData(self::$db, "google", $token);
        return $token;
    }



    /**
     * Returns the Time of the last Fetch
     * @return integer
     */
    public static function getLastFetch(): int {
        self::load();
        return Settings::getCore(self::$db, "lastGmailFetch");
    }

    /**
     * Returns the new Emails
     * @return object[]
     */
    public static function getLatestEmails(): array {
        self::loadClient();
        if (empty(self::$service)) {
            return [];
        }

        $time = Settings::getCore(self::$db, "lastGmailFetch");

        $result   = [];
        $messages = self::$service->users_messages->listUsersMessages("me", [
            "maxResults" => 10,
            "labelIds"   => "INBOX",
            "q"          => "after:$time",
        ]);
        $list = $messages->getMessages();

        foreach ($list as $row) {
            $messageId = $row->getId();
            $message   = self::$service->users_messages->get("me", $messageId, [ "format" => "full" ]);
            $payload   = $message->getPayload();
            $headers   = $payload->getHeaders();
            $subject   = self::getHeader($headers, "Subject");

            if (Strings::contains($subject, "#")) {
                $from     = self::getFromTo($headers, "From");
                $to       = self::getFromTo($headers, "To");
                $body     = self::getBody($payload);

                $result[] = (object)[
                    "id"      => Strings::substringAfter($subject, "#"),
                    "time"    => strtotime(self::getHeader($headers, "Date")),
                    "from"    => $from["name"],
                    "email"   => $from["email"],
                    "subject" => $subject,
                    "message" => self::parseBody($body, $to["name"], $to["email"]),
                ];
            }
        }

        Settings::setCore(self::$db, "lastGmailFetch", time());
        return $result;
    }

    /**
     * Return the header with the given key
     * @param array{}[] $headers
     * @param string    $key
     * @return string
     */
    private static function getHeader(array $headers, string $key): string {
        foreach ($headers as $header) {
            if ($header["name"] == $key) {
                return $header["value"];
            }
        }
        return "";
    }

    /**
     * Parses the Email From/To
     * @param array{}[] $headers
     * @param string    $key
     * @return array{}
     */
    private static function getFromTo(array $headers, string $key): array {
        $string = self::getHeader($headers, $key);
        $name   = trim(Strings::substringBefore($string, "<"));
        $email  = trim(Strings::substringBetween($string, "<", ">"));
        return [ "name" => $name, "email" => $email ];
    }

    /**
     * Return the body from the given payload
     * @param mixed $payload
     * @return string
     */
    private static function getBody(mixed $payload): string {
        $body   = $payload->getBody();
        $result = self::decodeBody($body["data"]);

        // If we didn't find a body, let's look for the parts
        if (empty($result)) {
            $parts = $payload->getParts();
            foreach ($parts as $part) {
                if ($part["body"] && $part["mimeType"] == "text/plain") {
                    $result = self::decodeBody($part["body"]->data);
                    break;
                }

                // Last try: if we didn't find the body in the first parts,
                // let's loop into the parts of the parts.
                if ($part["parts"] && empty($result)) {
                    foreach ($part["parts"] as $subpart) {
                        if ($subpart["body"] && $subpart["mimeType"] == "text/plain") {
                            $result = self::decodeBody($subpart["body"]->data);
                            break;
                        }
                    }
                }
                if (!empty($result)) {
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Decodes the Body
     * @param string|null $body
     * @return string|null
     */
    private static function decodeBody(?string $body): ?string {
        if (empty($body)) {
            return null;
        }
        $rawData        = $body;
        $sanitizedData  = strtr($rawData,"-_", "+/");
        $decodedMessage = base64_decode($sanitizedData);
        return $decodedMessage;
    }

    /**
     * Parses the Body
     * @param string $body
     * @param string $toName
     * @param string $toEmail
     * @return string
     */
    private static function parseBody(string $body, string $toName, string $toEmail): string {
        $body      = Strings::removeHtml($body);
        $parts     = Strings::split($body, "\n");
        $result    = [];
        $lastBlank = true;

        foreach ($parts as $part) {
            $string = trim($part);
            if (Strings::startsWith($string, ">")) {
                continue;
            }
            if (Strings::contains($string, $toName) || Strings::contains($string, $toEmail)) {
                continue;
            }
            if ($lastBlank && empty($string)) {
                continue;
            }
            if ($string === "--" || Strings::startsWith($string, "_____") || $string === "|      |") {
                break;
            }

            $result[] = $string;
            if (empty($string)) {
                $lastBlank = true;
            }
        }
        return Strings::join($result, "\n");
    }
}
