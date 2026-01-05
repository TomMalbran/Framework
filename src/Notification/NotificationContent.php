<?php
namespace Framework\Notification;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryMigration;
use Framework\Notification\Schema\NotificationContentSchema;
use Framework\Notification\Schema\NotificationContentEntity;
use Framework\Notification\Schema\NotificationContentQuery;
use Framework\Provider\Mustache;
use Framework\System\Language;
use Framework\System\NotificationCode;
use Framework\Utils\Arrays;

/**
 * The Notification Contents
 */
class NotificationContent extends NotificationContentSchema implements DiscoveryMigration {

    /**
     * Returns an Notification Content for the Notification Sender
     * @param NotificationCode $notificationCode
     * @param string           $language         Optional.
     * @return NotificationContentEntity
     */
    public static function get(NotificationCode $notificationCode, string $language = "root"): NotificationContentEntity {
        $langCode = Language::getCode($language);

        $query = new NotificationContentQuery();
        $query->notificationCode->equal($notificationCode->name);
        $query->language->equal($langCode);
        return self::getEntity($query);
    }

    /**
     * Renders the Notification Content message with Mustache
     * @param string              $message
     * @param array<string,mixed> $data    Optional.
     * @return string
     */
    public static function render(string $message, array $data = []): string {
        return Mustache::render($message, $data);
    }



    /**
     * Migrates the Notification Contents data
     * @return boolean
     */
    public static function migrateData(): bool {
        self::truncateData();

        $languages = Language::getAll();
        $position  = 0;
        $didUpdate = false;

        foreach ($languages as $language => $languageName) {
            $notifications = Discovery::loadNotifications($language);
            if (!Arrays::isEmpty($notifications)) {
                $position  = self::migrateLanguage($notifications, $language, $languageName, $position);
                $didUpdate = true;
            }
        }

        if (!$didUpdate) {
            print("- No notifications updated\n");
            return false;
        }
        return true;
    }

    /**
     * Migrates the Notification Templates for the given Language
     * @param array<string,mixed> $notifications
     * @param string              $language
     * @param string              $languageName
     * @param integer             $position
     * @return integer
     */
    private static function migrateLanguage(array $notifications, string $language, string $languageName, int $position): int {
        $total = 0;

        foreach ($notifications as $notificationCode => $notificationData) {
            $data      = Arrays::toStringsMap($notificationData);
            $position += 1;
            $total    += 1;

            self::createEntity(
                notificationCode: $notificationCode,
                language:         $language,
                languageName:     $languageName,
                description:      $data["description"],
                title:            $data["title"],
                body:             $data["body"],
                position:         $position,
                skipOrder:        true,
            );
        }

        if ($total > 0) {
            print("- Updated $total notifications for language $languageName\n");
        }
        return $position;
    }
}
