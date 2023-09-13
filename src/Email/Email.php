<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\Request;
use Framework\Config\Config;
use Framework\Email\WhiteList;
use Framework\Provider\Mustache;
use Framework\Provider\Mandrill;
use Framework\Provider\Mailjet;
use Framework\Provider\SendGrid;
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

    const SMTP     = "SMTP";
    const Mandrill = "Mandrill";
    const Mailjet  = "Mailjet";
    const SendGrid = "SendGrid";


    private static bool    $loaded   = false;
    private static ?string $template = null;
    private static string  $url      = "";
    private static mixed   $config   = null;

    private static mixed   $smtp     = null;
    private static mixed   $google   = null;


    /**
     * Loads the Email Config
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded   = true;
        self::$template = Framework::loadFile(Framework::DataDir, "email.html");
        self::$url      = Config::get("url");
        self::$config   = Config::get("email");
        return true;
    }

    /**
     * Returns the PHPMailer
     * @return PHPMailer
     */
    private static function smtp(): PHPMailer {
        if (!empty(self::$smtp)) {
            return new PHPMailer();
        }
        self::$smtp   = Config::get("smtp");
        self::$google = Config::get("google");
        return new PHPMailer();
    }



    /**
     * Returns true if is possible to send the email
     * @param string $toEmail
     * @return boolean
     */
    public static function canSend(string $toEmail): bool {
        if (!self::$config->isActive) {
            return false;
        }
        if (!empty(self::$config->useWhiteList) && !WhiteList::emailExists($toEmail)) {
            return false;
        }
        return true;
    }

    /**
     * Sends an Email
     * @param string $toEmail
     * @param string $fromEmail
     * @param string $fromName
     * @param string $subject
     * @param string $message
     * @return boolean
     */
    public static function send(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $message
    ): bool {
        self::load();
        if (!self::canSend($toEmail)) {
            return false;
        }

        $logo = "";
        if (!empty(self::$config->logo)) {
            $logo = self::$config->logo;
        }
        $body = Mustache::render(self::$template, [
            "url"      => self::$url,
            "name"     => self::$config->name,
            "files"    => Path::getUrl("framework"),
            "logo"     => $logo,
            "siteName" => self::$config->name,
            "message"  => $message,
        ]);

        $provider  = self::$config->provider ?: "smtp";
        $fromEmail = $fromEmail ?: self::$config->email;
        $fromName  = $fromName  ?: self::$config->name;

        switch ($provider) {
        case self::Mandrill:
            return Mandrill::sendEmail($toEmail, $fromEmail, $fromName, $subject, $body);
        case self::Mailjet:
            return Mailjet::sendEmail($toEmail, $fromEmail, $fromName, $subject, $body);
        case self::SendGrid:
            return SendGrid::sendEmail($toEmail, $fromEmail, $fromName, $subject, $body);
        default:
            return self::sendSMTP($toEmail, $fromEmail, $fromName, $subject, $body);
        }
        return false;
    }

    /**
     * Sends the given Template Email
     * @param Model           $template
     * @param string[]|string $sendTo
     * @param string|null     $message  Optional.
     * @param string|null     $subject  Optional.
     * @return boolean
     */
    public static function sendTemplate(Model $template, array|string $sendTo, ?string $message = null, ?string $subject = null): bool {
        $sendTo  = Arrays::toArray($sendTo);
        $subject = $subject ?: $template->subject;
        $message = $message ?: $template->message;
        $success = false;

        foreach ($sendTo as $email) {
            $success = self::send($email, $template->sendAs, $template->sendName, $subject, $message);
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

        return self::send($sendTo, "", "", $subject, $message, false, $attachment);
    }



    /**
     * Sends the Email with SMTP
     * @param string $toEmail
     * @param string $fromEmail
     * @param string $fromName
     * @param string $subject
     * @param string $body
     * @param string $attachment Optional.
     * @return boolean
     */
    public static function sendSMTP(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $body,
        string $attachment = ""
    ): bool {
        $email = self::smtp();
        $email->isSMTP();
        $email->isHTML(true);
        $email->clearAllRecipients();
        $email->clearReplyTos();

        $email->Timeout     = 10;
        $email->Host        = self::$smtp->host;
        $email->Port        = self::$smtp->port;
        $email->SMTPSecure  = self::$smtp->secure;
        $email->SMTPAuth    = true;
        $email->SMTPAutoTLS = false;

        $username = !empty(self::$smtp->username) ? self::$smtp->username : self::$config->email;
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
        $email->From     = self::$config->email;
        $email->FromName = $fromName ?: self::$config->name;
        $email->Subject  = $subject;
        $email->Body     = $body;

        $email->addAddress($toEmail);
        if (!empty($fromEmail)) {
            $email->addReplyTo($fromEmail, $fromName ?: self::$config->name);
        } elseif (!empty(self::$smtp->replyTo)) {
            $email->addReplyTo(self::$smtp->replyTo, self::$config->name);
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
    public static function isCaptchaValid(Request $request, bool $withScore = false): bool {
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
