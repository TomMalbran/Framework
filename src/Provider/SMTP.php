<?php
namespace Framework\Provider;

use Framework\System\Config;

use PHPMailer\PHPMailer\PHPMailer;

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

        $email->Username    = Config::getSmtpUsername();
        $email->Password    = Config::getSmtpPassword();

        $email->CharSet     = "UTF-8";
        $email->From        = $fromEmail;
        $email->FromName    = $fromName;
        $email->Subject     = $subject;
        $email->Body        = $body;

        $email->addAddress($toEmail);
        if (!empty($replyTo)) {
            $email->addReplyTo($replyTo, $fromName);
        }
        if (!empty($attachment)) {
            $email->addAttachment($attachment);
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
}
