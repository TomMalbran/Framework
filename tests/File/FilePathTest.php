<?php
namespace Tests\File;

use Framework\Application;
use Framework\Builder\Builder;
use Framework\Discovery\Package;
use Framework\File\Storage;
use Framework\File\FilePath;
use Framework\System\Config;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use ParseError;

class FilePathTest extends TestCase {
    use TestHelpers;

    private string $tmpDir = "";


    protected function setUp(): void {
        // reset internal static state before each test
        $this->setPrivateStaticProperty(FilePath::class, "paths", []);
        $this->setPrivateStaticProperty(FilePath::class, "directories", []);

        $_SERVER = [];
    }

    protected function tearDown(): void {
        // clear any globals we modified
        $_SERVER = [];

        if ($this->tmpDir !== "" && file_exists($this->tmpDir)) {
            @rmdir($this->tmpDir);
        }

        Storage::deleteDir(FilePath::getPath());
    }


    #[DataProvider("providerRegister")]
    public function testRegister(string $input, bool $expected): void {
        FilePath::register($input);
        $paths = $this->getPrivateStaticProperty(FilePath::class, "paths");
        if ($expected) {
            $this->assertContains($input, $paths);
        } else {
            $this->assertNotContains($input, $paths);
        }
    }

    public static function providerRegister(): array {
        return [
            "valid"   => [ "test1", true ],
            "invalid" => [ "", false ],
            "example" => [ "example", false ],
        ];
    }


    #[DataProvider("providerRegisterDirectory")]
    public function testRegisterDirectory(string $input, bool $expected): void {
        FilePath::registerDirectory($input);
        $directories = $this->getPrivateStaticProperty(FilePath::class, "directories");
        if ($expected) {
            $this->assertContains($input, $directories);
        } else {
            $this->assertNotContains($input, $directories);
        }
    }

    public static function providerRegisterDirectory(): array {
        return [
            "valid"   => [ "testDir1", true ],
            "invalid" => [ "", false ],
            "example" => [ "example", false ],
        ];
    }


    #[DataProvider("providerGetBasePath")]
    public function testGetBasePath(bool $forFramework, bool $forBackend, bool $forPrivate, string $ip, string $expected): void {
        $_SERVER["REMOTE_ADDR"] = $ip;
        $basePath = FilePath::getBasePath($forFramework, $forBackend, $forPrivate);
        $this->assertEquals($expected, $basePath);
    }

    public static function providerGetBasePath(): array {
        return [
            "framework"     => [ true, false, false, "", Package::getBasePath() ],
            "backend"       => [ false, true, false, "", Application::getBasePath() ],
            "normal"        => [ false, false, false, "", Application::getIndexPath() ],
            "private"       => [ false, false, true, "", Storage::getDirectory(Application::getIndexPath()) ],
            "private local" => [ false, false, true, "127.0.0.1", Application::getIndexPath() ],
        ];
    }


    #[DataProvider("providerGetPath")]
    public function testGetPath(array $pathParts, string $expectedEnd): void {
        $path     = FilePath::getPath(...$pathParts);
        $expected = Application::getIndexPath() . "/" . Config::getFileDir() .  $expectedEnd;
        $this->assertEquals($expected, $path);
    }

    public static function providerGetPath(): array {
        return [
            "empty"    => [ [], "" ],
            "single"   => [ [ "test" ], "/test" ],
            "multiple" => [ [ "test", "subdir", "file.txt" ], "/test/subdir/file.txt" ],
            "mixed"    => [ [ "test", 123, "file.txt" ], "/test/123/file.txt" ],
        ];
    }


    #[DataProvider("providerGetPrivatePath")]
    public function testPrivatePath(array $pathParts, string $expectedEnd): void {
        $path     = FilePath::getPrivatePath(...$pathParts);
        $expected = FilePath::getBasePath(forPrivate: true) . $expectedEnd;
        $this->assertEquals($expected, $path);
    }

    public static function providerGetPrivatePath(): array {
        return [
            "empty"    => [ [], "" ],
            "single"   => [ [ "test" ], "/test" ],
            "multiple" => [ [ "test", "subdir", "file.txt" ], "/test/subdir/file.txt" ],
            "mixed"    => [ [ "test", 123, "file.txt" ], "/test/123/file.txt" ],
        ];
    }


