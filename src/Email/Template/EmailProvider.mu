<?php
namespace {{namespace}};

use Framework\Email\EmailSender;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Email Providers
 */
enum EmailProvider implements Enum, JsonSerializable {
    use IsEnum;

    case None;

{{#providers}}
    case {{name}};
{{/providers}}



    /**
     * Returns the Sender of the Provider, or null if there is none
     * @return class-string<EmailSender>|null
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
