<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Discovery\Discovery;
use Framework\Email\EmailWhiteList;
use Framework\Email\EmailProvider;
use Framework\Email\EmailResult;
use Framework\Email\Schema\EmailContentEntity;
use Framework\Provider\Mustache;
use Framework\Provider\SMTP;
use Framework\Provider\Mandrill;
use Framework\Provider\Mailjet;
use Framework\Provider\Mailgun;
use Framework\Provider\SendGrid;
use Framework\File\FilePath;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Utils;

/**
 * The Email Provider
 */
class Email {

    /**
     * Sends an Email
     * @param string  $toEmail
     * @param string  $subject
     * @param string  $message
     * @param boolean $sendAlways      Optional.
     * @param boolean $sendTest        Optional.
     * @param boolean $withoutTemplate Optional.
     * @return EmailResult
     */
    public static function send(
        string $toEmail,
        string $subject,
        string $message,
        bool $sendAlways = false,
        bool $sendTest = false,
        bool $withoutTemplate = false,
    ): EmailResult {
        // Return some possible errors
        if (!$sendTest && !Config::isEmailActive()) {
            return EmailResult::InactiveSend;
        }
        if (!$sendTest && !$sendAlways && Config::isEmailUseWhiteList() && !EmailWhiteList::emailExists($toEmail)) {
            return EmailResult::WhiteListFilter;
        }
        if (!Utils::isValidEmail($toEmail)) {
            return EmailResult::InvalidEmail;
        }

        // Create the template
        if ($withoutTemplate) {
            $body = $message;
        } else {
            $template = Discovery::loadEmailTemplate();
            $body     = Mustache::render($template, [
                "url"        => Config::getEmailUrl(),
                "name"       => Config::getName(),
                "files"      => FilePath::getInternalUrl(),
                "logo"       => Config::getEmailLogo(),
                "logoHeight" => Config::getEmailLogoHeight(),
                "siteName"   => Config::getName(),
                "message"    => $message,
            ]);
        }

        // Configure the variables
        $provider  = EmailProvider::fromValue(Config::getEmailProvider());
        $fromName  = Config::getName();
        $fromEmail = Config::getEmailEmail();
        $replyTo   = Config::getEmailReplyTo();

        // Try to send the email
        $wasSent = match ($provider) {
            EmailProvider::Mandrill => Mandrill::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
            EmailProvider::Mailjet  => Mailjet::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
            EmailProvider::Mailgun  => Mailgun::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
            EmailProvider::SendGrid => SendGrid::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
            default                 => SMTP::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
        };
        return $wasSent ? EmailResult::Sent : EmailResult::ProviderError;
    }

    /**
     * Sends the given Email Content
     * @param EmailContentEntity $content
     * @param string[]|string    $sendTo
     * @param string|null        $message    Optional.
     * @param string|null        $subject    Optional.
     * @param boolean            $sendAlways Optional.
     * @return EmailResult
     */
    public static function sendContent(
        EmailContentEntity $content,
        array|string $sendTo,
        ?string $message = null,
        ?string $subject = null,
        bool $sendAlways = false,
    ): EmailResult {
        $sendTo    = Arrays::toStrings($sendTo);
        $subject ??= $content->subject;
        $message ??= $content->message;
        $result    = EmailResult::NoEmails;

        foreach ($sendTo as $email) {
            $result = self::send($email, $subject, $message, $sendAlways);
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
        $recaptchaSecret = Config::getEmailRecaptchaSecret();
        if (!$request->has("g-recaptcha-response") || $recaptchaSecret === "") {
            return false;
        }
        $secretKey = urlencode($recaptchaSecret);
        $captcha   = urlencode($request->getString("g-recaptcha-response"));
        $url       = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha";
        $response  = JSON::readUrl($url);

        if (!isset($response["success"])) {
            return false;
        }
        if ($withScore && $response["score"] <= 0.5) {
            return false;
        }
        return true;
    }
}
