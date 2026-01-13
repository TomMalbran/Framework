<?php
namespace Framework\Notification;

use Framework\Discovery\DiscoveryBuilder;
use Framework\Builder\Builder;
use Framework\Intl\NLSConfig;
use Framework\System\Language;
use Framework\Utils\Dictionary;

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
        $data      = new Dictionary();

        foreach ($languages as $language => $languageName) {
            $data = NLSConfig::loadNotifications($language);
            if (!$data->isEmpty()) {
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
