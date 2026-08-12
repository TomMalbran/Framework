<?php
namespace Tests\Email\Fixture;

use Framework\Email\EmailSender;

/**
 * A Sender that keeps the emails rather than delivering them
 *
 * The build writes the Providers from the classes in the source, and this is
 * not one of them, so it is handed to Email::setSender() rather than named in
 * the config. A send then runs the length of the class, template and all, and
 * stops here with the message to look at.
 *
 * @phpstan-type SentEmail array{
 *   toEmail:   string,
 *   fromEmail: string,
 *   fromName:  string,
 *   replyTo:   string,
 *   subject:   string,
 *   body:      string,
 * }
 */
class TestEmailSender implements EmailSender {

    /** @var list<SentEmail> */
    private static array $emails = [];

    private static bool $sends = true;


    /**
     * Sets whether the sends go through or are refused, which is how the
     * answer of a provider that would not take the email is tried out
     * @param bool $sends
     * @return void
     */
    public static function setSends(bool $sends): void {
        self::$sends = $sends;
    }

    /**
     * Sends the Email, which is to say it keeps it
     * @param string $toEmail
     * @param string $fromEmail
     * @param string $fromName
     * @param string $replyTo
     * @param string $subject
     * @param string $body
     * @return bool
     */
    #[\Override]
    public static function sendEmail(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $replyTo,
        string $subject,
        string $body,
    ): bool {
        self::$emails[] = [
            "toEmail"   => $toEmail,
            "fromEmail" => $fromEmail,
            "fromName"  => $fromName,
            "replyTo"   => $replyTo,
            "subject"   => $subject,
            "body"      => $body,
        ];

        // A refused email was handed over just the same, so it is kept
        return self::$sends;
    }

    /**
     * Returns every Email that was sent
     * @return list<SentEmail>
     */
    public static function getAll(): array {
        return self::$emails;
    }

    /**
     * Returns the last Email that was sent, or an empty one
     * @return SentEmail
     */
    public static function getLast(): array {
        $result = end(self::$emails);
        if ($result === false) {
            return [
                "toEmail"   => "",
                "fromEmail" => "",
                "fromName"  => "",
                "replyTo"   => "",
                "subject"   => "",
                "body"      => "",
            ];
        }
        return $result;
    }

    /**
     * Returns the amount of Emails that were sent
     * @return int
     */
    public static function getCount(): int {
        return count(self::$emails);
    }

    /**
     * Forgets every Email that was sent
     * @return void
     */
    public static function reset(): void {
        self::$emails = [];
        self::$sends  = true;
    }
}
