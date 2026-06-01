<?php
namespace Tests\File;

use Framework\Application;
use Framework\Discovery\Package;
use Framework\File\Storage;
use Framework\File\FilePath;
use Framework\System\Config;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
            "private_local" => [ false, false, true, "127.0.0.1", Application::getIndexPath() ],
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
            "valid_create"    => [ 888, true, false ],
            "valid_no_create" => [ 999, false, false ],
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
}
