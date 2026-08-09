<?php
namespace Tests\Discovery;

use Framework\Discovery\Composer;
use Framework\Discovery\Package;
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
        $this->setPrivateStaticProperty(Package::class, "loaded", false);
        $this->setPrivateStaticProperty(Package::class, "version", "");
        $this->setPrivateStaticProperty(Package::class, "sourceDir", "");

        $this->assertSame($this->composerData()["version"], Package::getVersion());
        $this->assertSame("src", Package::getSourceDir());

        // And the second call takes what the first one kept
        $this->setPrivateStaticProperty(Package::class, "version", "kept");
        $this->assertSame("kept", Package::getVersion());
        $this->setPrivateStaticProperty(Package::class, "version", $this->composerData()["version"]);
    }



    /**
     * What the Composer reader makes of a directory
     * @param string $path
     * @param string $key
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerComposer")]
    public function testTheComposerFileIsRead(string $path, string $key, string $expected): void {
        $composer = Composer::readFile(Package::getBasePath($path));

        $this->assertSame($expected, $composer[$key]);
    }

    /**
     * The name is "vendor/package", and the vendor is taken as the project name.
     * A directory holding no composer file falls back to the defaults.
     * @return array<string,array{string,string,string}>
     */
    public static function providerComposer(): array {
        $version = JSON::readFile(Package::getBasePath(), "composer.json")["version"];

        return [
            "the name"          => [ "", "name", "Frameworkdevar" ],
            "the version"       => [ "", "version", $version ],
            "the namespace"     => [ "", "namespace", "Framework\\" ],
            "the source dir"    => [ "", "sourceDir", "src" ],
            "no name"           => [ "data", "name", "" ],
            "a default version" => [ "data", "version", "0.1.0" ],
            "no namespace"      => [ "data", "namespace", "" ],
            "no source dir"     => [ "data", "sourceDir", "" ],
        ];
    }
}
