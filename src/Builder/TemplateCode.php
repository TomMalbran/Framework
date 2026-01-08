<?php
namespace Framework\Builder;

use Framework\Application;
use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The Template Code
 */
class TemplateCode {

    /**
     * Returns the File Code to Generate
     * @return array<string,mixed>
     */
    public static function getFileCode(): array {
        $path      = Application::getAppPath();
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


        // Return the Templates
        return [
            "templates" => $templates,
            "total"     => count($templates),
        ];
    }
}
