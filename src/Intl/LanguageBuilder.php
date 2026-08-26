<?php
namespace Framework\Intl;

use Framework\Analysis\Attr\NotTested;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Discovery\Attr\Priority;
use Framework\Builder\Builder;
use Framework\Intl\IntlConfig;
use Framework\File\Storage;
use Framework\Utils\Strings;

/**
 * The Language Builder
 * @phpstan-type LanguageData array{
 *   code: string,
 *   name: string,
 * }
 * @phpstan-type LanguageResult array{
 *   languages: list<LanguageData>,
 *   rootCode:  string,
 *   total:     int,
 * }
 */
#[Priority(Priority::Highest)]
class LanguageBuilder implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    #[NotTested("It generates a file")]
    public static function generateCode(): int {
        $data = self::collectLanguages();
        return Builder::generateCode("Language", $data);
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
     * Collects the Languages from the Strings files
     * @return LanguageResult
     */
    public static function collectLanguages(): array {
        $path      = IntlConfig::getStringsPath();
        $files     = Storage::getFilesInDir($path);
        $rootCode  = IntlConfig::getDefaultLanguage();
        $rootFound = false;
        $languages = [];

        // Load all the languages
        foreach ($files as $file) {
            $code = Strings::stripEnd($file, ".json");
            $data = IntlConfig::loadStrings($code);
            if (!$data->hasValue("NAME")) {
                continue;
            }

            $languages[] = [
                "code" => $code,
                "name" => $data->getString("NAME"),
            ];
            if ($code === $rootCode) {
                $rootFound = true;
            }
        }

        // If no languages are found, add a default one
        if (count($languages) === 0) {
            $languages[] = [
                "code" => "en",
                "name" => "English",
            ];
        }

        // If the root language is not found, set the first one as root
        if (!$rootFound) {
            $rootCode = $languages[0]["code"];
        }


        // Sort the Root Language to the top
        usort($languages, function (array $a, array $b) use ($rootCode) {
            if ($a["code"] === $rootCode) {
                return -1;
            }
            if ($b["code"] === $rootCode) {
                return 1;
            }
            return Strings::compare($a["name"], $b["name"]);
        });

        return [
            "languages" => $languages,
            "rootCode"  => $rootCode,
            "total"     => count($languages),
        ];
    }
}
