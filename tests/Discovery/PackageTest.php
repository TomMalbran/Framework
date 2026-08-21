<?php
namespace Tests\Discovery;

use Framework\Discovery\Composer;
use Framework\Discovery\Package;
use Framework\Discovery\Type\ComposerData;
use Framework\File\Storage;
use Framework\Utils\JSON;

use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Framework Package and the Composer file behind it
 */
class PackageTest extends TestCase {
    use TestHelpers;

    /**
     * Returns the Framework's own composer data
     * @return array<string,mixed>
     */
    private function composerData(): array {
        return JSON::readFile(Package::getBasePath(), "composer.json");
    }



    /**
     * A path built from the base of the repository
     * @param list<string> $pathParts
     * @param string       $expected
     * @return void
     */
    #[DataProvider("providerBasePath")]
    public function testThePathPartsAreJoinedToTheBasePath(array $pathParts, string $expected): void {
        $basePath = Package::getBasePath();

        $this->assertSame($basePath . $expected, Package::getBasePath(...$pathParts));
    }

    /**
     * @return array<string,array{list<string>,string}>
     */
    public static function providerBasePath(): array {
        return [
            "the base itself" => [ [], "" ],
            "one part"        => [ [ "src" ], "/src" ],
            "two parts"       => [ [ "src", "Discovery" ], "/src/Discovery" ],
            "a file"          => [ [ "composer.json" ], "/composer.json" ],
            "a nested file"   => [ [ "src", "Discovery", "Package.php" ], "/src/Discovery/Package.php" ],
        ];
    }

    /**
     * Something the repository is known to hold
     * @param string $path
     * @param bool   $isDirectory
     * @return void
     */
    #[DataProvider("providerContents")]
    public function testTheBasePathIsTheRootOfTheRepository(string $path, bool $isDirectory): void {
        $full = Package::getBasePath($path);

        if ($isDirectory) {
            $this->assertDirectoryExists($full);
        } else {
            $this->assertFileExists($full);
        }
    }

    /**
     * @return array<string,array{string,bool}>
     */
    public static function providerContents(): array {
        return [
            "the composer file" => [ "composer.json", false ],
            "the source"        => [ "src", true ],
            "the discovery"     => [ "src/Discovery", true ],
            "the config"        => [ Package::ConfigDir, true ],
            "the documentation" => [ Package::DocsDir, true ],
        ];
    }

    public function testTheSourcePathIsTheDirectoryComposerAutoloads(): void {
        $this->assertSame(
            Package::getBasePath(Package::getSourceDir()),
            Package::getSourcePath(),
        );
        $this->assertFileExists(Package::getSourcePath("Discovery", "Package.php"));
    }

    public function testTheBuildPathIsWhereTheGeneratedClassesGo(): void {
        $this->assertSame(
            Package::getSourcePath(Package::SystemDir),
            Package::getBuildPath(),
        );
    }

    public function testTheVersionAndTheSourceDirComeFromTheComposerFile(): void {
        $psr = $this->composerData()["autoload"]["psr-4"];

        $this->assertSame($this->composerData()["version"], Package::getVersion());
        $this->assertSame($psr["Framework\\"], Package::getSourceDir());
    }

    public function testTheFrameworkIsItsOwnApplicationHere(): void {
        // Which is why the tests can walk its classes without an app around it
        $this->assertTrue(Package::isFramework());
    }

    public function testTheComposerFileIsReadOnceAndKept(): void {
        // Forgotten, so the next call has to go back to the file for it
        $this->setPrivateStaticProperty(Package::class, "composer", null);

        $this->assertSame($this->composerData()["version"], Package::getVersion());
        $this->assertSame("src", Package::getSourceDir());

        // And the second call takes what the first one kept
        $kept = Package::getComposer();
        $this->setPrivateStaticProperty(Package::class, "composer", new ComposerData(version: "kept"));
        $this->assertSame("kept", Package::getVersion());
        $this->setPrivateStaticProperty(Package::class, "composer", $kept);
    }



