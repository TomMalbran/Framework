<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\Request;
use Framework\Auth\Access;
use Framework\Auth\Credential;
use Framework\Config\Config;
use Framework\Email\Queue;
use Framework\Provider\Mustache;
use Framework\File\Path;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

/**
 * The Mailer Provider
 */
class Mailer {
    
    private static $loaded   = false;
    private static $template = null;
    private static $url      = "";
    private static $name     = "";
    private static $smtp     = null;
    private static $google   = null;
    private static $emails   = [];
    
    
    /**
     * Loads the Mailer Config
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
            self::$emails   = Config::getArray("smtpActiveEmails");
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

        if (self::$smtp->sendDisabled) {
            return false;
        }
        if (!empty(self::$emails) && !Arrays::contains(self::$emails, $to)) {
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
        
        if (self::$smtp->useOauth) {
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
        $body = Mustache::render(self::$template, [
            "url"      => self::$url,
            "name"     => self::$name,
            "files"    => Path::getUrl("email"),
            "logo"     => self::$smtp->logo ?: "",
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
    public static function sendToEmails(Model $template, $sendTo, string $message = null, string $subject = null): bool {
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
     * Sends the Message to all the Admins or the ones in the Template
     * @param Model  $template
     * @param string $message  Optional.
     * @param string $subject  Optional.
     * @return boolean
     */
    public static function sendToAdmins(Model $template, string $message = null, string $subject = null): bool {
        $sendTo = [];
        if (!empty($template->sendTo)) {
            $sendTo = $template->sendToParts;
        } else {
            $sendTo = Credential::getEmailsForLevel(Access::getAdmins());
        }
        return self::sendToEmails($template, $sendTo, $message, $subject);
    }

    /**
     * Sends the Unsent Emails from the Queue
     * @return void
     */
    public static function sendQueue() {
        $emails = Queue::getAllUnsent();
        foreach ($emails as $email) {
            $success = false;
            foreach ($email["sendToParts"] as $sendTo) {
                if (!empty($sendTo)) {
                    $success = self::sendHTML($sendTo, $email["sendAs"], $email["sendName"], $email["subject"], $email["message"]);
                }
            }
            Queue::markAsSent($email["emailID"], $success);
        }
    }

    /**
     * Sends the Backup to the Backup account
     * @param string $sendTo
     * @param string $attachment
     * @return boolean
     */
    public function sendBackup(string $sendTo, string $attachment): bool {
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
     * @return boolean
     */
    public static function isCaptchaValid(Request $request) {
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
