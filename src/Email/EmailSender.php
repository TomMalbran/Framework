<?php
namespace Framework\Email;

/**
 * The Email Sender
 *
 * A Provider that can deliver a message. The build finds every one of them and
 * writes the EmailProvider enum from their names, so a new provider is a new
 * class and nothing else: the class name is what EMAIL_PROVIDER takes, unless
 * the class gives itself another with a Name constant.
 *
 *     class AmazonSes implements EmailSender {
 *         public const Name = "SES";
 *     }
 */
interface EmailSender {

    /**
     * Sends the Email
     * @param string $toEmail
     * @param string $fromEmail
     * @param string $fromName
     * @param string $replyTo
     * @param string $subject
     * @param string $body
     * @return bool
     */
    public static function sendEmail(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $replyTo,
        string $subject,
        string $body,
    ): bool;
}
