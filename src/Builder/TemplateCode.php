<?php
namespace Framework\Builder;

use Framework\Application;
use Framework\Discovery\Attr\Priority;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Builder\Builder;
use Framework\File\Storage;
use Framework\Utils\Strings;

/**
 * The Template Code
 * @phpstan-type TemplateData array{
 *   name:     string,
 *   relPath:  string,
 *   constant: string,
 * }
 * @phpstan-type TemplateResult array{
 *   templates: list<TemplateData>,
 *   total:     int,
 * }
 */
#[Priority(Priority::Highest)]
class TemplateCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $data = self::collectTemplates();
        return Builder::generateCode("Template", $data);
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
     * Collects the Templates from the Files in the Base Path
     * @param string $basePath Optional.
     * @return TemplateResult
     */
    public static function collectTemplates(string $basePath = ""): array {
        $path      = $basePath !== "" ? $basePath : Application::getBasePath();
        $filePaths = Storage::getFilesInDir($path, recursive: true, skipVendor: true);
        $templates = [];
        $maxLength = 0;

        // Parse the Files that end with .mu
        foreach ($filePaths as $filePath) {
            if (!Strings::endsWith($filePath, ".mu")) {
                continue;
            }

            $fileName  = Storage::getBaseName(Storage::getFileName($filePath));
            $maxLength = max($maxLength, Strings::length($fileName));
            $relPath   = Strings::replace($filePath, $path, "");

            $templates[] = [
                "name"    => $fileName,
                "relPath" => $relPath,
            ];
        }

        foreach ($templates as $index => $template) {
            $templates[$index]["constant"] = Strings::padRight(
                $template["name"],
                $maxLength,
            );
        }

        return [
            "templates" => $templates,
            "total"     => count($templates),
        ];
    }
}