    #[DataProvider("providerGetFTPPath")]
    public function testFTPPath(array $pathParts, string $expectedEnd): void {
        $path     = FilePath::getFTPPath(...$pathParts);
        $expected = FilePath::getBasePath(forPrivate: true) . "/" . Config::getFileFtp() . $expectedEnd;
        $this->assertEquals($expected, $path);
    }

    public static function providerGetFTPPath(): array {
        return [
            "empty"    => [ [], "" ],
            "single"   => [ [ "test" ], "/test" ],
            "multiple" => [ [ "test", "subdir", "file.txt" ], "/test/subdir/file.txt" ],
            "mixed"    => [ [ "test", 123, "file.txt" ], "/test/123/file.txt" ],
        ];
    }


    #[DataProvider("providerGetDir")]
    public function testGetDir(array $pathParts, string $expectedEnd): void {
        $path     = FilePath::getDir(...$pathParts);
        $expected = Config::getFileDir() . $expectedEnd;
        $this->assertEquals($expected, $path);
    }

    public static function providerGetDir(): array {
        return [
            "empty"    => [ [], "" ],
            "single"   => [ [ "test" ], "/test" ],
            "multiple" => [ [ "test", "subdir", "file.txt" ], "/test/subdir/file.txt" ],
            "mixed"    => [ [ "test", 123, "file.txt" ], "/test/123/file.txt" ],
        ];
    }


    #[DataProvider("providerGetInternalDir")]
    public function testGetInternalDir(array $pathParts, string $expectedEnd): void {
        $path     = FilePath::getInternalDir(...$pathParts);
        $expected = Application::getBaseDir() . "/" . Config::getFileDir() . $expectedEnd;
        $this->assertEquals($expected, $path);
    }

    public static function providerGetInternalDir(): array {
        return [
            "empty"    => [ [], "" ],
            "single"   => [ [ "test" ], "/test" ],
            "multiple" => [ [ "test", "subdir", "file.txt" ], "/test/subdir/file.txt" ],
            "mixed"    => [ [ "test", 123, "file.txt" ], "/test/123/file.txt" ],
        ];
    }


    #[DataProvider("providerGetUrl")]
    public function testGetUrl(array $pathParts, string $expectedEnd): void {
        $url      = FilePath::getUrl(...$pathParts);
        $expected = Config::getFileUrl() . Config::getFileDir() . $expectedEnd;
        $this->assertEquals($expected, $url);
    }

    public static function providerGetUrl(): array {
        return [
            "empty"    => [ [], "" ],
            "single"   => [ [ "test" ], "/test" ],
            "multiple" => [ [ "test", "subdir", "file.txt" ], "/test/subdir/file.txt" ],
            "mixed"    => [ [ "test", 123, "file.txt" ], "/test/123/file.txt" ],
        ];
    }


    #[DataProvider("providerGetSystemTempPath")]
    public function testGetSystemTempPath(array $pathParts, string $expectedEnd): void {
        $path     = FilePath::getSystemTempPath(...$pathParts);
        $expected = Storage::parsePath(sys_get_temp_dir()) . $expectedEnd;
        $this->assertEquals($expected, $path);
    }

    public static function providerGetSystemTempPath(): array {
        return [
            "empty"    => [ [], "" ],
            "single"   => [ [ "test" ], "/test" ],
            "multiple" => [ [ "test", "subdir", "file.txt" ], "/test/subdir/file.txt" ],
            "mixed"    => [ [ "test", 123, "file.txt" ], "/test/123/file.txt" ],
        ];
    }


    #[DataProvider("providerGetTempPath")]
    public function testGetTempPath(int $id, bool $create, bool $expectEmpty = false): void {
        $path = FilePath::getTempPath($id, $create);
        if ($expectEmpty) {
            $this->assertEmpty($path);
            return;
        }

        $this->tmpDir = $path;
        if ($create) {
            $this->assertDirectoryExists($this->tmpDir);
            $this->assertStringContainsString((string)$id, $this->tmpDir);
        } else {
            $this->assertIsString($this->tmpDir);
        }
    }

    public static function providerGetTempPath(): array {
        return [
            "zero"            => [ 0, true, true ],
            "negative"        => [ -1, true, true ],
            "valid create"    => [ 888, true, false ],
            "valid no create" => [ 999, false, false ],
        ];
    }


