<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Builder\AccessCode;
use Framework\Builder\ConfigCode;
use Framework\Builder\LanguageCode;
use Framework\Builder\RouterCode;
use Framework\Builder\SettingCode;
use Framework\Builder\SignalCode;
use Framework\Builder\TemplateCode;
use Framework\Database\SchemaBuilder;
use Framework\Discovery\DataFile;
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

    private const Namespace = "Framework\\";
    private const SystemDir = "System";



    /**
     * Generates all the Code
     * @param boolean $willContinue Optional.
     * @return integer
     */
    public static function generateCode(bool $willContinue = false): int {
        $package   = self::getPackageData();
        $writePath = File::parsePath($package["framePath"], "src", self::SystemDir);
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
            "framePath"    => $framePath,
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

        $template = Discovery::loadFrameTemplate($name);
        $contents = Mustache::render($template, $data + [
            "namespace" => self::Namespace . self::SystemDir,
        ]);

        File::create($writePath, "$name.php", $contents);
        print("- Generated the $name code\n");
        return 1;
    }
}
