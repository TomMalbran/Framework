<?php
namespace Framework\Email;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Builder\Builder;
use Framework\System\Language;
use Framework\Utils\Arrays;

/**
 * The Email Builder
 */
class EmailBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return integer
     */
    #[\Override]
    public static function generateCode(): int {
        $languages = Language::getAll();
        $data      = [];

        foreach ($languages as $language => $languageName) {
            $data = Discovery::loadEmails($language);
            if (!Arrays::isEmpty($data)) {
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
     * @return integer
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }
}
