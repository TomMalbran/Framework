<?php
namespace Framework\Email;

use Framework\Framework;
use Framework\Request;
use Framework\Config\Config;
use Framework\Email\EmailWhiteList;
use Framework\Email\EmailResult;
use Framework\Provider\Mustache;
use Framework\Provider\SMTP;
use Framework\Provider\Mandrill;
use Framework\Provider\Mailjet;
use Framework\Provider\SendGrid;
use Framework\File\Path;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Utils;

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
    private static object  $config;


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
        self::$url      = Config::getString("url");
        self::$config   = Config::getObject("email");
        return true;
    }



    /**
     * Sends an Email
     * @param string $toEmail
     * @param string $subject
     * @param string $message
     * @return string
     */
    public static function send(string $toEmail, string $subject, string $message): string {
        self::load();
        if (!self::$config->isActive) {
            return EmailResult::InactiveSend;
        }
        if (!empty(self::$config->useWhiteList) && !EmailWhiteList::emailExists($toEmail)) {
            return EmailResult::WhiteListFilter;
        }
        if (!Utils::isValidEmail($toEmail)) {
            return EmailResult::InvalidEmail;
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
        $fromEmail = self::$config->email;
        $fromName  = self::$config->name;

        $wasSent   = match ($provider) {
            self::Mandrill => Mandrill::sendEmail($toEmail, $fromEmail, $fromName, $subject, $body),
            self::Mailjet  => Mailjet::sendEmail($toEmail, $fromEmail, $fromName, $subject, $body),
            self::SendGrid => SendGrid::sendEmail($toEmail, $fromEmail, $fromName, $subject, $body),
            default        => SMTP::sendEmail($toEmail, $fromEmail, $fromName, $subject, $body),
        };
        return $wasSent ? EmailResult::Sent : EmailResult::ProviderError;
    }

    /**
     * Sends the given Template Email
     * @param Model           $template
     * @param string[]|string $sendTo
     * @param string|null     $message  Optional.
     * @param string|null     $subject  Optional.
     * @return string
     */
    public static function sendTemplate(Model $template, array|string $sendTo, ?string $message = null, ?string $subject = null): string {
        $sendTo  = Arrays::toArray($sendTo);
        $subject = $subject ?: $template->subject;
        $message = $message ?: $template->message;
        $result  = EmailResult::NoEmails;

        foreach ($sendTo as $email) {
            $result = self::send($email, $subject, $message);
        }
        return $result;
    }



    /**
     * Checks if the Recaptcha is Valid
     * @param Request $request
     * @param boolean $withScore Optional.
     * @return boolean
     */
    public static function isCaptchaValid(Request $request, bool $withScore = false): bool {
        $recaptchaSecret = Config::getString("recaptchaSecret");
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