    /**
     * What the Composer reader makes of a directory
     * @param string $path
     * @param string $key
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerComposer")]
    public function testTheComposerFileIsRead(string $path, callable $read, string $expected): void {
        $composer = Composer::readFile(Package::getBasePath($path));

        $this->assertSame($expected, $read($composer));
    }

    /**
     * The name is "vendor/package", and the vendor is taken as the project name.
     * A directory holding no composer file falls back to the defaults.
     * @return array<string,array{string,callable,string}>
     */
    public static function providerComposer(): array {
        $version = JSON::readFile(Package::getBasePath(), "composer.json")["version"];

        $name      = fn (ComposerData $composer): string => $composer->name;
        $theVersion = fn (ComposerData $composer): string => $composer->version;
        $namespace = fn (ComposerData $composer): string => $composer->namespace;
        $sourceDir = fn (ComposerData $composer): string => $composer->sourceDir;

        return [
            "the name"          => [ "", $name, "Frameworkdevar" ],
            "the version"       => [ "", $theVersion, $version ],
            "the namespace"     => [ "", $namespace, "Framework\\" ],
            "the source dir"    => [ "", $sourceDir, "src" ],
            "no name"           => [ "data", $name, "" ],
            "a default version" => [ "data", $theVersion, "0.1.0" ],
            "no namespace"      => [ "data", $namespace, "" ],
            "no source dir"     => [ "data", $sourceDir, "" ],
        ];
    }



    /**
     * Builds a directory holding the given composer file, and returns its path
     * @param string $contents
     * @return string
     */
    private function makeComposer(string $contents): string {
        $basePath = sys_get_temp_dir() . "/composerTest" . getmypid();
        Storage::deleteDir($basePath);
        Storage::createDir($basePath);
        Storage::writeFile("$basePath/composer.json", $contents);
        return $basePath;
    }

    /**
     * The Versions a project records for the Libraries copied into its source
     * @param string               $contents
     * @param array<string,string> $expected
     * @return void
     */
    #[DataProvider("providerLibraries")]
    public function testTheLibrariesAreRead(string $contents, array $expected): void {
        $basePath = $this->makeComposer($contents);

        $composer = Composer::readFile($basePath);

        $this->assertSame($expected, $composer->libraries);
        Storage::deleteDir($basePath);
    }

    /**
     * The map lives under the extra key, which composer leaves to whoever writes it
     * @return array<string,array{string,array<string,string>}>
     */
    public static function providerLibraries(): array {
        return [
            "one library"        => [
                '{ "extra": { "libraries": { "dashboard": "1.2.0" } } }',
                [ "dashboard" => "1.2.0" ],
            ],
            "every library"      => [
                '{ "extra": { "libraries": { "dashboard": "1.2.0", "editor": "2.0.1" } } }',
                [ "dashboard" => "1.2.0", "editor" => "2.0.1" ],
            ],
            "nothing under it"   => [ '{ "extra": { "libraries": {} } }', [] ],
            "no libraries"       => [ '{ "extra": { "branch-alias": {} } }', [] ],
            "no extra"           => [ '{ "name": "app/server" }', [] ],
            // Whatever wrote it is not composer, so the shape is not to be trusted
            "a list, not a map"  => [ '{ "extra": { "libraries": [ "dashboard" ] } }', [] ],
            "not even a map"     => [ '{ "extra": { "libraries": "1.2.0" } }', [] ],
            "a version as a map" => [ '{ "extra": { "libraries": { "dashboard": {} } } }', [] ],
            "a number version"   => [ '{ "extra": { "libraries": { "dashboard": 2 } } }', [ "dashboard" => "2" ] ],
        ];
    }

    public function testTheFrameworkRecordsNoLibraries(): void {
        // It is the library, so nothing is copied into it
        $this->assertSame([], Composer::readFile(Package::getBasePath())->libraries);
    }
}
