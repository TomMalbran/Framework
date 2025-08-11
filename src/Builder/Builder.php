<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\Builder\AccessCode;
use Framework\Builder\ConfigCode;
use Framework\Builder\LanguageCode;
use Framework\Builder\RouterCode;
use Framework\Builder\SettingCode;
use Framework\Builder\SignalCode;
use Framework\Builder\TemplateCode;
use Framework\Database\SchemaBuilder;
use Framework\File\File;
use Framework\File\FilePath;
use Framework\File\FileType;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Builder
 */
class Builder {

    /**
     * Generates all the Code
     * @param boolean $willContinue Optional.
     * @return integer
     */
    public static function generateCode(bool $willContinue = false): int {
        $package   = self::getPackageData();
        $writePath = Discovery::getBuildPath();
        $files     = 0;

        File::createDir($writePath);
        File::emptyDir($writePath);


        print("\nFRAMEWORK MAIN CODES\n");
        $files += self::generateOne($writePath, "Package",  $package);
        $files += self::generateOne($writePath, "Path",     FilePath::getCode());
        $files += self::generateOne($writePath, "Language", LanguageCode::getCode());
        $files += self::generateOne($writePath, "Access",   AccessCode::getCode());
        $files += self::generateOne($writePath, "Template", TemplateCode::getCode());


        print("\nSCHEMA CODES\n");
        $files += SchemaBuilder::generateCode(forFramework: true);
        $files += SchemaBuilder::generateCode(forFramework: false);


        print("\nFRAMEWORK SEC CODES\n");
        $files += self::generateOne($writePath, "Setting",  SettingCode::getCode());
        $files += self::generateOne($writePath, "Config",   ConfigCode::getCode());
        $files += self::generateOne($writePath, "Signal",   SignalCode::getCode());
        $files += self::generateOne($writePath, "Router",   RouterCode::getCode());


        if (!$willContinue) {
            print("\nGenerated $files files\n");
        }
        return $files;
    }

    /**
     * Returns the Package Data
     * @return array<string,string>
     */
    private static function getPackageData(): array {
        $framePath = Discovery::getFramePath();
        if (Strings::contains($framePath, "vendor")) {
            $basePath = Strings::substringBefore($framePath, "/vendor");
            $appDir   = Strings::substringAfter($basePath, "/");
        } else {
            $basePath = $framePath;
            $appDir   = "";
        }

        // Read the Composer File
        $composer     = JSON::readFile($basePath, "composer.json");
        $psr          = [];
        $appNamespace = "";
        $sourceDir    = "";

        if (isset($composer["autoload"]) && is_array($composer["autoload"]) && is_array($composer["autoload"]["psr-4"])) {
            $psr          = $composer["autoload"]["psr-4"];
            $appNamespace = Strings::toString(key($psr));
            $sourceDir    = Strings::toString($psr[$appNamespace]);
        }

        // Find the Data and Template directories
        $files       = File::getFilesInDir($basePath, true);
        $dataDir     = "";
        $templateDir = "";
        $intFilesDir = "";

        foreach ($files as $file) {
            if (Strings::contains($file, "vendor") || File::hasExtension($file, "php")) {
                continue;
            }

            $dataFile = DataFile::getFileName($file);
            if ($dataDir === "" && $dataFile !== "") {
                $dataDir = Strings::substringBetween($file, "$basePath/", "/$dataFile");
            } elseif ($templateDir === "" && Strings::endsWith($file, ".mu")) {
                $templateDir = Strings::substringAfter($file, "$basePath/");
                $templateDir = Strings::substringBefore($templateDir, "/", false);
            } elseif ($intFilesDir === "" && FileType::isImage($file)) {
                $intFilesDir = Strings::substringAfter($file, "$basePath/");
                $intFilesDir = Strings::substringBefore($intFilesDir, "/", false);
            }
        }

        // Return the Package Data
        return [
            "basePath"     => $basePath,
            "appNamespace" => $appNamespace,
            "appDir"       => $appDir,
            "sourceDir"    => $sourceDir,
            "dataDir"      => $dataDir,
            "templateDir"  => $templateDir,
            "intFilesDir"  => $intFilesDir,
        ];
    }

    /**
     * Generates a single System Code
     * @param string              $writePath
     * @param string              $name
     * @param array<string,mixed> $data
     * @return integer
     */
    private static function generateOne(string $writePath, string $name, array $data): int {
        if (Arrays::isEmpty($data)) {
            return 0;
        }

        $contents = self::render("system/$name", $data + [
            "namespace" => Discovery::getBuildNamespace(),
        ]);

        File::create($writePath, "$name.php", $contents);
        print("- Generated the $name code\n");
        return 1;
    }



    /**
     * Renders a template with the given data
     * @param string              $name
     * @param array<string,mixed> $data
     * @return string
     */
    public static function render(string $name, array $data): string {
        $template = Discovery::loadFrameTemplate($name);
        return Mustache::render($template, $data);
    }

    /**
     * Aligns the Params of the given content
     * @param string $contents
     * @return string
     */
    public static function alignParams(string $contents): string {
        $lines      = Strings::split($contents, "\n");
        $typeLength = 0;
        $varLength  = 0;
        $result     = [];

        foreach ($lines as $index => $line) {
            if (Strings::contains($line, "/**")) {
                [ $typeLength, $varLength ] = self::getLongestParam($lines, $index);
            } elseif (Strings::contains($line, "@param")) {
                $docType    = Strings::substringBetween($line, "@param ", " ");
                $docTypePad = Strings::padRight($docType, $typeLength);
                $line       = Strings::replace($line, $docType, $docTypePad);
                if (Strings::contains($line, "Optional.")) {
                    $varName    = Strings::substringBetween($line, "$docTypePad ", " Optional.");
                    $varNamePad = Strings::padRight($varName, $varLength);
                    $line       = Strings::replace($line, $varName, $varNamePad);
                }
            }
            $result[] = $line;
        }
        return Strings::join($result, "\n");
    }

    /**
     * Returns the longest Param and Type of the current Doc comment
     * @param string[] $lines
     * @param integer  $index
     * @return integer[]
     */
    private static function getLongestParam(array $lines, int $index): array {
        $line       = $lines[$index];
        $typeLength = 0;
        $varLength  = 0;

        while (!Strings::contains($line, "*/")) {
            if (Strings::contains($line, "@param")) {
                $docType    = Strings::substringBetween($line, "@param ", " ");
                $typeLength = max($typeLength, Strings::length($docType));
                $varName    = Strings::substringBetween($line, "$docType ", " Optional.");
                $varLength  = max($varLength, Strings::length($varName));
            }
            $index += 1;
            $line   = $lines[$index];
        }
        return [ $typeLength, $varLength ];
    }
}
