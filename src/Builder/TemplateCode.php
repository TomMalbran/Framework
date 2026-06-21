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
 */
#[Priority(Priority::Highest)]
class TemplateCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $path      = Application::getBasePath();
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

        // Builds the code
        return Builder::generateCode("Template", [
            "templates" => $templates,
            "total"     => count($templates),
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
