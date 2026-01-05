<?php
namespace Framework\Email;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryCode;
use Framework\System\Language;
use Framework\Utils\Arrays;

/**
 * The Email Builder
 */
class EmailBuilder implements DiscoveryCode {

    /**
     * Returns the File Name to Generate
     * @return string
     */
    public static function getFileName(): string {
        return "EmailCode";
    }

    /**
     * Returns the File Code to Generate
     * @return array<string,mixed>
     */
    public static function getFileCode(): array {
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

        return [
            "codes" => $codes,
            "total" => count($codes),
        ];
    }
}
