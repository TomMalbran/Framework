<?php
namespace Framework\Builder;

use Framework\Application;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\Priority;
use Framework\Builder\Builder;
use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The Template Code
 */
#[Priority(Priority::High)]
class TemplateCode implements DiscoveryBuilder {

    /**
     * Generates the code
     * @return integer
     */
    #[\Override]
    public static function generateCode(): int {
        $path      = Application::getBasePath();
        $filePaths = File::getFilesInDir($path, recursive: true, skipVendor: true);
        $templates = [];
        $maxLength = 0;

        // Parse the Files that end with .mu
        foreach ($filePaths as $filePath) {
            if (!Strings::endsWith($filePath, ".mu")) {
                continue;
            }

            $fileName    = File::getBaseName(File::getName($filePath));
            $maxLength   = max($maxLength, Strings::length($fileName));
            $relPath     = Strings::replace($filePath, $path, "");

            $templates[] = [
                "name"    => $fileName,
                "relPath" => $relPath,
            ];
        }

        foreach ($templates as $index => $template) {
            $templates[$index]["constant"] = Strings::padRight($template["name"], $maxLength, " ");
        }

        // Builds the code
        return Builder::generateCode("Template", [
            "templates" => $templates,
            "total"     => count($templates),
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
