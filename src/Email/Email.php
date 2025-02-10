<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Discovery\Discovery;
use Framework\Email\EmailWhiteList;
use Framework\Email\EmailProvider;
use Framework\Email\EmailResult;
use Framework\Provider\Mustache;
use Framework\Provider\SMTP;
use Framework\Provider\Mandrill;
use Framework\Provider\Mailjet;
use Framework\Provider\SendGrid;
use Framework\File\FilePath;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Utils;
use Framework\Schema\EmailTemplateEntity;

/**
 * The Email Provider
 */
class Email {

    /**
     * Sends an Email
     * @param string  $toEmail
     * @param string  $subject
     * @param string  $message
     * @param boolean $sendAlways
     * @return EmailResult
     */
    public static function send(string $toEmail, string $subject, string $message, bool $sendAlways): EmailResult {
        // Return some possible errors
        if (!Config::isEmailActive()) {
            return EmailResult::InactiveSend;
        }
        if (!$sendAlways && Config::isEmailUseWhiteList() && !EmailWhiteList::emailExists($toEmail)) {
            return EmailResult::WhiteListFilter;
        }
        if (!Utils::isValidEmail($toEmail)) {
            return EmailResult::InvalidEmail;
        }

        // Create the template
        $template = Discovery::loadEmailTemplate();
        $body     = Mustache::render($template, [
            "url"      => Config::getUrl(),
            "name"     => Config::getName(),
            "files"    => FilePath::getInternalUrl(),
            "logo"     => Config::getEmailLogo(),
            "siteName" => Config::getName(),
            "message"  => $message,
        ]);

        // Configure the variables
        $provider  = EmailProvider::from(Config::getEmailProvider());
        $fromName  = Config::getName();
        $fromEmail = Config::getEmailEmail();
        $replyTo   = Config::getEmailReplyTo();

        // Try to send the email
        $wasSent = match ($provider) {
            EmailProvider::Mandrill => Mandrill::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
            EmailProvider::Mailjet  => Mailjet::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
            EmailProvider::SendGrid => SendGrid::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
            default                 => SMTP::sendEmail($toEmail, $fromEmail, $fromName, $replyTo, $subject, $body),
        };
        return $wasSent ? EmailResult::Sent : EmailResult::ProviderError;
    }

    /**
     * Sends the given Template Email
     * @param EmailTemplateEntity $template
     * @param string[]|string     $sendTo
     * @param string|null         $message    Optional.
     * @param string|null         $subject    Optional.
     * @param boolean             $sendAlways Optional.
     * @return EmailResult
     */
    public static function sendTemplate(
        EmailTemplateEntity $template,
        array|string $sendTo,
        ?string $message = null,
        ?string $subject = null,
        bool $sendAlways = false,
    ): EmailResult {
        $sendTo  = Arrays::toArray($sendTo);
        $subject = $subject ?: $template->subject;
        $message = $message ?: $template->message;
        $result  = EmailResult::NoEmails;

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
        if (!$request->has("g-recaptcha-response") || empty($recaptchaSecret)) {
            return false;
        }
        $secretKey = urlencode($recaptchaSecret);
        $captcha   = urlencode($request->get("g-recaptcha-response"));
        $url       = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captcha";
        $response  = JSON::readUrl($url);

        if (empty($response["success"])) {
            return false;
        }
        if ($withScore && $response["score"] <= 0.5) {
            return false;
        }
        return true;
    }
}
