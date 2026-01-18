<?php
namespace Framework\Email;

use Framework\Discovery\DiscoveryBuilder;
use Framework\Builder\Builder;
use Framework\Intl\NLSConfig;
use Framework\System\Language;
use Framework\Utils\Dictionary;

/**
 * The Email Builder
 */
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
            $data = NLSConfig::loadEmails($language);
            if ($data->isNotEmpty()) {
                break;
            }
        }

        $codes = [];
        foreach ($data as $emailCode => $email) {
            $codes[] = $emailCode;
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
