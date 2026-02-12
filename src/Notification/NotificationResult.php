<?php
namespace Framework\Notification;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Notification Results used by the System
 */
enum NotificationResult implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case Sent;
    case NotProcessed;
    case InactiveSend;
    case NoDevices;
    case ProviderError;
}
