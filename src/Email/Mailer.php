<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Config\Config;
use Framework\Utils\JSON;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

/**
 * The Mailer Provider
 */
class Mailer {
    
    private static $loaded = false;
    private static $url    = "";
    private static $name   = "";
    private static $smtp   = null;
    private static $google = null;
    
    
    /**
     * Loads the Mailer Config
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$url    = Config::get("url");
            self::$name   = Config::get("name");
            self::$smtp   = Config::get("smtp");
            self::$google = Config::get("google");
        }
    }
    
    
    
    /**
     * Sends Emails in HTML
     * @param string $to
     * @param string $from
     * @param string $fromName
     * @param string $subject
     * @param string $message
     * @return boolean
     */
    public static function sendHtml(
        string $to,
        string $from,
        string $fromName,
        string $subject,
        string $message
    ): bool {
        self::load();

        $name  = self::$name;
        $url   = self::$url;
        $img   = !empty(self::$smtp->header) ? self::$url . self::$smtp->header : "";

        $body  = "<table cellpadding='0' cellspacing='0' border='0' height='100%' width='100%' bgcolor='#f4f4f4' style='background-color:#f4f4f4;background-image:none;background-repeat:repeat;border-spacing:0'>";
        $body .= "<tbody><tr><td style='border-collapse:collapse'>";
        $body .= "<table border='0' width='100%' cellpadding='0' cellspacing='0' align='center' style='max-width:600px;margin-top:auto;margin-bottom:auto;margin-right:auto;margin-left:auto;border-spacing:0'>";
        $body .= "<tbody><tr>";
        $body .= "<td valign='middle' style='padding-top:30px;padding-bottom:20px;padding-right:20px;padding-left:20px;text-align:left;border-collapse:collapse'>";
        $body .= "<a href='$url' target='_blank'><img src='$img' alt='$name' border='0' style='height:50px;display:block;max-width:100%'></a>";
        $body .= "</td></tr><tr>";
        $body .= "<td style='background-color:#ffffff;background-image:none;background-repeat:repeat;border-spacing:0;padding-top:20px;padding-bottom:20px;padding-right:40px;padding-left:40px;font-family:sans-serif;font-size:15px;line-height:27px;border-collapse:collapse'>";
        $body .= $message;
        $body .= "</td></tr>";
        $body .= "<tr><td style='padding-top:0px;padding-bottom:20px;padding-right:20px;padding-left:20px;text-align:left;border-collapse:collapse'></td></tr>";
        $body .= "</tbody></table>";
        $body .= "</td></tr></tbody>";
        $body .= "</table>";
        
        return self::send($to, $from, $fromName, $subject, $body, true);
    }
    
    /**
     * Sends the Email
     * @param string  $to
     * @param string  $from
     * @param string  $fromName
     * @param string  $subject
     * @param string  $body
     * @param boolean $sendHtml   Optional.
     * @param string  $attachment Optional.
     * @return boolean
     */
    public static function send(
        string $to,
        string $from,
        string $fromName,
        string $subject,
        string $body,
        bool   $sendHtml = false,
        string $attachment = ""
    ): bool {
        self::load();

        if (self::$smtp->sendDisabled) {
            return false;
        }
        $mail = new PHPMailer();
        
        $mail->isSMTP();
        $mail->isHTML($sendHtml);
        $mail->clearAllRecipients();
        $mail->clearReplyTos();

        $mail->Timeout     = 10;
        $mail->Host        = self::$smtp->host;
        $mail->Port        = self::$smtp->port;
        $mail->SMTPSecure  = self::$smtp->secure;
        $mail->SMTPAuth    = true;
        $mail->SMTPAutoTLS = false;
        
        if (self::$smtp->useOAUTH) {
            $mail->SMTPAuth = true;
            $mail->AuthType = "XOAUTH2";

            $provider = new Google([
                "clientId"     => self::$google->client,
                "clientSecret" => self::$google->secret,
            ]);
            $mail->setOAuth(new OAuth([
                "provider"     => $provider,
                "clientId"     => self::$google->client,
                "clientSecret" => self::$google->secret,
                "refreshToken" => self::$smtp->refreshToken,
                "userName"     => self::$smtp->email,
            ]));
        } else {
            $mail->Username = self::$smtp->email;
            $mail->Password = self::$smtp->password;
        }
        
        $mail->CharSet  = "UTF-8";
        $mail->From     = self::$smtp->email;
        $mail->FromName = $fromName ?: self::$name;
        $mail->Subject  = $subject;
        $mail->Body     = $body;
        
        $mail->addAddress($to);
        if (!empty($from)) {
            $mail->addReplyTo($from, $fromName ?: self::$name);
        }
        if (!empty($attachment)) {
            $mail->AddAttachment($attachment);
        }
        if (self::$smtp->showErrors) {
            $mail->SMTPDebug = 3;
        }
        
        $result = $mail->send();
        if (self::$smtp->showErrors && !$result) {
            echo "Message could not be sent.";
            echo "Mailer Error: " . $mail->ErrorInfo;
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
            "clientId"     => self::$google->client,
            "clientSecret" => self::$google->secret,
            "redirectUri"  => self::$url . $redirectUri,
            "accessType"   => "offline",
        ]);
        return $provider->getAuthorizationUrl($options);
    }

    /**
     * Returns the Google Refresh Token
     * @param string $code
     * @return string
     */
    public static function getAuthToken(string $code): string {
        $provider = new Google([
            "clientId"     => self::$google->client,
            "clientSecret" => self::$google->secret,
            "accessType"   => "offline",
        ]);
        $token = $provider->getAccessToken("authorization_code", [ "code" => $code ]);
        return $token->getRefreshToken();
    }



    /**
     * Checks if the Recaptcha is Valid
     * @param Request $request
     * @return boolean
     */
    public static function isCpatchaValid(Request $request) {
        $secretKey = urlencode(Config::get("recaptchaKey"));
        $captcha   = urlencode($request->get("g-recaptcha-response"));
        $url       = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha";
        $response  = JSON::readUrl($url, true);

        if (empty($response["success"])) {
            return false;
        }
        return true;
    }
}
