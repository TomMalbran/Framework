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
     * @return boolean
     */
    public static function generateCode(): bool {
        $package   = self::getPackageData();
        $writePath = File::parsePath($package["framePath"], "src", self::SystemDir);

        File::createDir($writePath);
        File::emptyDir($writePath);


        print("\nFRAMEWORK MAIN CODES\n");
        self::generateOne($writePath, "Package",  $package);
        self::generateOne($writePath, "Path",     FilePath::getCode());
        self::generateOne($writePath, "Language", LanguageCode::getCode());
        self::generateOne($writePath, "Access",   AccessCode::getCode());
        self::generateOne($writePath, "Status",   StatusCode::getCode());


        print("\nSCHEMA CODES\n");
        $schemaWritePath = File::parsePath($package["basePath"], $package["sourceDir"], self::SchemaDir);
        Generator::generateCode($package["appNamespace"], $schemaWritePath, false);

        $schemaWritePath = File::parsePath($package["framePath"], "src", self::SchemaDir);
        Generator::generateCode(self::Namespace, $schemaWritePath, true);


        print("\nFRAMEWORK SEC CODES\n");
        self::generateOne($writePath, "Setting", SettingCode::getCode());
        self::generateOne($writePath, "Config",  ConfigCode::getCode());
        self::generateOne($writePath, "Signal",  SignalCode::getCode());
        self::generateOne($writePath, "Router",  RouterCode::getCode());
        return true;
    }

    /**
     * Returns the Package Data
     * @return array{}
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

        foreach ($files as $file) {
            if (empty($dataDir) && Strings::endsWith($file, $schemasFile)) {
                $dataDir = Strings::substringBetween($file, "$basePath/", "/$schemasFile");
            } elseif (empty($templateDir) && Strings::endsWith($file, ".mu")) {
                $templateDir = Strings::substringAfter($file, "$basePath/");
                $templateDir = Strings::substringBefore($templateDir, "/", false);
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
        ];
    }

    /**
     * Generates a single System Code
     * @param string  $writePath
     * @param string  $name
     * @param array{} $data
     * @return boolean
     */
    private static function generateOne(string $writePath, string $name, array $data): bool {
        if (empty($data)) {
            return false;
        }

        $template = Discovery::loadFrameTemplate($name);
        $contents = Mustache::render($template, $data + [
            "namespace" => self::Namespace . self::SystemDir,
        ]);

        File::create($writePath, "$name.php", $contents);
        print("- Generated the $name code\n");
        return true;
    }
}
