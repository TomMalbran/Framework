<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\Request;
use Framework\Auth\Access;
use Framework\Auth\Credential;
use Framework\Config\Config;
use Framework\Email\WhiteList;
use Framework\Provider\Mustache;
use Framework\File\Path;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

/**
 * The Email Provider
 */
class Email {

    private static $loaded   = false;
    private static $template = null;
    private static $url      = "";
    private static $name     = "";
    private static $smtp     = null;
    private static $google   = null;


    /**
     * Loads the Email Config
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            self::$loaded   = true;
            self::$template = Framework::loadFile(Framework::DataDir, "email.html");
            self::$url      = Config::get("url");
            self::$name     = Config::get("name");
            self::$smtp     = Config::get("smtp");
            self::$google   = Config::get("google");
        }
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
        if (self::$smtp->sendDisabled || (self::$smtp->useWhiteList && !WhiteList::emailExists($to))) {
            return false;
        }

        $email = new PHPMailer();

        $email->isSMTP();
        $email->isHTML($sendHtml);
        $email->clearAllRecipients();
        $email->clearReplyTos();

        $email->Timeout     = 10;
        $email->Host        = self::$smtp->host;
        $email->Port        = self::$smtp->port;
        $email->SMTPSecure  = self::$smtp->secure;
        $email->SMTPAuth    = true;
        $email->SMTPAutoTLS = false;

        $username = !empty(self::$smtp->username) ? self::$smtp->username : self::$smtp->email;
        if (self::$smtp->useOauth) {
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
                "refreshToken" => self::$smtp->refreshToken,
                "userName"     => $username,
            ]));
        } else {
            $email->Username = $username;
            $email->Password = self::$smtp->password;
        }

        $email->CharSet  = "UTF-8";
        $email->From     = self::$smtp->email;
        $email->FromName = $fromName ?: self::$name;
        $email->Subject  = $subject;
        $email->Body     = $body;

        $email->addAddress($to);
        if (!empty($from)) {
            $email->addReplyTo($from, $fromName ?: self::$name);
        }
        if (!empty($attachment)) {
            $email->AddAttachment($attachment);
        }
        if (self::$smtp->showErrors) {
            $email->SMTPDebug = 3;
        }

        $result = $email->send();
        if (self::$smtp->showErrors && !$result) {
            echo "Message could not be sent.";
            echo "Email Error: " . $email->ErrorInfo;
        }
        return $result;
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
    public static function sendHtml(string $to, string $from, string $fromName, string $subject, string $message): bool {
        self::load();
        $logo = "";
        if (!empty(self::$smtp->logo)) {
            $logo = self::$smtp->logo;
        }
        $body = Mustache::render(self::$template, [
            "url"      => self::$url,
            "name"     => self::$name,
            "files"    => Path::getUrl("framework"),
            "logo"     => $logo,
            "siteName" => self::$name,
            "message"  => $message,
        ]);
        return self::send($to, $from, $fromName, $subject, $body, true);
    }



    /**
     * Sends the given Template Email
     * @param Model           $template
     * @param string|string[] $sendTo
     * @param string          $message  Optional.
     * @param string          $subject  Optional.
     * @return boolean
     */
    public static function sendTemplate(Model $template, $sendTo, string $message = null, string $subject = null): bool {
        $sendTo  = Arrays::toArray($sendTo);
        $subject = $subject ?: $template->subject;
        $message = $message ?: $template->message;
        $success = false;

        foreach ($sendTo as $email) {
            $success = self::sendHtml($email, $template->sendAs, $template->sendName, $subject, $message);
        }
        return $success;
    }

    /**
     * Sends the Backup to the Backup account
     * @param string $sendTo
     * @param string $attachment
     * @return boolean
     */
    public static function sendBackup(string $sendTo, string $attachment): bool {
        $subject = Config::get("name") . ": Database Backup";
        $message = "Backup de la base de datos al dia: " . date("d M Y, H:i:s");

        return self::send($sendTo, null, null, $subject, $message, false, $attachment);
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



    /**
     * Checks if the Recaptcha is Valid
     * @param Request $request
     * @param boolean $withScore Optional.
     * @return boolean
     */
    public static function isCaptchaValid(Request $request, bool $withScore = false) {
        $recaptchaSecret = Config::get("recaptchaSecret");
        if (!$request->has("g-recaptcha-response") || empty($recaptchaSecret)) {
            return false;
        }
        $secretKey = urlencode($recaptchaSecret);
        $captcha   = urlencode($request->get("g-recaptcha-response"));
        $url       = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha";
        $response  = JSON::readUrl($url, true);

        if (empty($response["success"])) {
            return false;
        }
        if ($withScore && $response["score"] <= 0.5) {
            return false;
        }
        return true;
    }
}
