<?php
namespace Framework\Provider;

use Framework\System\ConfigCode;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

/**
 * The SMTP Provider
 */
class SMTP {

    private static bool   $loaded = false;
    private static string $url    = "";
    private static object $config;
    private static object $google;


    /**
     * Creates the SMTP Provider
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return true;
        }

        self::$loaded = true;
        self::$url    = ConfigCode::getString("url");
        self::$config = ConfigCode::getObject("smtp");
        self::$google = ConfigCode::getObject("google");
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
     * @param string $attachment Optional.
     * @return boolean
     */
    public static function sendEmail(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $replyTo,
        string $subject,
        string $body,
        string $attachment = ""
    ): bool {
        self::load();

        $email = new PHPMailer();

        $email->isSMTP();
        $email->isHTML(true);
        $email->clearAllRecipients();
        $email->clearReplyTos();

        $email->Timeout     = 10;
        $email->Host        = self::$config->host;
        $email->Port        = self::$config->port   ?: 25;
        $email->SMTPSecure  = self::$config->secure ?: "";
        $email->SMTPAuth    = true;
        $email->SMTPAutoTLS = false;

        $username   = !empty(self::$config->username) ? self::$config->username : self::$config->email;
        $showErrors = !empty(self::$config->showErrors);

        if (!empty(self::$config->useOauth)) {
            $email->SMTPAuth = true;
            $email->AuthType = "XOAUTH2";

            $provider = new Google([
                "clientId"     => self::$google->client,
                "clientSecret" => self::$google->secret,
            ]);
            $email->setOAuth(new OAuth([
                "provider"     => $provider,
                "clientId"     => self::$google->client,
                "clientSecret" => self::$google->secret,
                "refreshToken" => self::$config->refreshToken,
                "userName"     => $username,
            ]));
        } else {
            $email->Username = $username;
            $email->Password = self::$config->password;
        }

        $email->CharSet  = "UTF-8";
        $email->From     = $fromEmail;
        $email->FromName = $fromName;
        $email->Subject  = $subject;
        $email->Body     = $body;

        $email->addAddress($toEmail);
        if (!empty($replyTo)) {
            $email->addReplyTo($replyTo, $fromName);
        }
        if (!empty($attachment)) {
            $email->AddAttachment($attachment);
        }

        if ($showErrors) {
            $email->SMTPDebug = 3;
        }

        $result = $email->send();

        if ($showErrors && !$result) {
            echo "Message could not be sent.";
            echo "Email Error: " . $email->ErrorInfo;
        }
        return $result;
    }



    /**
     * Returns the Google Auth Url
     * @param string $redirectUri
     * @return string
     */
    public static function getAuthUrl(string $redirectUri): string {
        self::load();

        $options  = [ "scope" => [ "https://mail.google.com/" ]];
        $provider = new Google([
            "clientId"     => self::$google->client,
            "clientSecret" => self::$google->secret,
            "redirectUri"  => self::$url . $redirectUri,
            "accessType"   => "offline",
        ]);
        return $provider->getAuthorizationUrl($options);
    }

    /**
     * Returns the Google Refresh Token
     * @param string $redirectUri
     * @param string $code
     * @return string
     */
    public static function getAuthToken(string $redirectUri, string $code): string {
        self::load();

        $provider = new Google([
            "clientId"     => self::$google->client,
            "clientSecret" => self::$google->secret,
            "redirectUri"  => self::$url . $redirectUri,
            "accessType"   => "offline",
        ]);
        $token = $provider->getAccessToken("authorization_code", [ "code" => $code ]);
        return $token->getRefreshToken();
    }
}
