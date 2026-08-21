<?php
namespace Tests\Tools;

use Framework\Application;
use Framework\Discovery\Type\ComposerData;
use Framework\Discovery\Package;
use Framework\File\Storage;
use Framework\Utils\Strings;
use Framework\Tools\Version;

use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Framework Version command
 *
 * The writing is run against a copy in a temporary directory, so a test run
 * cannot leave a rewritten composer.json, README or documentation page behind.
 */
class VersionTest extends TestCase {
    use TestHelpers;

    public function testTheVersionIsPrinted(): void {
        $this->expectOutputString("Version: " . Application::getVersion() . "\n");

        Version::getVersion();
    }

    public function testTheApplicationAndThePackageAgreeOnIt(): void {
        // They read the same composer file here, since the Framework is the app
        $this->assertSame(Application::getVersion(), Package::getVersion());
    }



    /**
     * The version a move lands on, or empty when it cannot move there
     * @param string $version
     * @param int    $amount
     * @param bool   $patch
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerMoveVersion")]
    public function testTheVersionMovesWhereItIsAsked(
        string $version,
        int $amount,
        bool $patch,
        string $expected,
    ): void {
        $this->assertSame($expected, Version::moveVersion($version, $amount, patch: $patch));
    }

    /**
     * The minor takes the patch back to zero with it, which is what a release
     * has always done. The patch moves on its own when it is asked for.
     * @return array<string,array{string,int,bool,string}>
     */
    public static function providerMoveVersion(): array {
        return [
            "the minor up"          => [ "0.17.0", 1, false, "0.18.0" ],
            "the minor down"        => [ "0.18.0", -1, false, "0.17.0" ],
            "the minor over ten"    => [ "0.9.0", 1, false, "0.10.0" ],
            "the minor keeps major" => [ "2.4.0", 1, false, "2.5.0" ],
            "the minor drops patch" => [ "1.2.3", 1, false, "1.3.0" ],
            "the minor at zero"     => [ "1.0.0", -1, false, "" ],

            "the patch up"          => [ "0.17.0", 1, true, "0.17.1" ],
            "the patch down"        => [ "0.17.1", -1, true, "0.17.0" ],
            "the patch over ten"    => [ "0.17.9", 1, true, "0.17.10" ],
            "the patch keeps minor" => [ "1.2.3", 1, true, "1.2.4" ],
            "the patch at zero"     => [ "0.17.0", -1, true, "" ],

            "two parts"             => [ "0.17", 1, false, "" ],
            "four parts"            => [ "0.17.0.1", 1, false, "" ],
            "nothing at all"        => [ "", 1, false, "" ],
        ];
    }

    /**
     * Builds a copy of the files a version is written into, and returns its path
     * @param array<string,string> $files
     * @return string
     */
    private function makeCopy(array $files): string {
        $basePath = sys_get_temp_dir() . "/versionTest" . getmypid();
        Storage::deleteDir($basePath);

        foreach ($files as $path => $contents) {
            $full = "$basePath/$path";
            Storage::createDir(dirname($full));
            Storage::writeFile($full, $contents);
        }
        return $basePath;
    }

    /**
     * A copy of the files, and what writing a version into it leaves behind
     * @param array<string,string> $files
     * @param string               $version
     * @param array<string,string> $expected What each file has to hold afterwards
     * @param list<string>         $says     What the command has to have said
     * @return void
     */
    #[DataProvider("providerWriteVersion")]
    public function testTheVersionIsWrittenWhereItIsRecorded(
        array $files,
        string $version,
        array $expected,
        array $says,
    ): void {
        $basePath = $this->makeCopy($files);

        ob_start();
        Version::writeVersion($version, $basePath);
        $output = Strings::toString(ob_get_clean());

        foreach ($expected as $path => $contents) {
            $this->assertSame($contents, Storage::readFile("$basePath/$path"), "in $path");
        }
        foreach ($says as $said) {
            $this->assertStringContainsString($said, $output);
        }
        Storage::deleteDir($basePath);
    }

