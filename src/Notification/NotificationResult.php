<?php
namespace Framework\Notification;

/**
 * The Notification Results used by the System
 */
enum NotificationResult {

    case Sent;
    case NotProcessed;
    case InactiveSend;
    case NoDevices;
    case ProviderError;
}
