<?php
namespace Framework\Notification;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryCode;
use Framework\System\Language;
use Framework\Utils\Arrays;

/**
 * The Notification Builder
 */
class NotificationBuilder implements DiscoveryCode {

    /**
     * Returns the File Name to Generate
     * @return string
     */
    public static function getFileName(): string {
        return "NotificationCode";
    }

    /**
     * Returns the File Code to Generate
     * @return array<string,mixed>
     */
    public static function getFileCode(): array {
        $languages = Language::getAll();
        $data      = [];

        foreach ($languages as $language => $languageName) {
            $data = Discovery::loadNotifications($language);
            if (!Arrays::isEmpty($data)) {
                break;
            }
        }

        $codes = [];
        foreach ($data as $notificationCode => $notification) {
            $codes[] = $notificationCode;
        }

        return [
            "codes" => $codes,
        ];
    }
}
