<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\DiscoveryCode;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\ConsoleCommand;
use Framework\Builder\LanguageCode;
use Framework\Builder\RouterCode;
use Framework\Builder\SignalCode;
use Framework\Database\SchemaBuilder;
use Framework\File\File;
use Framework\File\FilePath;
use Framework\Provider\Mustache;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Builder
 */
class Builder {

    /**
     * Builds all the Code
     * @return boolean
     */
    #[ConsoleCommand("build")]
    public static function build(): bool {
        print("Building the Code...\n");

        DiscoveryConfig::load();
        $writePath = Discovery::getBuildPath();
        $created   = 0;

        File::createDir($writePath);
        File::emptyDir($writePath);


        print("\nFRAMEWORK MAIN CODES\n");
        $created += self::generateOne($writePath, "Path",     FilePath::getCode());
        $created += self::generateOne($writePath, "Language", LanguageCode::getCode());


        print("\nFRAMEWORK SCHEMA CODES\n");
        $created += SchemaBuilder::generateCode(forFramework: true);
        $created += SchemaBuilder::generateCode(forFramework: false);


        /** @var DiscoveryCode[] */
        $frameCodes = Discovery::getClassesWithInterface(DiscoveryCode::class, forFramework: true);
        if (count($frameCodes) > 0) {
            print("\nFRAMEWORK BUILDER CODES\n");
            foreach ($frameCodes as $code) {
                $created += self::generateOne($writePath, $code::getFileName(), $code::getFileCode());
            }
        }


        print("\nFRAMEWORK FINAL CODES\n");
        $created += self::generateOne($writePath, "Signal", SignalCode::getCode());
        $created += self::generateOne($writePath, "Router", RouterCode::getCode());


        /** @var DiscoveryBuilder[] */
        $appBuilders = Discovery::getClassesWithInterface(DiscoveryBuilder::class);
        if (count($appBuilders) > 0) {
            print("\nAPP CODES\n");
            foreach ($appBuilders as $builder) {
                $created += $builder::generateCode();
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
        $writePath = Discovery::getBuildPath();
        $deleted   = 0;
        File::deleteDir($writePath, $deleted);

        $deleted += SchemaBuilder::destroyCode(forFramework: true);
        $deleted += SchemaBuilder::destroyCode(forFramework: false);

        /** @var DiscoveryBuilder[] */
        $appBuilders = Discovery::getClassesWithInterface(DiscoveryBuilder::class);
        foreach ($appBuilders as $builder) {
            $deleted += $builder::destroyCode();
        }

        print("\nDestroyed $deleted generated files\n");
        return true;
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
            print("- Skipping the $name code\n");
            return 0;
        }

        $contents = self::render("system/$name", $data + [
            "namespace" => Discovery::getBuildNamespace(),
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
