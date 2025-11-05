<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;
use Framework\System\Language;
use Framework\System\Package;

/**
 * The Email Code
 */
class EmailCode {

    /**
     * Returns the Code variables
     * @return array<string,mixed>
     */
    public static function getCode(): array {
        $languages = Language::getAll();
        $data      = [];

        foreach ($languages as $language => $languageName) {
            /** @var array<string,string>[] */
            $data = Discovery::loadJSON(Package::EmailsDir, $language);

            if (!Arrays::isEmpty($data)) {
                break;
            }
        }

        $emailCodes = [];
        $maxLength  = 0;
        foreach ($data as $emailCode => $email) {
            $emailCodes[] = [
                "name"  => Strings::upperCaseFirst($emailCode),
                "value" => $emailCode,
            ];
            $maxLength = max($maxLength, Strings::length($emailCode));
        }

        foreach ($emailCodes as $index => $emailCode) {
            $emailCodes[$index]["name"] = Strings::padRight($emailCode["name"], $maxLength);
        }

        return [
            "hasEmailCodes" => count($emailCodes) > 0,
            "emailCodes"    => $emailCodes,
        ];
    }
}
