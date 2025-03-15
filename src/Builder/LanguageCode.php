<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\File\File;
use Framework\System\Package;
use Framework\Utils\Strings;

/**
 * The Language Code
 */
class LanguageCode {

    /**
     * Returns the Code variables
     * @return array<string,mixed>
     */
    public static function getCode(): array {
        $path      = Discovery::getAppPath(Package::StringsDir);
        $files     = File::getFilesInDir($path);
        $rootCode  = "es";
        $rootFound = false;
        $languages = [];

        // Load all the languages
        foreach ($files as $file) {
            $code = Strings::stripEnd($file, ".json");
            $data = Discovery::loadJSON(Package::StringsDir, $code);
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

        // Sort the Root Language to the top
        usort($languages, function($a, $b) use ($rootCode) {
            if ($a["code"] === $rootCode) {
                return -1;
            }
            if ($b["code"] === $rootCode) {
                return 1;
            }
            return strcmp($a["name"], $b["name"]);
        });

        // Return the Languages
        return [
            "languages" => $languages,
            "rootCode"  => $rootCode,
        ];
    }
}
