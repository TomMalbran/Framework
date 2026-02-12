<?php
namespace Framework\Email;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Email Results used by the System
 */
enum EmailResult implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case Sent;
    case NotProcessed;
    case InactiveSend;
    case NoEmails;
    case WhiteListFilter;
    case InvalidEmail;
    case ProviderError;
}
