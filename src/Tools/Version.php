<?php
namespace Framework\Tools;

use Framework\Application;
use Framework\Discovery\Composer;
use Framework\Discovery\Package;
use Framework\Discovery\Attr\Priority;
use Framework\Discovery\Attr\ConsoleCommand;
use Framework\File\Storage;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * The Framework Version
 */
class Version {

    /**
     * Displays the version information
     * @return void
     */
    #[ConsoleCommand("version", "-v")]
    #[Priority(Priority::Highest)]
    public static function getVersion(): void {
        $version = Application::getVersion();
        print("Version: $version\n");
    }

    /**
     * Increases the Version of the Framework
     * @param bool $patch Optional.
     * @return void
     */
    #[ConsoleCommand("incVersion", isPrivate: true)]
    public static function incVersion(bool $patch = false): void {
        self::applyMove(1, patch: $patch);
    }

    /**
     * Decreases the Version of the Framework
     * @param bool $patch Optional.
     * @return void
     */
    #[ConsoleCommand("decVersion", isPrivate: true)]
    public static function decVersion(bool $patch = false): void {
        self::applyMove(-1, patch: $patch);
    }



    /**
     * Moves the Version of the Framework, and writes what it lands on
     * @param int  $amount
     * @param bool $patch
     * @return void
     */
    private static function applyMove(int $amount, bool $patch): void {
        $version = Application::getVersion();
        $moved   = self::moveVersion($version, $amount, patch: $patch);

        if ($moved === "") {
            print("The version can not be moved from $version\n");
            return;
        }
        self::writeVersion($moved);
    }

    /**
     * Returns the Version the given one moves to, or empty when it cannot move
     * @param string $version
     * @param int    $amount
     * @param bool   $patch   Optional.
     * @return string
     */
    public static function moveVersion(string $version, int $amount, bool $patch = false): string {
        $parts = Strings::split($version, ".");
        if (count($parts) !== 3) {
            return "";
        }

        $major = Numbers::toInt($parts[0]);
        $minor = Numbers::toInt($parts[1]);
        $fix   = Numbers::toInt($parts[2]);

        if ($patch) {
            $fix += $amount;
            return $fix < 0 ? "" : "$major.$minor.$fix";
        }

        $minor += $amount;
        return $minor < 0 ? "" : "$major.$minor.0";
    }

    /**
     * Writes the given Version in every file that stores it
     * @param string $version
     * @param string $basePath Optional.
     * @return void
     */
    public static function writeVersion(string $version, string $basePath = ""): void {
        $basePath   = $basePath !== "" ? $basePath : Package::getBasePath();
        $oldVersion = Composer::readFile($basePath)["version"];

        if ($version === $oldVersion) {
            print("The version is already $version\n");
            return;
        }

        print("Updating the version from $oldVersion to $version...\n");
        $files = [
            // The version used by Application::getVersion()
            "composer.json" => [
                '/("version"\s*:\s*")[^"]+(")/',
                "\${1}$version\${2}",
            ],
            // The version an app requires in its composer.json
            "README.md"     => [
                '/(frameworkdevar\/framework"\s*:\s*"dev-main#v)[^"]+(")/',
                "\${1}$version\${2}",
            ],
        ];

        foreach ($files as $fileName => [ $pattern, $replace ]) {
            $filePath = Storage::parsePath($basePath, $fileName);
            $contents = Storage::readFile($filePath);
            if ($contents === "") {
                print("- Could not read $fileName\n");
                continue;
            }

            $result = Strings::replacePattern($contents, $pattern, $replace);
            if ($result === $contents) {
                print("- No version found in $fileName\n");
                continue;
            }

            Storage::writeFile($filePath, $result);
            print("- Updated $fileName\n");
        }

        self::writeDocsVersion($version, $basePath);
    }

    /**
     * Writes the given Version in the Documentation pages
     * @param string $version
     * @param string $basePath
     * @return void
     */
    private static function writeDocsVersion(string $version, string $basePath): void {
        $docsPath = Storage::parsePath($basePath, Package::DocsDir);
        if (!Storage::fileExists($docsPath)) {
            return;
        }

        // The sidebar badge is built from this, rather than written into every page
        $definition = "assets/version.js";
        $contents   = Storage::readFile($docsPath, $definition);
        $result     = Strings::replacePattern(
            $contents,
            '/(DOCS_VERSION\s*=\s*")[\d.]+(")/',
            "\${1}$version\${2}",
        );
        if ($result === $contents) {
            print("- No version found in $definition\n");
        } else {
            Storage::writeFile("$docsPath/$definition", $result);
            print("- Updated the documentation version\n");
        }

        // The require in the install snippets is left as the text a reader copies
        $patterns = [ '/(dev-main#v)[\d.]+/' ];
        $replaces = [ "\${1}$version" ];
        $total    = 0;

        foreach (Storage::getFilesInDir($docsPath, recursive: true) as $filePath) {
            if (!Strings::endsWith($filePath, ".html")) {
                continue;
            }

            $contents = Storage::readFile($filePath);
            $result   = Strings::replacePattern($contents, $patterns, $replaces);
            if ($result === $contents) {
                continue;
            }

            Storage::writeFile($filePath, $result);
            $total += 1;
        }

        if ($total > 0) {
            print("- Updated $total documentation pages\n");
        }
    }
}
