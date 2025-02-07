<?php
namespace Framework\Provider;

use Framework\System\Config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

/**
 * The SMTP Provider
 */
class SMTP {

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
        $email = new PHPMailer();

        $email->isSMTP();
        $email->isHTML(true);
        $email->clearAllRecipients();
        $email->clearReplyTos();

        $email->Timeout     = 10;
        $email->Host        = Config::getSmtpHost();
        $email->Port        = Config::getSmtpPort();
        $email->SMTPSecure  = Config::getSmtpSecure();
        $email->SMTPAuth    = true;
        $email->SMTPAutoTLS = false;

        if (Config::isSmtpOauth()) {
            $email->SMTPAuth = true;
            $email->AuthType = "XOAUTH2";

            $provider = new Google([
                "clientId"     => Config::getGoogleClient(),
                "clientSecret" => Config::getGoogleSecret(),
            ]);
            $email->setOAuth(new OAuth([
                "provider"     => $provider,
                "clientId"     => Config::getGoogleClient(),
                "clientSecret" => Config::getGoogleSecret(),
                "refreshToken" => Config::getGoogleRefreshToken(),
                "userName"     => Config::getSmtpUsername(),
            ]));
        } else {
            $email->Username = Config::getSmtpUsername();
            $email->Password = Config::getSmtpPassword();
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

        if (Config::isSmtpDebug()) {
            $email->SMTPDebug = 3;
        }

        $result = $email->send();

        if ($email->SMTPDebug > 0 && !$result) {
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
        $options  = [ "scope" => [ "https://mail.google.com/" ]];
        $provider = new Google([
            "clientId"     => Config::getGoogleClient(),
            "clientSecret" => Config::getGoogleSecret(),
            "redirectUri"  => Config::getUrl($redirectUri),
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
        $provider = new Google([
            "clientId"     => Config::getGoogleClient(),
            "clientSecret" => Config::getGoogleSecret(),
            "redirectUri"  => Config::getUrl($redirectUri),
            "accessType"   => "offline",
        ]);
        $token = $provider->getAccessToken("authorization_code", [ "code" => $code ]);
        return $token->getRefreshToken();
    }
}
