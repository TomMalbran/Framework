<?php
namespace Framework\Notification;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Builder\Builder;
use Framework\System\Language;
use Framework\Utils\Arrays;

/**
 * The Notification Builder
 */
class NotificationBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return integer
     */
    #[\Override]
    public static function generateCode(): int {
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

        // Builds the code
        return Builder::generateCode("NotificationCode", [
            "codes" => $codes,
            "total" => count($codes),
        ]);
    }

    /**
     * Destroys the Code
     * @return integer
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }
}
