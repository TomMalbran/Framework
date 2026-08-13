<?php
namespace {{namespace}};

use Framework\Notification\NotificationSender;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Notification Providers
 */
enum NotificationProvider implements Enum, JsonSerializable {
    use IsEnum;

    case None;

{{#providers}}
    case {{name}};
{{/providers}}



    /**
     * Returns the Sender of the Provider, or null if there is none
     * @return class-string<NotificationSender>|null
     */
    public function getSender(): ?string {
        return match ($this) {
            self::{{none}} => null,
{{#providers}}
            self::{{constant}} => \{{class}}::class,
{{/providers}}
        };
    }
}
