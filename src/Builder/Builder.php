<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Builder\AccessCode;
use Framework\Builder\ConfigCode;
use Framework\Builder\LanguageCode;
use Framework\Builder\RouterCode;
use Framework\Builder\SettingCode;
use Framework\Builder\SignalCode;
use Framework\Builder\StatusCode;
use Framework\Database\Generator;
use Framework\Discovery\DataFile;
use Framework\File\File;
use Framework\File\FilePath;
use Framework\File\FileType;
use Framework\Provider\Mustache;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Builder
 */
class Builder {

    private const Namespace = "Framework\\";
    private const SystemDir = "System";
    private const SchemaDir = "Schema";



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
        $files += self::generateOne($writePath, "Status",   StatusCode::getCode());


        print("\nSCHEMA CODES\n");
        $schemaWritePath = File::parsePath($package["basePath"], $package["sourceDir"], self::SchemaDir);
        $files += Generator::generateCode($package["appNamespace"], $schemaWritePath, false);

        $schemaWritePath = File::parsePath($package["framePath"], "src", self::SchemaDir);
        $files += Generator::generateCode(self::Namespace, $schemaWritePath, true);


        print("\nFRAMEWORK SEC CODES\n");
        $files += self::generateOne($writePath, "Setting", SettingCode::getCode());
        $files += self::generateOne($writePath, "Config",  ConfigCode::getCode());
        $files += self::generateOne($writePath, "Signal",  SignalCode::getCode());
        $files += self::generateOne($writePath, "Router",  RouterCode::getCode());


        if (!$willContinue) {
            print("\nGenerated $files files\n");
        }
        return $files;
    }

    /**
     * Returns the Package Data
     * @return array<string,mixed>
     */
    private static function getPackageData(): array {
        $framePath = dirname(__FILE__, 3);
        if (Strings::contains($framePath, "vendor")) {
            $basePath = Strings::substringBefore($framePath, "/vendor");
            $appDir   = Strings::substringAfter($basePath, "/");
        } else {
            $basePath = $framePath;
            $appDir   = "";
        }

        // Read the Composer File
        $composer     = JSON::readFile($basePath, "composer.json");
        $psr          = $composer["autoload"]["psr-4"];
        $appNamespace = key($psr);
        $sourceDir    = $psr[$appNamespace];

        // Find the Data and Template directories
        $files       = File::getFilesInDir($basePath, true);
        $schemasFile = DataFile::Schemas->fileName();
        $dataDir     = "";
        $templateDir = "";
        $intFilesDir = "";

        foreach ($files as $file) {
            if (Strings::contains($file, "vendor") || File::hasExtension($file, "php")) {
                continue;
            }

            if (empty($dataDir) && Strings::endsWith($file, $schemasFile)) {
                $dataDir = Strings::substringBetween($file, "$basePath/", "/$schemasFile");
            } elseif (empty($templateDir) && Strings::endsWith($file, ".mu")) {
                $templateDir = Strings::substringAfter($file, "$basePath/");
                $templateDir = Strings::substringBefore($templateDir, "/", false);
            } elseif (empty($intFilesDir) && FileType::isImage($file)) {
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
        if (empty($data)) {
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