    #[DataProvider("providerGetTempUrl")]
    public function testGetTempUrl(int $id, array $pathParts, string $expectedEnd, bool $expectEmpty = false): void {
        $path = FilePath::getTempUrl($id, ...$pathParts);
        if ($expectEmpty) {
            $this->assertEmpty($path);
        } else {
            $expected = Config::getFileUrl() . "temp" . $expectedEnd;
            $this->assertEquals($expected, $path);
        }
    }

    public static function providerGetTempUrl(): array {
        return [
            "zero"     => [ 0, [], "", true ],
            "negative" => [ -1, [], "", true ],
            "empty"    => [ 777, [], "/777" ],
            "single"   => [ 888, [ "file.txt" ], "/888/file.txt" ],
            "multiple" => [ 999, [ "subdir", "file.txt" ], "/999/subdir/file.txt" ],
        ];
    }


    #[DataProvider("providerCollectPaths")]
    public function testCollectPaths(array $register, array $expected): void {
        foreach ($register as $name) {
            FilePath::register($name);
        }
        $this->assertSame($expected, FilePath::collectPaths());
    }

    public static function providerCollectPaths(): array {
        $base = [
            [ "name" => "source",  "title" => "Source" ],
            [ "name" => "thumbs",  "title" => "Thumbs" ],
            [ "name" => "avatars", "title" => "Avatars" ],
        ];
        return [
            "defaults"        => [ [], $base ],
            "with_registered" => [ [ "docs" ], [ ...$base, [ "name" => "docs", "title" => "Docs" ] ] ],
            "skips_invalid"   => [ [ "", "example" ], $base ],
        ];
    }


    public function testDestroyCode(): void {
        $this->assertSame(1, FilePath::destroyCode());
    }


    #[DataProvider("providerCreateDirs")]
    public function testCreateDirs(array $directories, int $id, array $expected): void {
        foreach ($directories as $directory) {
            FilePath::registerDirectory($directory);
        }

        $this->assertSame($expected, FilePath::createDirs($id));
        foreach ($expected as $path) {
            $this->assertDirectoryExists(FilePath::getPath($path));
        }
    }

    public static function providerCreateDirs(): array {
        return [
            "default id"              => [
                [],
                0,
                [ "source", "source/0", "thumbs", "thumbs/0" ],
            ],
            "with id and directories" => [
                [ "images" ],
                5,
                [ "source", "source/5", "source/5/images", "thumbs", "thumbs/5", "thumbs/5/images" ],
            ],
        ];
    }


    public function testCreateDirsIsIdempotent(): void {
        $this->assertNotEmpty(FilePath::createDirs(7));
        $this->assertSame([], FilePath::createDirs(7));
    }


    public function testEnsurePaths(): void {
        FilePath::register("docs");
        FilePath::registerDirectory("images");

        ob_start();
        FilePath::ensurePaths();
        $output = ob_get_clean();

        $this->assertStringContainsString("ENSURE PATHS", $output);
        $this->assertStringContainsString("Added", $output);

        foreach ([ "temp", "source", "thumbs", "avatars", "docs" ] as $basePath) {
            $this->assertDirectoryExists(FilePath::getPath($basePath));
        }
        $this->assertDirectoryExists(FilePath::getPath("source/0/images"));
    }


    public function testEnsurePathsWhenAlreadyCreated(): void {
        ob_start();
        FilePath::ensurePaths();
        ob_end_clean();

        ob_start();
        FilePath::ensurePaths();
        $output = ob_get_clean();

        $this->assertStringContainsString("No paths added", $output);
    }


    public function testTheGeneratedCodeParses(): void {
        // The code is rendered from templates the build reads once, so without
        // them the file would be written empty over the one that is there
        $this->callPrivateStaticMethod(Builder::class, "loadTemplates");

        // It writes into the build directory, which is gitignored and holds
        // what ./framework build already put there
        ob_start();
        $written = FilePath::generateCode();
        ob_get_clean();

        $this->assertSame(1, $written);

        $code = Storage::readFile(Package::getBuildPath(), "Path.php");
        $this->assertStringContainsString("class Path", $code);

        // Every path it collected got a getter of its own
        foreach (FilePath::collectPaths() as $path) {
            $this->assertStringContainsString("function get{$path["title"]}Dir(", $code);
        }

        $error = null;
        try {
            token_get_all($code, TOKEN_PARSE);
        } catch (ParseError $e) {
            $error = $e->getMessage();
        }
        $this->assertNull($error, "the path code does not parse: $error");
    }
}
