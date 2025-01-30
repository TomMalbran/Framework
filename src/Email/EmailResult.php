<?php
namespace Framework\Email;

/**
 * The Email Results used by the System
 */
enum EmailResult {

    case Sent;
    case NotProcessed;
    case NoEmails;
    case InactiveSend;
    case WhiteListFilter;
    case InvalidEmail;
    case ProviderError;
    case UnknownError;

}
