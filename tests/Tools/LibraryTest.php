<?php
namespace Tests\Tools;

use Framework\Discovery\Type\ComposerData;
use Framework\Discovery\Type\LibraryData;
use Framework\File\Storage;
use Framework\Tools\Library;

use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Libraries copied into a Project
 *
 * The download is the one half that needs the repository, so what is checked
 * here is what happens to the files once they are there: an archive is built
 * the way a tag of one extracts, and the copy is pointed at it.
 */
class LibraryTest extends TestCase {
    use TestHelpers;

    private string $basePath = "";

    protected function setUp(): void {
        $this->basePath = sys_get_temp_dir() . "/libraryTest" . getmypid();
        Storage::deleteDir($this->basePath);
        Storage::createDir($this->basePath);
    }

    protected function tearDown(): void {
        Storage::deleteDir($this->basePath);
    }

    /**
     * Writes the given files under the base path, and returns the path they are at
     * @param string               $dir
     * @param array<string,string> $files
     * @return string
     */
    private function writeFiles(string $dir, array $files): string {
        $path = "{$this->basePath}/$dir";
        foreach ($files as $name => $contents) {
            $full = "$path/$name";
            Storage::createDir(dirname($full));
            Storage::writeFile($full, $contents);
        }
        return $path;
    }

    /**
     * Copies the source of the given Library from the archive to the project
     * @param LibraryData $library
     * @param string      $fromPath
     * @param string      $toPath
     * @return bool
     */
    private function copySource(LibraryData $library, string $fromPath, string $toPath): bool {
        return (bool)$this->callPrivateStaticMethod(
            Library::class,
            "copySource",
            $library,
            $fromPath,
            $toPath,
        );
    }

    /**
     * Returns a Library of the given source, with everything else it takes to be placed
     * @param string $source Optional.
     * @param string $tag    Optional.
     * @param string $branch Optional.
     * @return LibraryData
     */
    private function library(
        string $source = "src",
        string $tag = "v0.1.0",
        string $branch = "",
    ): LibraryData {
        return new LibraryData(
            "dashboard",
            $tag,
            $branch,
            "https://github.com/FrameworkDevAR/Dashboard",
            $source,
            "client/src/Dashboard",
        );
    }



    public function testTheSourceIsCopied(): void {
        // The archive holds the whole repository, and only the source is taken
        $fromPath = $this->writeFiles("Dashboard-0.1.0", [
            "src/Components/Common/Icon.jsx" => "icon",
            "src/Core/Store.js"              => "store",
            "src/Dashboard.jsx"              => "dashboard",
            "package.json"                   => "{}",
            "README.md"                      => "read me",
        ]);
        $toPath = "{$this->basePath}/client/src/Dashboard";

        $this->assertTrue($this->copySource($this->library(), $fromPath, $toPath));

        $this->assertSame("icon", Storage::readFile($toPath, "Components/Common/Icon.jsx"));
        $this->assertSame("store", Storage::readFile($toPath, "Core/Store.js"));
        $this->assertSame("dashboard", Storage::readFile($toPath, "Dashboard.jsx"));

        // The rest of the repository is not part of the library
        $this->assertFalse(Storage::fileExists($toPath, "package.json"));
        $this->assertFalse(Storage::fileExists($toPath, "README.md"));
    }

    public function testTheWholeRepositoryIsCopiedWithNoSource(): void {
        $fromPath = $this->writeFiles("Dashboard-0.1.0", [
            "src/Core/Store.js" => "store",
            "package.json"      => "{}",
        ]);
        $toPath = "{$this->basePath}/client/src/Dashboard";

        $this->assertTrue($this->copySource($this->library(source: ""), $fromPath, $toPath));

        $this->assertSame("store", Storage::readFile($toPath, "src/Core/Store.js"));
        $this->assertSame("{}", Storage::readFile($toPath, "package.json"));
    }

    public function testWhatTheVersionDroppedIsGone(): void {
        // A copy alone would leave behind the files the new version does not have,
        // which is what makes a placed library not the version it says it is
        $toPath = $this->writeFiles("client/src/Dashboard", [
            "Components/Common/Gone.jsx" => "dropped",
            "Core/Store.js"              => "the old one",
        ]);
        $fromPath = $this->writeFiles("Dashboard-0.2.0", [
            "src/Core/Store.js" => "the new one",
        ]);

        $this->assertTrue($this->copySource($this->library(tag: "v0.2.0"), $fromPath, $toPath));

        $this->assertFalse(Storage::fileExists($toPath, "Components/Common/Gone.jsx"));
        $this->assertSame("the new one", Storage::readFile($toPath, "Core/Store.js"));
    }

    public function testAnArchiveWithoutTheSourceIsNotCopied(): void {
        $fromPath = $this->writeFiles("Dashboard-0.1.0", [ "lib/Core/Store.js" => "store" ]);
        $toPath   = $this->writeFiles("client/src/Dashboard", [ "Core/Store.js" => "the old one" ]);

        $this->assertFalse($this->copySource($this->library(), $fromPath, $toPath));

        // Nothing was taken, so what was there is what is there
        $this->assertSame("the old one", Storage::readFile($toPath, "Core/Store.js"));
    }



