<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\ConsoleCommand;
use Framework\Discovery\Package;
use Framework\File\File;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Builder
 */
class Builder {

    /** @var array<string,string> */
    private static array $templates = [];


    /**
     * Builds all the Code
     * @return boolean
     */
    #[ConsoleCommand("build")]
    public static function build(): bool {
        print("Building the Code...\n");

        DiscoveryConfig::load();
        self::loadTemplates();
        $writePath = Package::getBuildPath();
        $created   = 0;

        File::createDir($writePath);
        File::emptyDir($writePath);


        /** @var DiscoveryBuilder[] */
        $frameBuilders = Discovery::getClassesWithInterface(DiscoveryBuilder::class, forFramework: true);
        if (count($frameBuilders) > 0) {
            print("\nFRAMEWORK CODES\n");
            foreach ($frameBuilders as $builder) {
                $created += $builder::generateCode();
            }
        }


        if (!Package::isFramework()) {
            /** @var DiscoveryBuilder[] */
            $appBuilders = Discovery::getClassesWithInterface(DiscoveryBuilder::class);
            if (count($appBuilders) > 0) {
                print("\nAPP CODES\n");
                foreach ($appBuilders as $builder) {
                    $created += $builder::generateCode();
                }
            }
        }

        print("\nGenerated $created files\n");
        return true;
    }

    /**
     * Destroys all the Code
     * @return boolean
     */
    #[ConsoleCommand("destroy")]
    public static function destroy(): bool {
        $writePath = Package::getBuildPath();
        $deleted   = 0;

        if (!Package::isFramework()) {
            /** @var DiscoveryBuilder[] */
            $appBuilders = Discovery::getClassesWithInterface(DiscoveryBuilder::class);
            $appBuilders = array_reverse($appBuilders);
            foreach ($appBuilders as $builder) {
                $deleted += $builder::destroyCode();
            }
        }

        /** @var DiscoveryBuilder[] */
        $frameBuilders = Discovery::getClassesWithInterface(DiscoveryBuilder::class, forFramework: true);
        $frameBuilders = array_reverse($frameBuilders);
        foreach ($frameBuilders as $builder) {
            $deleted += $builder::destroyCode();
        }

        File::deleteDir($writePath, $deleted);

        print("\nDestroyed $deleted generated files\n\n");
        return true;
    }



    /**
     * Loads all the Templates
     * @return boolean
     */
    private static function loadTemplates(): bool {
        $path      = Package::getBasePath();
        $filePaths = File::getFilesInDir($path, recursive: true, skipVendor: true);

        foreach ($filePaths as $filePath) {
            if (Strings::endsWith($filePath, ".mu")) {
                $fileName = File::getBaseName(File::getName($filePath));
                self::$templates[$fileName] = File::read($filePath);
            }
        }
        return true;
    }

    /**
     * Generates a single System Code
     * @param string              $name
     * @param array<string,mixed> $data Optional.
     * @return integer
     */
    public static function generateCode(string $name, array $data = []): int {
        if (Arrays::isEmpty($data)) {
            print("- Skipping the $name code\n");
            return 0;
        }

        $writePath = Package::getBuildPath();
        $contents  = self::render($name, $data + [
            "namespace" => Package::Namespace . Package::SystemDir,
        ]);
        File::create($writePath, "$name.php", $contents);

        $total = "";
        if (isset($data["total"]) && is_int($data["total"])) {
            $plural = $data["total"] !== 1 ? "s" : "";
            $total  = "-> {$data["total"]} item$plural";
        }
        print("- Generated the $name code $total\n");
        return 1;
    }

    /**
     * Renders a template with the given data
     * @param string              $name
     * @param array<string,mixed> $data
     * @return string
     */
    public static function render(string $name, array $data): string {
        if (!isset(self::$templates[$name])) {
            return "";
        }

        $content = self::$templates[$name];
        $result  = Mustache::render($content, $data);
        return self::alignParams($result);
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
     * @return array{integer,integer}
     */
    private static function getLongestParam(array $lines, int $index): array {
        $line       = $lines[$index] ?? "";
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
            $line   = $lines[$index] ?? "";
        }
        return [ $typeLength, $varLength ];
    }
}
