<?php
namespace Framework\Email;

/**
 * The Email Providers used by the System
 */
enum EmailProvider {

    case SMTP;
    case Mandrill;
    case Mailjet;
    case SendGrid;



    /**
     * Creates a Email Provider from a String
     * @param string $value
     * @return EmailProvider
     */
    public static function fromValue(string $value): EmailProvider {
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }
        return self::SMTP;
    }
}
