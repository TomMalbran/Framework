<?php
namespace Framework\Email;

/**
 * The Email Results used by the System
 */
enum EmailResult {

    case Sent;
    case NotProcessed;
    case InactiveSend;
    case NoEmails;
    case WhiteListFilter;
    case InvalidEmail;
    case ProviderError;
}
