<?php
namespace Framework\Email;

/**
 * The Email Results used by the System
 */
class EmailResult {

    const Sent            = "Sent";
    const NotProcessed    = "NotProcessed";
    const NoEmails        = "NoEmails";
    const InactiveSend    = "InactiveSend";
    const WhiteListFilter = "WhiteListFilter";
    const InvalidEmail    = "InvalidEmail";
    const ProviderError   = "ProviderError";
    const UnknownError    = "UnknownError";

}
