<?php
namespace Framework\Email;

use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Discovery\Attr\Priority;
use Framework\Builder\Builder;
use Framework\Intl\IntlConfig;
use Framework\System\Language;
use Framework\Utils\Dictionary;

/**
 * The Email Builder
 * @phpstan-type EmailCodesResult array{
 *   codes: list<string>,
 *   total: int,
 * }
 */
#[Priority(Priority::High)]
class EmailBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $data = self::collectEmails();
        return Builder::generateCode("EmailCode", $data);
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
     * Collects the Emails from the Emails files
     * @return EmailCodesResult
     */
    public static function collectEmails(): array {
        $languages = Language::getAll();
        $data      = new Dictionary();

        foreach ($languages as $language => $languageName) {
            $data = IntlConfig::loadEmails($language);
            if ($data->isNotEmpty()) {
                break;
            }
        }

        $codes = [];
        foreach ($data as $emailCode => $email) {
            $codes[] = $emailCode;
        }

        // If no codes are found, add a default one
        if (count($codes) === 0) {
            $codes[] = "Test";
        }

        return [
            "codes" => $codes,
            "total" => count($codes),
        ];
    }
}
