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
 */
#[Priority(Priority::High)]
class EmailBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
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

        // Builds the code
        return Builder::generateCode("EmailCode", [
            "codes" => $codes,
            "total" => count($codes),
        ]);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }
}