    /**
     * A name that is asked for against the Libraries the App has
     * @param string $name
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerPlaceByName")]
    public function testANameThatIsNotThere(string $name, string $expected): void {
        // The one that is there would be downloaded, so only the misses are asked for
        $composerWas = $this->swapComposer(new ComposerData(libraries: [
            "dashboard" => $this->library(),
        ]));

        try {
            $this->expectOutputString($expected);
            Library::placeByName($name);
        } finally {
            $this->swapComposer($composerWas);
        }
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerPlaceByName(): array {
        return [
            "one nobody copied" => [ "editor", "There is no Library called editor\n" ],
            "another case"      => [ "Dashboard", "There is no Library called Dashboard\n" ],
        ];
    }

    public function testAnAppWithNoLibraries(): void {
        // Nothing to ask about, so it is not asked
        $composerWas = $this->swapComposer(new ComposerData());

        try {
            $this->expectOutputString("There are no Libraries to place\n");
            Library::placeByName();
        } finally {
            $this->swapComposer($composerWas);
        }
    }




    /**
     * The Url the archive of a tag or a branch is asked for at
     * @param LibraryData $library
     * @param string      $expected
     * @return void
     */
    #[DataProvider("providerArchiveUrl")]
    public function testTheArchiveUrl(LibraryData $library, string $expected): void {
        $this->assertSame($expected, $library->getArchiveUrl());
    }

    /**
     * The tag is written whole, so it carries its own v, and a branch is not a tag
     * @return array<string,array{LibraryData,string}>
     */
    public static function providerArchiveUrl(): array {
        $url = "https://github.com/FrameworkDevAR/Dashboard";

        return [
            "a tag"            => [
                new LibraryData("dashboard", "v1.2.0", "", $url),
                "$url/archive/refs/tags/v1.2.0.zip",
            ],
            "a tag with no v"  => [
                new LibraryData("dashboard", "1.2.0", "", $url),
                "$url/archive/refs/tags/1.2.0.zip",
            ],
            "a branch"         => [
                new LibraryData("dashboard", "", "dev", $url),
                "$url/archive/refs/heads/dev.zip",
            ],
            "a branch of one"  => [
                new LibraryData("dashboard", "", "feature/thing", $url),
                "$url/archive/refs/heads/feature/thing.zip",
            ],
            "the tag wins"     => [
                new LibraryData("dashboard", "v1.2.0", "dev", $url),
                "$url/archive/refs/tags/v1.2.0.zip",
            ],
            "one with a slash" => [
                new LibraryData("dashboard", "v1.2.0", "", "$url/"),
                "$url/archive/refs/tags/v1.2.0.zip",
            ],
            "neither of them"  => [ new LibraryData("dashboard", "", "", $url), "" ],
            "no url"           => [ new LibraryData("dashboard", "v1.2.0"), "" ],
        ];
    }

    /**
     * The name a Library is given while it is downloaded, which has to be a directory
     * @param LibraryData $library
     * @param string      $expected
     * @return void
     */
    #[DataProvider("providerFileName")]
    public function testTheFileName(LibraryData $library, string $expected): void {
        $this->assertSame($expected, $library->getFileName());
    }

    /**
     * @return array<string,array{LibraryData,string}>
     */
    public static function providerFileName(): array {
        return [
            "a tag"           => [ new LibraryData("dashboard", "v1.2.0"), "dashboard-v1.2.0" ],
            "a branch"        => [ new LibraryData("dashboard", "", "dev"), "dashboard-dev" ],
            // A branch is written with slashes as often as not, and a path is not
            "a branch of one" => [ new LibraryData("dashboard", "", "a/b"), "dashboard-a-b" ],
        ];
    }

    /**
     * What a Library needs before anything is downloaded for it
     * @param LibraryData $library
     * @param bool        $expected
     * @return void
     */
    #[DataProvider("providerIsValid")]
    public function testIsValid(LibraryData $library, bool $expected): void {
        $this->assertSame($expected, $library->isValid());
    }

    /**
     * The source is the one part a Library can do without, which takes all of it
     * @return array<string,array{LibraryData,bool}>
     */
    public static function providerIsValid(): array {
        $url = "https://github.com/FrameworkDevAR/Dashboard";

        return [
            "a tag"        => [ new LibraryData("d", "v1.2.0", "", $url, "src", "client"), true ],
            "a branch"     => [ new LibraryData("d", "", "dev", $url, "src", "client"), true ],
            "no source"    => [ new LibraryData("d", "v1.2.0", "", $url, "", "client"), true ],
            "no name"      => [ new LibraryData("", "v1.2.0", "", $url, "src", "client"), false ],
            "no reference" => [ new LibraryData("d", "", "", $url, "src", "client"), false ],
            "no url"       => [ new LibraryData("d", "v1.2.0", "", "", "src", "client"), false ],
            "no path"      => [ new LibraryData("d", "v1.2.0", "", $url, "src", ""), false ],
            "nothing"      => [ new LibraryData(), false ],
        ];
    }
}
