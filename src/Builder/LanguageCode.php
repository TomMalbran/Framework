<?php
namespace Framework\Builder;

use Framework\Application;
use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\Priority;
use Framework\Builder\Builder;
use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The Language Code
 */
#[Priority(Priority::High)]
class LanguageCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return integer
     */
    #[\Override]
    public static function generateCode(): int {
        $path      = Application::getStringsPath();
        $files     = File::getFilesInDir($path);
        $rootCode  = "es";
        $rootFound = false;
        $languages = [];

        // Load all the languages
        foreach ($files as $file) {
            $code = Strings::stripEnd($file, ".json");
            $data = Discovery::loadStrings($code);
            if (!isset($data["NAME"])) {
                continue;
            }

            $languages[] = [
                "code" => $code,
                "name" => Strings::toString($data["NAME"]),
            ];
            if ($code === $rootCode) {
                $rootFound = true;
            }
        }

        // If no languages are found, add a default one
        if (count($languages) === 0) {
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
        usort($languages, function(array $a, array $b) use ($rootCode) {
            if ($a["code"] === $rootCode) {
                return -1;
            }
            if ($b["code"] === $rootCode) {
                return 1;
            }
            return Strings::compare($a["name"], $b["name"]);
        });


        // Builds the code
        return Builder::generateCode("Language", [
            "languages" => $languages,
            "rootCode"  => $rootCode,
            "total"     => count($languages),
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
