<?php
namespace Framework\Notification;

use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Builder\Builder;
use Framework\Intl\IntlConfig;
use Framework\System\Language;
use Framework\Utils\Dictionary;

/**
 * The Notification Builder
 * @phpstan-type NotificationCodesResult array{
 *   codes: list<string>,
 *   total: int,
 * }
 */
class NotificationBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $data = self::collectNotifications();
        return Builder::generateCode("NotificationCode", $data);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }



    /**
     * Collects the Notifications from the Notification files
     * @return NotificationCodesResult
     */
    public static function collectNotifications(): array {
        $languages = Language::getAll();
        $data      = new Dictionary();

        foreach ($languages as $language => $languageName) {
            $data = IntlConfig::loadNotifications($language);
            if ($data->isNotEmpty()) {
                break;
            }
        }

        $codes = [];
        foreach ($data as $notificationCode => $notification) {
            $codes[] = $notificationCode;
        }

        return [
            "codes" => $codes,
            "total" => count($codes),
        ];
    }
}
