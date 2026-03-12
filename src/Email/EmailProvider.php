<?php
namespace Framework\Email;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Email Providers used by the System
 */
enum EmailProvider implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case SMTP;
    case Mandrill;
    case Mailjet;
    case Mailgun;
    case SendGrid;
}
