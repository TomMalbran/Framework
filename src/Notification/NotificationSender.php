<?php
namespace Framework\Notification;

/**
 * The Notification Sender
 *
 * A Provider that can push a notification. The build finds every one of them
 * and writes the NotificationProvider enum from their names, so a new provider
 * is a new class and nothing else: the class name is what
 * NOTIFICATION_PROVIDER takes, unless the class gives itself another with a
 * Name constant.
 *
 *     class Firebase implements NotificationSender {
 *         public const Name = "FCM";
 *     }
 *
 * Both sends answer with the ID the Provider gave the notification, or null
 * when it would not take it.
 */
interface NotificationSender {

    /**
     * Sends the Notification to every device there is
     * @param string $title
     * @param string $message
     * @param string $url
     * @param string $icon
     * @param string $dataType
     * @param int    $dataID
     * @return string
     */
    public static function sendToAll(
        string $title,
        string $message,
        string $url,
        string $icon,
        string $dataType,
        int $dataID,
    ): string;

    /**
     * Sends the Notification to the given devices
     * @param string       $title
     * @param string       $message
     * @param string       $url
     * @param string       $icon
     * @param string       $dataType
     * @param int          $dataID
     * @param list<string> $playerIDs
     * @return string
     */
    public static function sendToSome(
        string $title,
        string $message,
        string $url,
        string $icon,
        string $dataType,
        int $dataID,
        array $playerIDs,
    ): string;
}
