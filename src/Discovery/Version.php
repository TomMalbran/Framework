<?php
namespace Framework\Discovery;

use Framework\Application;
use Framework\Console;
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
     * Sets the Version of the Framework in every file that stores it
     * @param string $version Optional.
     * @return void
     */
    #[ConsoleCommand("setVersion", isPrivate: true)]
    public static function setVersion(string $version = ""): void {
        $version = Strings::trim($version);
        if ($version === "") {
            $version = Strings::trim(Console::prompt("New version (ie. 0.17.0)"));
        }
        if (!Strings::match($version, '/^\d+\.\d+\.\d+$/')) {
            print("The version must be given as x.y.z\n");
            return;
        }
        self::writeVersion($version);
    }

    /**
     * Increases the Minor Version of the Framework
     * @return void
     */
    #[ConsoleCommand("incVersion", isPrivate: true)]
    public static function incVersion(): void {
        self::moveVersion(1);
    }

    /**
     * Decreases the Minor Version of the Framework
     * @return void
     */
    #[ConsoleCommand("decVersion", isPrivate: true)]
    public static function decVersion(): void {
        self::moveVersion(-1);
    }



    /**
     * Moves the Minor Version of the Framework by the given amount
     * @param int $amount
     * @return void
     */
    private static function moveVersion(int $amount): void {
        $version = Application::getVersion();
        $parts   = Strings::split($version, ".");
        if (count($parts) !== 3) {
            print("The current version ($version) is not in the x.y.z format\n");
            return;
        }

        // For now every version is 0.x.0, so only the minor moves
        $minor = Numbers::toInt($parts[1]) + $amount;
        if ($minor < 0) {
            print("The version can not be decreased below {$parts[0]}.0.0\n");
            return;
        }
        self::writeVersion("{$parts[0]}.$minor.0");
    }

    /**
     * Writes the given Version in every file that stores it
     * @param string $version
     * @return void
     */
    private static function writeVersion(string $version): void {
        $oldVersion = Application::getVersion();
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
                '/(frameworkphpar\/framework"\s*:\s*"dev-main#v)[^"]+(")/',
                "\${1}$version\${2}",
            ],
        ];

        foreach ($files as $fileName => [ $pattern, $replace ]) {
            $filePath = Package::getBasePath($fileName);
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

        self::writeDocsVersion($version);
    }

    /**
     * Writes the given Version in the Documentation pages
     * @param string $version
     * @return void
     */
    private static function writeDocsVersion(string $version): void {
        $docsPath = Package::getBasePath(Package::DocsDir);
        if (!Storage::fileExists($docsPath)) {
            return;
        }

        // The badge in the sidebar, the tag it links to, and the require in the install snippets
        $patterns = [
            '/(class="version" href="[^"]+\/tag\/v)[\d.]+(")/',
            '/(class="version"[^>]*>v)[^<]+(<)/',
            '/(dev-main#v)[\d.]+/',
        ];
        $replaces = [
            "\${1}$version\${2}",
            "\${1}$version\${2}",
            "\${1}$version",
        ];
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
