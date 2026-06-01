<?php
namespace Tests\File;

use Framework\File\Storage;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ZipArchive;

class StorageTest extends TestCase {
    use TestHelpers;

    private string $tmpDir = "";
    private string $browseRoot = "";
    private string $emptyRoot = "";
    private string $spaceFile = "";
    private string $plainFile = "";
    private string $existingDir = "";
    private string $brokenEmptyDir = "";
    private string $uploadSource = "";
    private string $fixtureZip = "";
    private string $brokenZipSource = "";


    protected function setUp(): void {
        $this->tmpDir          = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "file_test_" . uniqid();
        $this->browseRoot      = $this->tmpDir . DIRECTORY_SEPARATOR . "browse-root";
        $this->emptyRoot       = $this->tmpDir . DIRECTORY_SEPARATOR . "empty-root";
        $this->existingDir     = $this->tmpDir . DIRECTORY_SEPARATOR . "existing-dir";
        $this->brokenEmptyDir  = $this->tmpDir . DIRECTORY_SEPARATOR . "broken-empty-dir";
        $this->plainFile       = $this->tmpDir . DIRECTORY_SEPARATOR . "plain.txt";
        $this->spaceFile       = $this->tmpDir . DIRECTORY_SEPARATOR . "space file.txt";
        $this->uploadSource    = $this->tmpDir . DIRECTORY_SEPARATOR . "upload-source.txt";
        $this->fixtureZip      = $this->tmpDir . DIRECTORY_SEPARATOR . "fixture.zip";
        $this->brokenZipSource = $this->tmpDir . DIRECTORY_SEPARATOR . "zip-source-broken";

        @mkdir($this->tmpDir);

        @mkdir($this->browseRoot . DIRECTORY_SEPARATOR . "sub", 0777, true);
        @mkdir($this->browseRoot . DIRECTORY_SEPARATOR . "vendor", 0777, true);
        @mkdir($this->emptyRoot, 0777, true);
        @mkdir($this->existingDir, 0777, true);
        @mkdir($this->brokenEmptyDir . DIRECTORY_SEPARATOR . "nested", 0777, true);
        @mkdir($this->tmpDir . DIRECTORY_SEPARATOR . "delete-dir" . DIRECTORY_SEPARATOR . "nested", 0777, true);
        @mkdir($this->tmpDir . DIRECTORY_SEPARATOR . "empty-dir-target", 0777, true);
        @mkdir($this->tmpDir . DIRECTORY_SEPARATOR . "zip-source" . DIRECTORY_SEPARATOR . "nested", 0777, true);
        @mkdir($this->brokenZipSource, 0777, true);
        @mkdir($this->tmpDir . DIRECTORY_SEPARATOR . "from", 0777, true);
        @mkdir($this->tmpDir . DIRECTORY_SEPARATOR . "copy-target", 0777, true);

        @file_put_contents($this->plainFile, "plain");
        @file_put_contents($this->spaceFile, "spaced");
        @file_put_contents($this->uploadSource, "upload");
        @file_put_contents($this->browseRoot . DIRECTORY_SEPARATOR . "alpha.txt", "alpha");
        @file_put_contents($this->browseRoot . DIRECTORY_SEPARATOR . "sub" . DIRECTORY_SEPARATOR . "child.txt", "child");
        @file_put_contents($this->browseRoot . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "vendor.txt", "vendor");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "delete-file.txt", "delete");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "delete-dir" . DIRECTORY_SEPARATOR . "nested" . DIRECTORY_SEPARATOR . "remove.txt", "remove");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "empty-dir-target" . DIRECTORY_SEPARATOR . "one.txt", "one");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "empty-dir-target" . DIRECTORY_SEPARATOR . "two.txt", "two");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "zip-source" . DIRECTORY_SEPARATOR . "a.txt", "zip-a");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "zip-source" . DIRECTORY_SEPARATOR . "nested" . DIRECTORY_SEPARATOR . "b.txt", "zip-b");
        @file_put_contents($this->brokenZipSource . DIRECTORY_SEPARATOR . "a.txt", "zip-a");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "from" . DIRECTORY_SEPARATOR . "move-me.txt", "move");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "source-copy.txt", "copy");

        if (function_exists("symlink")) {
            @symlink($this->brokenZipSource . DIRECTORY_SEPARATOR . "missing-target", $this->brokenZipSource . DIRECTORY_SEPARATOR . "broken");
            @symlink($this->brokenEmptyDir . DIRECTORY_SEPARATOR . "nested" . DIRECTORY_SEPARATOR . "missing-target", $this->brokenEmptyDir . DIRECTORY_SEPARATOR . "nested" . DIRECTORY_SEPARATOR . "broken");
        }

        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($this->fixtureZip, ZipArchive::CREATE));
        $zip->addFromString("doc.txt", "doc");
        $zip->addFromString("folder/item.txt", "item");
        $zip->close();

        $GLOBALS["test_move_uploaded_file"] = false;
    }

    protected function tearDown(): void {
        if (is_link($this->brokenZipSource . DIRECTORY_SEPARATOR . "broken")) {
            @unlink($this->brokenZipSource . DIRECTORY_SEPARATOR . "broken");
        }
        if (is_link($this->brokenEmptyDir . DIRECTORY_SEPARATOR . "nested" . DIRECTORY_SEPARATOR . "broken")) {
            @unlink($this->brokenEmptyDir . DIRECTORY_SEPARATOR . "nested" . DIRECTORY_SEPARATOR . "broken");
        }

        Storage::deleteDir($this->tmpDir);
        unset($GLOBALS["test_move_uploaded_file"]);
    }

    /**
     * @param array<int,mixed> $value
     * @return array<int,mixed>
     */
    private function resolveTokens(array $value): array {
        $result = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $result[] = $this->resolveTokens($item);
            } elseif (is_string($item)) {
                $result[] = $this->resolveStringToken($item);
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    private function resolveStringToken(string $value): string {
        return str_replace(
            [
                "__TMP_DIR__",
                "__BROWSE_ROOT__",
                "__BROWSE_ROOT_WITH_SLASH__",
                "__EMPTY_ROOT__",
                "__EMPTY_ROOT_WITH_SLASH__",
                "__PLAIN_FILE__",
                "__SPACE_FILE_URL__",
                "__EXISTING_DIR__",
                "__UPLOAD_SOURCE__",
                "__FIXTURE_ZIP__",
                "__BROKEN_EMPTY_DIR__",
                "__BROKEN_ZIP_SOURCE__",
            ],
            [
                $this->tmpDir,
                $this->browseRoot,
                Storage::addLastSlash($this->browseRoot),
                $this->emptyRoot,
                Storage::addLastSlash($this->emptyRoot),
                $this->plainFile,
                "file://" . $this->spaceFile,
                $this->existingDir,
                $this->uploadSource,
                $this->fixtureZip,
                $this->brokenEmptyDir,
                $this->brokenZipSource,
            ],
            $value,
        );
    }



    #[DataProvider("providerGetDirectory")]
    public function testGetDirectory(string $path, int $levels, string $expected): void {
        $this->assertSame($expected, Storage::getDirectory($path, $levels));
    }

    public static function providerGetDirectory(): array {
        return [
            "single"   => [ "/tmp/demo/file.txt", 1, "/tmp/demo" ],
            "two"      => [ "/tmp/demo/sub/file.txt", 2, "/tmp/demo" ],
            "zero"     => [ "/tmp/demo/file.txt", 0, "/tmp/demo" ],
            "negative" => [ "/tmp/demo/file.txt", -1, "/tmp/demo" ],
            "empty"    => [ "", 1, "" ],
        ];
    }


    #[DataProvider("providerParsePath")]
    public function testParsePath(array $pathParts, string $expected): void {
        $this->assertSame($expected, Storage::parsePath(...$pathParts));
    }

    public static function providerParsePath(): array {
        return [
            "slashes" => [ [ "/tmp//demo/", "sub//", "file.txt" ], "/tmp/demo/sub/file.txt" ],
            "windows" => [ [ "C://temp//demo", "file.txt" ], "C://temp/demo/file.txt" ],
            "mixed"   => [ [ "/tmp", 12, "file.txt" ], "/tmp/12/file.txt" ],
            "empty"   => [ [], "" ],
        ];
    }


    #[DataProvider("providerParseUrl")]
    public function testParseUrl(array $pathParts, string $expected): void {
        $this->assertSame($expected, Storage::parseUrl(...$pathParts));
    }

    public static function providerParseUrl(): array {
        return [
            "http"  => [ [ "http://example.com//files/", "demo.txt" ], "http://example.com/files/demo.txt" ],
            "https" => [ [ "https://example.com//files/", "demo.txt" ], "https://example.com/files/demo.txt" ],
            "path"  => [ [ "/tmp//demo/", "demo.txt" ], "/tmp/demo/demo.txt" ],
            "empty" => [ [], "" ],
        ];
    }


    #[DataProvider("providerAddLastSlash")]
    public function testAddLastSlash(string $path, string $expected): void {
        $this->assertSame($expected, Storage::addLastSlash($path));
    }

    public static function providerAddLastSlash(): array {
        return [
            "missing" => [ "/tmp/demo", "/tmp/demo/" ],
            "present" => [ "/tmp/demo/", "/tmp/demo/" ],
            "empty"   => [ "", "" ],
        ];
    }


    #[DataProvider("providerAddFirstSlash")]
    public function testAddFirstSlash(string $path, string $expected): void {
        $this->assertSame($expected, Storage::addFirstSlash($path));
    }

    public static function providerAddFirstSlash(): array {
        return [
            "missing" => [ "tmp/demo", "/tmp/demo" ],
            "present" => [ "/tmp/demo", "/tmp/demo" ],
            "empty"   => [ "", "" ],
        ];
    }


    #[DataProvider("providerRemoveLastSlash")]
    public function testRemoveLastSlash(string $path, string $expected): void {
        $this->assertSame($expected, Storage::removeLastSlash($path));
    }

    public static function providerRemoveLastSlash(): array {
        return [
            "present" => [ "/tmp/demo/", "/tmp/demo" ],
            "missing" => [ "/tmp/demo", "/tmp/demo" ],
            "empty"   => [ "", "" ],
        ];
    }


    #[DataProvider("providerRemoveFirstSlash")]
    public function testRemoveFirstSlash(string $path, string $expected): void {
        $this->assertSame($expected, Storage::removeFirstSlash($path));
    }

    public static function providerRemoveFirstSlash(): array {
        return [
            "present" => [ "/tmp/demo", "tmp/demo" ],
            "missing" => [ "tmp/demo", "tmp/demo" ],
            "empty"   => [ "", "" ],
        ];
    }


    #[DataProvider("providerFileExists")]
    public function testFileExists(array $pathParts, bool $expected): void {
        $pathParts = $this->resolveTokens($pathParts);
        $this->assertSame($expected, Storage::fileExists(...$pathParts));
    }

    public static function providerFileExists(): array {
        return [
            "file"    => [ [ "__PLAIN_FILE__" ], true ],
            "nested"  => [ [ "__BROWSE_ROOT__", "sub", "child.txt" ], true ],
            "missing" => [ [ "__TMP_DIR__", "missing.txt" ], false ],
            "empty"   => [ [], false ],
        ];
    }


    #[DataProvider("providerGetModifiedTime")]
    public function testGetModifiedTime(array $pathParts, int|string $expected): void {
        $pathParts = $this->resolveTokens($pathParts);
        $time = Storage::getModifiedTime(...$pathParts);

        if ($expected === "current") {
            $this->assertSame(filemtime($this->plainFile), $time);
        } else {
            $this->assertSame($expected, $time);
        }
    }

    public static function providerGetModifiedTime(): array {
        return [
            "existing" => [ [ "__PLAIN_FILE__" ], "current" ],
            "missing"  => [ [ "__TMP_DIR__", "missing.txt" ], 0 ],
            "empty"    => [ [], 0 ],
        ];
    }


    #[DataProvider("providerReadFile")]
    public function testReadFile(array $pathParts, string $expected): void {
        $pathParts = $this->resolveTokens($pathParts);
        $this->assertSame($expected, Storage::readFile(...$pathParts));
    }

    public static function providerReadFile(): array {
        return [
            "plain"   => [ [ "__PLAIN_FILE__" ], "plain" ],
            "nested"  => [ [ "__BROWSE_ROOT__", "sub", "child.txt" ], "child" ],
            "missing" => [ [ "__TMP_DIR__", "missing.txt" ], "" ],
            "empty"   => [ [], "" ],
        ];
    }


    #[DataProvider("providerReadUrl")]
    public function testReadUrl(string $url, string $expected): void {
        $url = $this->resolveStringToken($url);
        $this->assertSame($expected, Storage::readUrl($url));
    }

    public static function providerReadUrl(): array {
        return [
            "spaced" => [ "__SPACE_FILE_URL__", "spaced" ],
            "empty"  => [ "", "" ],
        ];
    }


    #[DataProvider("providerUploadFile")]
    public function testUploadFile(string $path, string $fileName, string $tmpFile, bool $expected, ?string $expectedPath): void {
        $GLOBALS["test_move_uploaded_file"] = true;
        $path         = $this->resolveStringToken($path);
        $tmpFile      = $this->resolveStringToken($tmpFile);
        $expectedPath = $expectedPath !== null ? $this->resolveStringToken($expectedPath) : null;
        $result       = Storage::uploadFile($path, $fileName, $tmpFile);

        $this->assertSame($expected, $result);
        if ($expectedPath !== null) {
            $this->assertFileExists($expectedPath);
            $this->assertSame("upload", Storage::readFile($expectedPath));
        } else {
            $this->assertFalse(Storage::fileExists(Storage::parsePath($path, $fileName)));
        }
    }

    public static function providerUploadFile(): array {
        return [
            "valid"   => [ "__EXISTING_DIR__", "uploaded.txt", "__UPLOAD_SOURCE__", true, "__EXISTING_DIR__/uploaded.txt" ],
            "missing" => [ "__EXISTING_DIR__", "uploaded.txt", "__TMP_DIR__/missing-upload.txt", false, null ],
            "empty"   => [ "", "uploaded.txt", "__UPLOAD_SOURCE__", false, null ],
        ];
    }


    #[DataProvider("providerUploadPath")]
    public function testUploadPath(string $path, string $tmpFile, bool $expected, ?string $expectedContent): void {
        $GLOBALS["test_move_uploaded_file"] = true;
        $path    = $this->resolveStringToken($path);
        $tmpFile = $this->resolveStringToken($tmpFile);
        $result  = Storage::uploadPath($path, $tmpFile);

        $this->assertSame($expected, $result);
        if ($expectedContent !== null) {
            $this->assertSame($expectedContent, Storage::readFile($path));
        } else {
            $this->assertFalse(Storage::fileExists($path));
        }
    }

    public static function providerUploadPath(): array {
        return [
            "valid"   => [ "__EXISTING_DIR__/uploaded-path.txt", "__UPLOAD_SOURCE__", true, "upload" ],
            "missing" => [ "__EXISTING_DIR__/uploaded-path.txt", "__TMP_DIR__/missing-upload.txt", false, null ],
            "empty"   => [ "", "__UPLOAD_SOURCE__", false, null ],
        ];
    }


    #[DataProvider("providerCreateFile")]
    public function testCreateFile(string $path, string $fileName, array|string $content, bool $createDir, bool $expected, ?string $expectedPath, ?string $expectedContent): void {
        $path         = $this->resolveStringToken($path);
        $expectedPath = $expectedPath !== null ? $this->resolveStringToken($expectedPath) : null;
        $result       = $this->runWithSuppressedWarnings(
            fn() => Storage::createFile($path, $fileName, $content, $createDir),
            suppress: !$expected,
        );

        $this->assertSame($expected, $result);
        if ($expectedPath !== null) {
            $this->assertSame($expectedContent, Storage::readFile($expectedPath));
        } else {
            $this->assertFalse(Storage::fileExists(Storage::parsePath($path, $fileName)));
        }
    }

    public static function providerCreateFile(): array {
        return [
            "string" => [ "__EXISTING_DIR__", "created.txt", "hello", false, true, "__EXISTING_DIR__/created.txt", "hello" ],
            "list"   => [ "__TMP_DIR__/new-dir", "created.txt", [ "a", "b" ], true, true, "__TMP_DIR__/new-dir/created.txt", "a\nb" ],
            "empty"  => [ "", "", "hello", false, false, null, null ],
        ];
    }


    #[DataProvider("providerWriteFile")]
    public function testWriteFile(string $path, string $content, bool $expected): void {
        $path   = $this->resolveStringToken($path);
        $result = $this->runWithSuppressedWarnings(
            fn() => Storage::writeFile($path, $content),
            suppress: !$expected,
        );

        $this->assertSame($expected, $result);
        if ($expected) {
            $this->assertSame($content, Storage::readFile($path));
        } else {
            $this->assertFalse(Storage::fileExists($path));
        }
    }

    public static function providerWriteFile(): array {
        return [
            "existing" => [ "__EXISTING_DIR__/written.txt", "written", true ],
            "missing"  => [ "__TMP_DIR__/missing-dir/written.txt", "written", false ],
        ];
    }


    #[DataProvider("providerWriteFromUrl")]
    public function testWriteFromUrl(string $path, string $url, bool $expected, ?string $expectedContent): void {
        $path   = $this->resolveStringToken($path);
        $url    = $this->resolveStringToken($url);
        $result = Storage::writeFromUrl($path, $url);

        $this->assertSame($expected, $result);
        if ($expectedContent !== null) {
            $this->assertSame($expectedContent, Storage::readFile($path));
            return;
        }

        $this->assertFalse(Storage::fileExists($path));
    }

    public static function providerWriteFromUrl(): array {
        return [
            "valid" => [ "__EXISTING_DIR__/copied.txt", "__SPACE_FILE_URL__", true, "spaced" ],
            "empty" => [ "__EXISTING_DIR__/copied.txt", "", false, null ],
        ];
    }


    #[DataProvider("providerMoveFile")]
    public function testMoveFile(string $fromPath, string $toPath, bool $expected, ?string $expectedContent): void {
        $fromPath = $this->resolveStringToken($fromPath);
        $toPath   = $this->resolveStringToken($toPath);
        $result   = $this->runWithSuppressedWarnings(
            fn() => Storage::moveFile($fromPath, $toPath),
            suppress: !$expected,
        );

        $this->assertSame($expected, $result);
        if ($expectedContent !== null) {
            $this->assertFalse(Storage::fileExists($fromPath));
            $this->assertSame($expectedContent, Storage::readFile($toPath));
        } else {
            $this->assertFalse(Storage::fileExists($toPath));
        }
    }

    public static function providerMoveFile(): array {
        return [
            "valid"      => [ "__TMP_DIR__/from/move-me.txt", "__TMP_DIR__/copy-target/moved.txt", true, "move" ],
            "empty_from" => [ "", "__TMP_DIR__/copy-target/moved.txt", false, null ],
            "empty_to"   => [ "__TMP_DIR__/from/move-me.txt", "", false, null ],
        ];
    }


    #[DataProvider("providerCopyFile")]
    public function testCopyFile(string $fromPath, string $toPath, bool $expected, ?string $expectedContent): void {
        $fromPath = $this->resolveStringToken($fromPath);
        $toPath   = $this->resolveStringToken($toPath);
        $result   = $this->runWithSuppressedWarnings(
            fn() => Storage::copyFile($fromPath, $toPath),
            suppress: !$expected,
        );

        $this->assertSame($expected, $result);
        if ($expectedContent !== null) {
            $this->assertSame($expectedContent, Storage::readFile($fromPath));
            $this->assertSame($expectedContent, Storage::readFile($toPath));
        } else {
            $this->assertFalse(Storage::fileExists($toPath));
        }
    }

    public static function providerCopyFile(): array {
        return [
            "valid"      => [ "__TMP_DIR__/source-copy.txt", "__TMP_DIR__/copy-target/copied.txt", true, "copy" ],
            "empty_from" => [ "", "__TMP_DIR__/copy-target/copied.txt", false, null ],
            "empty_to"   => [ "__TMP_DIR__/source-copy.txt", "", false, null ],
        ];
    }


    #[DataProvider("providerDeleteFile")]
    public function testDeleteFile(string $path, string $name, bool $expected): void {
        $path     = $this->resolveStringToken($path);
        $fullPath = Storage::parsePath($path, $name);
        $result   = Storage::deleteFile($path, $name);

        $this->assertSame($expected, $result);
        $this->assertFalse(Storage::fileExists($fullPath));
    }

    public static function providerDeleteFile(): array {
        return [
            "file"      => [ "__TMP_DIR__", "delete-file.txt", true ],
            "full_path" => [ "__PLAIN_FILE__", "", true ],
            "missing"   => [ "__TMP_DIR__", "missing.txt", false ],
            "empty"     => [ "", "", false ],
        ];
    }


    #[DataProvider("providerGetBaseName")]
    public function testGetBaseName(string $path, string $expected): void {
        $this->assertSame($expected, Storage::getBaseName($path));
    }

    public static function providerGetBaseName(): array {
        return [
            "path"  => [ "/tmp/demo/file.txt", "file.txt" ],
            "url"   => [ "https://example.com/demo/file.txt", "file.txt" ],
            "empty" => [ "", "" ],
        ];
    }


    #[DataProvider("providerGetFileName")]
    public function testGetFileName(string $name, string $expected): void {
        $this->assertSame($expected, Storage::getFileName($name));
    }

    public static function providerGetFileName(): array {
        return [
            "simple"   => [ "file.txt", "file" ],
            "multiple" => [ "archive.tar.gz", "archive.tar" ],
            "no_ext"   => [ "readme", "readme" ],
            "empty"    => [ "", "" ],
        ];
    }


    #[DataProvider("providerGetExtension")]
    public function testGetExtension(string $name, string $expected): void {
        $this->assertSame($expected, Storage::getExtension($name));
    }

    public static function providerGetExtension(): array {
        return [
            "simple" => [ "file.txt", "txt" ],
            "query"  => [ "file.pdf?version=2", "pdf" ],
            "none"   => [ "readme", "" ],
            "empty"  => [ "", "" ],
        ];
    }


    #[DataProvider("providerHasExtension")]
    public function testHasExtension(string $name, array|string $extensions, array $otherExtensions, bool $expected): void {
        $this->assertSame($expected, Storage::hasExtension($name, $extensions, ...$otherExtensions));
    }

    public static function providerHasExtension(): array {
        return [
            "string"       => [ "file.txt", "txt", [], true ],
            "query"        => [ "file.pdf?version=2", "pdf", [], true ],
            "array"        => [ "file.pdf", [ "txt", "pdf" ], [], true ],
            "variadic"     => [ "file.jpg", "png", [ "jpg", "gif" ], true ],
            "missing"      => [ "file.txt", [ "pdf", "doc" ], [], false ],
            "no_ext"       => [ "readme", "txt", [], false ],
            "empty_ext"    => [ "file.txt", "", [], false ],
            "empty_no_ext" => [ "file", "", [], false ],
            "empty_name"   => [ "", "txt", [], false ],
        ];
    }


    #[DataProvider("providerParseName")]
    public function testParseName(string $newName, string $oldName, string $expected): void {
        $this->assertSame($expected, Storage::parseName($newName, $oldName));
    }

    public static function providerParseName(): array {
        return [
            "keep_ext"   => [ "report.pdf", "old.txt", "report.pdf" ],
            "inherit"    => [ "report", "old.txt", "report.txt" ],
            "no_old_ext" => [ "report", "old", "report" ],
            "empty_new"  => [ "", "old.txt", "old.txt" ],
            "empty_old"  => [ "report", "", "report" ],
            "both_empty" => [ "", "", "" ],
        ];
    }


    #[DataProvider("providerGetAllInDir")]
    public function testGetAllInDir(string $path, array $expected): void {
        $path     = $this->resolveStringToken($path);
        $expected = $this->resolveTokens($expected);
        $this->assertSame($expected, Storage::getAllInDir($path));
    }

    public static function providerGetAllInDir(): array {
        return [
            "browse_root" => [
                "__BROWSE_ROOT__",
                [
                    "__BROWSE_ROOT__/alpha.txt",
                    "__BROWSE_ROOT__/sub",
                    "__BROWSE_ROOT__/vendor",
                ],
            ],
            "missing" => [ "__TMP_DIR__/missing-dir", [] ],
            "empty"   => [ "", [] ],
        ];
    }


    #[DataProvider("providerGetDirectoriesInDir")]
    public function testGetDirectoriesInDir(string $path, array $expected): void {
        $path     = $this->resolveStringToken($path);
        $expected = $this->resolveTokens($expected);
        $this->assertSame($expected, Storage::getDirectoriesInDir($path));
    }

    public static function providerGetDirectoriesInDir(): array {
        return [
            "browse_root" => [
                "__BROWSE_ROOT__",
                [
                    "__BROWSE_ROOT__/sub",
                    "__BROWSE_ROOT__/vendor",
                ],
            ],
            "missing" => [ "__TMP_DIR__/missing-dir", [] ],
            "empty"   => [ "", [] ],
        ];
    }


    #[DataProvider("providerGetFilesInDir")]
    public function testGetFilesInDir(string $path, bool $recursive, bool $skipVendor, array $expected): void {
        $path     = $this->resolveStringToken($path);
        $expected = $this->resolveTokens($expected);
        $this->assertSame($expected, Storage::getFilesInDir($path, $recursive, $skipVendor));
    }

    public static function providerGetFilesInDir(): array {
        return [
            "normal"      => [ "__BROWSE_ROOT__", false, false, [ "alpha.txt", "sub", "vendor" ] ],
            "recursive"   => [
                "__BROWSE_ROOT__",
                true,
                false,
                [
                    "__BROWSE_ROOT__/alpha.txt",
                    "__BROWSE_ROOT__/sub/child.txt",
                    "__BROWSE_ROOT__/vendor/vendor.txt",
                ],
            ],
            "skip_vendor" => [
                "__BROWSE_ROOT__",
                true,
                true,
                [
                    "__BROWSE_ROOT__/alpha.txt",
                    "__BROWSE_ROOT__/sub/child.txt",
                ],
            ],
            "file"        => [ "__PLAIN_FILE__", false, false, [ "__PLAIN_FILE__" ] ],
            "empty"       => [ "", false, false, [] ],
        ];
    }


    #[DataProvider("providerGetFirstFileInDir")]
    public function testGetFirstFileInDir(string $path, string $expected): void {
        $path     = $this->resolveStringToken($path);
        $expected = $this->resolveStringToken($expected);
        $this->assertSame($expected, Storage::getFirstFileInDir($path));
    }

    public static function providerGetFirstFileInDir(): array {
        return [
            "browse_root" => [ "__BROWSE_ROOT_WITH_SLASH__", "__BROWSE_ROOT_WITH_SLASH__alpha.txt" ],
            "empty_dir"   => [ "__EMPTY_ROOT_WITH_SLASH__", "" ],
            "empty_path"  => [ "", "" ],
        ];
    }


    #[DataProvider("providerCreateDir")]
    public function testCreateDir(string $path, bool $expected, bool $directoryShouldExist): void {
        $path   = $this->resolveStringToken($path);
        $result = Storage::createDir($path);

        $this->assertSame($expected, $result);
        if ($directoryShouldExist) {
            $this->assertDirectoryExists($path);
        }
    }

    public static function providerCreateDir(): array {
        return [
            "new"      => [ "__TMP_DIR__/new-dir", true, true ],
            "existing" => [ "__EXISTING_DIR__", false, false ],
            "empty"    => [ "", false, false ],
        ];
    }


    #[DataProvider("providerEnsureDir")]
    public function testEnsureDir(string $basePath, array $pathParts, string $expectedPath, array $expectedDirectories): void {
        $basePath            = $this->resolveStringToken($basePath);
        $expectedPath        = $this->resolveStringToken($expectedPath);
        $expectedDirectories = $this->resolveTokens($expectedDirectories);
        $path = Storage::ensureDir($basePath, ...$pathParts);

        $this->assertSame($expectedPath, $path);

        foreach ($expectedDirectories as $directory) {
            $this->assertDirectoryExists($directory);
        }
    }

    public static function providerEnsureDir(): array {
        return [
            "file_path" => [
                "__TMP_DIR__",
                [ "ensured", "nested", "file.txt" ],
                "__TMP_DIR__/ensured/nested/file.txt",
                [ "__TMP_DIR__/ensured", "__TMP_DIR__/ensured/nested" ],
            ],
            "dir_path"  => [
                "__TMP_DIR__",
                [ "ensured-dir", "nested" ],
                "__TMP_DIR__/ensured-dir/nested",
                [ "__TMP_DIR__/ensured-dir", "__TMP_DIR__/ensured-dir/nested" ],
            ],
        ];
    }


    #[DataProvider("providerDeleteDir")]
    public function testDeleteDir(string $path, bool $expected, int $expectedDeleted): void {
        $path    = $this->resolveStringToken($path);
        $deleted = 0;
        $result  = Storage::deleteDir($path, $deleted);

        $this->assertSame($expected, $result);
        $this->assertSame($expectedDeleted, $deleted);
        $this->assertFalse(Storage::fileExists($path));
    }

    public static function providerDeleteDir(): array {
        return [
            "file"    => [ "__TMP_DIR__/delete-file.txt", true, 1 ],
            "dir"     => [ "__TMP_DIR__/delete-dir", true, 1 ],
            "missing" => [ "__TMP_DIR__/missing-dir", true, 0 ],
            "empty"   => [ "", false, 0 ],
        ];
    }


    #[DataProvider("providerEmptyDir")]
    public function testEmptyDir(string $path, bool $expected, int $expectedDeleted, bool $directoryShouldExist, bool $expectBrokenChild): void {
        $path = $this->resolveStringToken($path);

        $deleted = 0;
        $result  = $this->runWithSuppressedWarnings(
            function () use ($path, &$deleted): bool {
                return Storage::emptyDir($path, $deleted);
            },
            suppress: $expectBrokenChild,
        );

        $this->assertSame($expected, $result);
        $this->assertSame($expectedDeleted, $deleted);
        $this->assertSame($directoryShouldExist, is_dir($path));

        if ($expectBrokenChild) {
            $this->assertDirectoryExists($path . DIRECTORY_SEPARATOR . "nested");
            $this->assertTrue(is_link($path . DIRECTORY_SEPARATOR . "nested" . DIRECTORY_SEPARATOR . "broken"));
        }
    }

    public static function providerEmptyDir(): array {
        return [
            "filled"     => [ "__TMP_DIR__/empty-dir-target", true, 2, true, false ],
            "missing"    => [ "__TMP_DIR__/missing-dir", true, 0, false, false ],
            "broken"     => [ "__BROKEN_EMPTY_DIR__", false, 0, true, true ],
            "empty_dir"  => [ "__EMPTY_ROOT__", true, 0, true, false ],
            "empty_path" => [ "", false, 0, false, false ],
        ];
    }


    #[DataProvider("providerCreateZip")]
    public function testCreateZip(string $zipPath, array|string $files, ?array $expectedEntries): void {
        $zipPath = $this->resolveStringToken($zipPath);
        $files   = is_array($files) ? $this->resolveTokens($files) : $this->resolveStringToken($files);

        if (is_array($files) && in_array($this->brokenZipSource, $files, true) && !is_link($this->brokenZipSource . DIRECTORY_SEPARATOR . "broken")) {
            $this->markTestSkipped("Broken symlink setup is not available in this environment");
        }

        $zip = Storage::createZip($zipPath, $files);

        if ($expectedEntries === null) {
            $this->assertNull($zip);
            return;
        }

        $this->assertInstanceOf(ZipArchive::class, $zip);
        $this->assertFileExists($zipPath);

        $archive = new ZipArchive();
        $this->assertSame(true, $archive->open($zipPath));

        $entries = [];
        for ($i = 0; $i < $archive->numFiles; $i += 1) {
            $entries[] = $archive->getNameIndex($i);
        }
        $archive->close();

        $this->assertSame($expectedEntries, $entries);
    }

    public static function providerCreateZip(): array {
        return [
            "file" => [
                "__TMP_DIR__/single.zip",
                "__PLAIN_FILE__",
                [ "plain.txt" ],
            ],
            "directory" => [
                "__TMP_DIR__/directory.zip",
                "__TMP_DIR__/zip-source",
                [
                    "zip-source/",
                    "zip-source/a.txt",
                    "zip-source/nested/",
                    "zip-source/nested/b.txt",
                ],
            ],
            "missing_entry" => [
                "__TMP_DIR__/missing-entry.zip",
                [ "__PLAIN_FILE__", "__TMP_DIR__/missing-source.txt" ],
                [ "plain.txt" ],
            ],
            "broken_symlink" => [
                "__TMP_DIR__/broken-link.zip",
                [ "__BROKEN_ZIP_SOURCE__" ],
                [
                    "zip-source-broken/",
                    "zip-source-broken/a.txt",
                ],
            ],
            "invalid_path" => [
                "__TMP_DIR__",
                "__PLAIN_FILE__",
                null,
            ],
        ];
    }


    #[DataProvider("providerExtractZip")]
    public function testExtractZip(string $zipPath, string $extractPath, bool $expected, array $expectedFiles): void {
        $zipPath     = $this->resolveStringToken($zipPath);
        $extractPath = $this->resolveStringToken($extractPath);
        $result      = Storage::extractZip($zipPath, $extractPath);

        $this->assertSame($expected, $result);

        foreach ($expectedFiles as $relativePath => $content) {
            $path = Storage::parsePath($extractPath, $relativePath);
            $this->assertSame($content, Storage::readFile($path));
        }
    }

    public static function providerExtractZip(): array {
        return [
            "valid"         => [
                "__FIXTURE_ZIP__",
                "__TMP_DIR__/extracted",
                true,
                [
                    "doc.txt"         => "doc",
                    "folder/item.txt" => "item",
                ],
            ],
            "missing"       => [
                "__TMP_DIR__/missing.zip",
                "__TMP_DIR__/missing-extract",
                false,
                [],
            ],
            "empty_path"    => [
                "",
                "__TMP_DIR__/empty-extract",
                false,
                [],
            ],
            "empty_extract" => [
                "__FIXTURE_ZIP__",
                "",
                false,
                [],
            ],
        ];
    }
}