    /**
     * The four kinds of file that record the version, and the ways each is missed
     * @return array<string,array{array<string,string>,string,array<string,string>,list<string>}>
     */
    public static function providerWriteVersion(): array {
        $composer = "{\n    \"name\": \"frameworkdevar/framework\",\n    \"version\": \"0.17.0\"\n}";
        $readme   = "Require it with\n\n    \"frameworkdevar/framework\": \"dev-main#v0.17.0\"\n";
        $versions = "window.DOCS_VERSION = \"0.17.0\";\nwindow.DOCS_BRANCH = \"main\";";
        $page     = "<p>dev-main#v0.17.0</p>";
        $whole    = [
            "composer.json"          => $composer,
            "README.md"              => $readme,
            "docs/assets/version.js" => $versions,
            "docs/index.html"        => $page,
            "docs/guides/one.html"   => $page,
        ];

        return [
            "every file that records it"             => [
                $whole, "0.18.0",
                [
                    "composer.json"          => "{\n    \"name\": \"frameworkdevar/framework\",\n    \"version\": \"0.18.0\"\n}",
                    "README.md"              => "Require it with\n\n    \"frameworkdevar/framework\": \"dev-main#v0.18.0\"\n",
                    "docs/assets/version.js" => "window.DOCS_VERSION = \"0.18.0\";\nwindow.DOCS_BRANCH = \"main\";",
                    "docs/index.html"        => "<p>dev-main#v0.18.0</p>",
                    "docs/guides/one.html"   => "<p>dev-main#v0.18.0</p>",
                ],
                [ "Updated composer.json", "Updated README.md", "Updated the documentation version", "Updated 2 documentation pages" ],
            ],
            "a patch is written the same"            => [
                $whole, "0.17.1",
                [ "composer.json" => "{\n    \"name\": \"frameworkdevar/framework\",\n    \"version\": \"0.17.1\"\n}" ],
                [ "Updating the version from 0.17.0 to 0.17.1" ],
            ],
            "the version it is already at"           => [
                $whole, "0.17.0",
                [ "composer.json" => $composer ],
                [ "The version is already 0.17.0" ],
            ],
            "a composer file with no version"        => [
                [ "composer.json" => "{\n    \"name\": \"frameworkdevar/framework\"\n}" ], "0.18.0",
                [ "composer.json" => "{\n    \"name\": \"frameworkdevar/framework\"\n}" ],
                [ "No version found in composer.json" ],
            ],
            "no readme at all"                       => [
                [ "composer.json" => $composer ], "0.18.0",
                [],
                [ "Could not read README.md" ],
            ],
            "a readme that requires another package" => [
                [ "composer.json" => $composer, "README.md" => "\"other/package\": \"dev-main#v0.17.0\"" ], "0.18.0",
                [ "README.md" => "\"other/package\": \"dev-main#v0.17.0\"" ],
                [ "No version found in README.md" ],
            ],
            "no documentation at all"                => [
                [ "composer.json" => $composer, "README.md" => $readme ], "0.18.0",
                [],
                [ "Updated composer.json" ],
            ],
            "a version.js that lost its line"        => [
                [ "composer.json" => $composer, "docs/assets/version.js" => "window.DOCS_BRANCH = \"main\";" ], "0.18.0",
                [ "docs/assets/version.js" => "window.DOCS_BRANCH = \"main\";" ],
                [ "No version found in assets/version.js" ],
            ],
            "pages that name no version"             => [
                [ "composer.json" => $composer, "docs/assets/version.js" => $versions, "docs/index.html" => "<p>nothing here</p>" ], "0.18.0",
                [ "docs/index.html" => "<p>nothing here</p>" ],
                [ "Updated the documentation version" ],
            ],
        ];
    }

    public function testAVersionThatCannotMoveIsNotWritten(): void {
        // The patch of a released x.y.0 has nowhere below it to go, and the
        // refusal has to come before anything is written
        $composerWas = $this->swapComposer(new ComposerData(version: "1.0.0"));

        try {
            $this->expectOutputString("The version can not be moved from 1.0.0\n");
            Version::decVersion(patch: true);
        } finally {
            $this->swapComposer($composerWas);
        }
    }
}
