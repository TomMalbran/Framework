<?php
namespace Framework\Builder;

use Framework\Framework;
use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The Language Code
 */
class LanguageCode {

    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        $path      = Framework::getPath(Framework::StringsDir);
        $files     = File::getFilesInDir($path);
        $rootCode  = "es";
        $rootFound = false;
        $languages = [];

        // Load all the languages
        foreach ($files as $file) {
            $code = Strings::stripEnd($file, ".json");
            $data = Framework::loadJSON(Framework::StringsDir, $code);
            if (empty($data["NAME"])) {
                continue;
            }

            $languages[] = [
                "code" => $code,
                "name" => $data["NAME"],
            ];
            if ($code === $rootCode) {
                $rootFound = true;
            }
        }

        // If no languages are found, add a default one
        if (empty($languages)) {
            $languages[] = [
                "code" => "es",
                "name" => "Español",
            ];
        }

        // If the root language is not found, set the first one as root
        if (!$rootFound) {
            $rootCode = $languages[0]["code"];
        }


        return [
            "languages" => $languages,
            "rootCode"  => $rootCode,
        ];
    }
}
