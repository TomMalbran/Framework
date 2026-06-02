<?php
namespace Tests\File;

use Framework\File\File;
use Framework\File\MediaFile;
use Framework\File\Storage;
use Framework\System\Path;
use Tests\TestHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use CURLFile;

class FileTest extends TestCase {
    use TestHelpers;

    private string $tmpDir = "";

    /** @var array<string,string> */
    private array $files = [];

    private int $mediaID = 0;


    protected function setUp(): void {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "file_test_" . uniqid();
        @mkdir($this->tmpDir);

        $this->files = [
            "image"   => $this->tmpDir . DIRECTORY_SEPARATOR . "sample.png",
            "text"    => $this->tmpDir . DIRECTORY_SEPARATOR . "sample.txt",
            "upload"  => $this->tmpDir . DIRECTORY_SEPARATOR . "upload.txt",
            "missing" => $this->tmpDir . DIRECTORY_SEPARATOR . "missing.txt",
        ];

        $this->writeFixtureImage($this->files["image"], 3, 10, 20);
        Storage::writeFile($this->files["text"], "text");
        Storage::writeFile($this->files["upload"], "upload");

        $this->mediaID = random_int(100000, 999999);
        MediaFile::setID($this->mediaID);

        $_FILES = [];
        $GLOBALS["test_move_uploaded_file"] = false;
    }

    protected function tearDown(): void {
        $_FILES = [];
        unset($GLOBALS["test_move_uploaded_file"]);

        Storage::deleteDir($this->tmpDir);
        Storage::deleteDir(Path::getSourcePath($this->mediaID));
        Storage::deleteDir(Path::getThumbsPath($this->mediaID));
        MediaFile::setID(0);
    }


    #[DataProvider("providerConstruct")]
    public function testConstruct(string $filePath, bool $withRequest, bool $expectedHasFile, string $expectedName): void {
        $fileRequest = $withRequest
            ? $this->createFileRequest("request.txt", "text/plain", $this->files["text"], UPLOAD_ERR_OK)
            : null;

        $file = new File($filePath, $fileRequest);

        $this->assertSame($expectedHasFile, $file->hasFile());
        $this->assertSame($expectedName, $file->getName());
    }

    public static function providerConstruct(): array {
        return [
            "request_file" => [ "", true, true, "request.txt" ],
            "path_file"    => [ "local.txt", false, false, "local.txt" ],
            "empty_string" => [ "", false, false, "" ],
        ];
    }


    #[DataProvider("providerFromRequest")]
    public function testFromRequest(string $key, string $fallbackPath, bool $expectedHasFile, string $expectedName, string $expectedString): void {
        $this->setUploadFile("upload", "request.txt", "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $file = File::fromRequest($key, $fallbackPath);

        $this->assertSame($expectedHasFile, $file->hasFile());
        $this->assertSame($expectedName, $file->getName());
        $this->assertSame($expectedString, $file->toString());
    }

    public static function providerFromRequest(): array {
        return [
            "existing_key"         => [ "upload", "fallback.txt", true, "request.txt", "" ],
            "missing_with_path"    => [ "missing", "fallback.txt", false, "fallback.txt", "fallback.txt" ],
            "missing_without_path" => [ "missing", "", false, "", "" ],
        ];
    }


    #[DataProvider("providerHasFile")]
    public function testHasFile(string $mode, string $key, bool $expected): void {
        $this->setUploadFile("upload", "request.txt", "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $key)->hasFile());
    }

    public static function providerHasFile(): array {
        return [
            "upload"  => [ "upload", "upload", true ],
            "path"    => [ "path", "local.txt", false ],
            "missing" => [ "path", "missing", false ],
        ];
    }


    #[DataProvider("providerIsValid")]
    public function testIsValid(string $mode, string $key, int $error, bool $expected): void {
        $this->setUploadFile("upload", "request.txt", "text/plain", $this->files["text"], $error);

        $this->assertSame($expected, $this->createFile($mode, $key)->isValid());
    }

    public static function providerIsValid(): array {
        return [
            "upload_ok"    => [ "upload", "upload", UPLOAD_ERR_OK, true ],
            "upload_error" => [ "upload", "upload", UPLOAD_ERR_NO_FILE, false ],
            "path"         => [ "path", "local.txt", UPLOAD_ERR_OK, true ],
            "empty_path"   => [ "path", "", UPLOAD_ERR_OK, false ],
        ];
    }


    #[DataProvider("providerHasSizeError")]
    public function testHasSizeError(string $mode, string $key, int $error, bool $expected): void {
        $this->setUploadFile("upload", "request.txt", "text/plain", $this->files["text"], $error);

        $this->assertSame($expected, $this->createFile($mode, $key)->hasSizeError());
    }

    public static function providerHasSizeError(): array {
        return [
            "ini_size"    => [ "upload", "upload", UPLOAD_ERR_INI_SIZE, true ],
            "form_size"   => [ "upload", "upload", UPLOAD_ERR_FORM_SIZE, false ],
            "upload_ok"   => [ "upload", "upload", UPLOAD_ERR_OK, false ],
            "path_backed" => [ "path", "local.txt", UPLOAD_ERR_INI_SIZE, false ],
        ];
    }


    #[DataProvider("providerHasExtension")]
    public function testHasExtension(string $mode, string $name, array $extensions, bool $expected): void {
        $this->setUploadFile("upload", $name, "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $name)->hasExtension(...$extensions));
    }

    public static function providerHasExtension(): array {
        return [
            "upload_match" => [ "upload", "report.txt", [ "txt" ], true ],
            "path_match"   => [ "path", "photo.png?size=large", [ "jpg", "png" ], true ],
            "no_extension" => [ "path", "README", [ "txt" ], false ],
            "no_match"     => [ "path", "archive.zip", [ "txt", "png" ], false ],
        ];
    }


    #[DataProvider("providerIsImage")]
    public function testIsImage(string $mode, string $name, bool $expected): void {
        $this->setUploadFile("upload", $name, "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $name)->isImage());
    }

    public static function providerIsImage(): array {
        return [
            "upload_image" => [ "upload", "photo.png", true ],
            "upload_text"  => [ "upload", "report.txt", false ],
            "path_image"   => [ "path", "cover.jpg", true ],
            "path_text"    => [ "path", "notes.md", false ],
        ];
    }


    #[DataProvider("providerIsValidImage")]
    public function testIsValidImage(string $mode, string $name, string $tmpToken, bool $expected): void {
        $this->setUploadFile("upload", $name, "text/plain", $this->resolveFileToken($tmpToken), UPLOAD_ERR_OK);

        set_error_handler(static fn() => true, E_NOTICE | E_WARNING);
        try {
            $this->assertSame($expected, $this->createFile($mode, $name)->isValidImage());
        } finally {
            restore_error_handler();
        }
    }

    public static function providerIsValidImage(): array {
        return [
            "upload_valid_image" => [ "upload", "photo.txt", "image", true ],
            "upload_text_file"   => [ "upload", "photo.png", "text", false ],
            "path_image_name"    => [ "path", "missing.png", "missing", true ],
            "path_text_name"     => [ "path", "missing.txt", "missing", false ],
        ];
    }


    #[DataProvider("providerMediaExists")]
    public function testMediaExists(string $mode, string $name, bool $createMedia, bool $expected): void {
        if ($createMedia) {
            Storage::createFile(Path::getSourcePath($this->mediaID), $name, "media", createDir: true);
        }
        $this->setUploadFile("upload", $name, "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $name)->mediaExists());
    }

    public static function providerMediaExists(): array {
        return [
            "existing_path" => [ "path", "media.txt", true, true ],
            "missing_path"  => [ "path", "missing.txt", false, false ],
            "upload_file"   => [ "upload", "media.txt", true, false ],
        ];
    }


    #[DataProvider("providerGetName")]
    public function testGetName(string $mode, string $key, string $uploadName, string $expected): void {
        $this->setUploadFile("upload", $uploadName, "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $key)->getName());
    }

    public static function providerGetName(): array {
        return [
            "upload" => [ "upload", "upload", "request.txt", "request.txt" ],
            "path"   => [ "path", "local.txt", "request.txt", "local.txt" ],
            "empty"  => [ "path", "", "request.txt", "" ],
        ];
    }


    #[DataProvider("providerGetTmpName")]
    public function testGetTmpName(string $mode, string $key, string $tmpToken, string $expectedToken): void {
        $tmpName = $this->resolveFileToken($tmpToken);
        $this->setUploadFile("upload", "request.txt", "text/plain", $tmpName, UPLOAD_ERR_OK);

        $this->assertSame($this->resolveExpectedString($expectedToken), $this->createFile($mode, $key)->getTmpName());
    }

    public static function providerGetTmpName(): array {
        return [
            "upload" => [ "upload", "upload", "text", "__TEXT__" ],
            "path"   => [ "path", "local.txt", "text", "local.txt" ],
            "empty"  => [ "path", "", "text", "" ],
        ];
    }


    #[DataProvider("providerGetType")]
    public function testGetType(string $mode, string $key, string $mimeType, string $expected): void {
        $this->setUploadFile("upload", "request.txt", $mimeType, $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $key)->getType());
    }

    public static function providerGetType(): array {
        return [
            "upload_text"  => [ "upload", "upload", "text/plain", "text/plain" ],
            "upload_image" => [ "upload", "upload", "image/png", "image/png" ],
            "path"         => [ "path", "local.txt", "text/plain", "" ],
        ];
    }


    #[DataProvider("providerGetExtension")]
    public function testGetExtension(string $mode, string $name, string $expected): void {
        $this->setUploadFile("upload", $name, "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $name)->getExtension());
    }

    public static function providerGetExtension(): array {
        return [
            "upload"     => [ "upload", "request.txt", "txt" ],
            "path_query" => [ "path", "photo.png?size=large", "png" ],
            "none"       => [ "path", "README", "" ],
        ];
    }


    #[DataProvider("providerGetCurlFile")]
    public function testGetCurlFile(string $mode, string $key, ?string $expectedName, ?string $expectedType): void {
        $this->setUploadFile("upload", "request.txt", "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $curlFile = $this->createFile($mode, $key)->getCurlFile();

        if ($expectedName === null) {
            $this->assertNull($curlFile);
            return;
        }

        $this->assertInstanceOf(CURLFile::class, $curlFile);
        $this->assertSame($this->files["text"], $curlFile->getFilename());
        $this->assertSame($expectedType, $curlFile->getMimeType());
        $this->assertSame($expectedName, $curlFile->getPostFilename());
    }

    public static function providerGetCurlFile(): array {
        return [
            "upload" => [ "upload", "upload", "request.txt", "text/plain" ],
            "path"   => [ "path", "local.txt", null, null ],
        ];
    }


    #[DataProvider("providerGetValue")]
    public function testGetValue(string $mode, bool $expectedCurlFile, string $expectedPath): void {
        $this->setUploadFile("upload", "request.txt", "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $value = $this->createFile($mode, $expectedPath)->getValue();

        if ($expectedCurlFile) {
            $this->assertInstanceOf(CURLFile::class, $value);
        } else {
            $this->assertSame($expectedPath, $value);
        }
    }

    public static function providerGetValue(): array {
        return [
            "upload" => [ "upload", true, "upload" ],
            "path"   => [ "path", false, "local.txt" ],
            "empty"  => [ "path", false, "" ],
        ];
    }


    #[DataProvider("providerParseName")]
    public function testParseName(string $mode, string $originalName, string $newName, string $expected): void {
        $this->setUploadFile("upload", $originalName, "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $originalName)->parseName($newName));
    }

    public static function providerParseName(): array {
        return [
            "empty_new_name"       => [ "upload", "request.txt", "", "request.txt" ],
            "new_without_ext"      => [ "upload", "request.txt", "renamed", "renamed.txt" ],
            "new_with_ext"         => [ "path", "photo.png", "renamed.jpg", "renamed.jpg" ],
            "original_without_ext" => [ "path", "README", "renamed", "renamed" ],
        ];
    }


    #[DataProvider("providerUpload")]
    public function testUpload(string $mode, string $key, string $name, string $tmpToken, string $targetName, bool $expected, ?string $expectedContent): void {
        $GLOBALS["test_move_uploaded_file"] = true;
        $this->setUploadFile("upload", $name, "text/plain", $this->resolveFileToken($tmpToken), UPLOAD_ERR_OK);

        $result = $this->createFile($mode, $key)->upload($this->tmpDir, $targetName);

        $this->assertSame($expected, $result);
        if ($expectedContent !== null) {
            $this->assertSame($expectedContent, Storage::readFile($this->tmpDir, $targetName));
        } else {
            $this->assertFalse(Storage::fileExists($this->tmpDir, $targetName));
        }
    }

    public static function providerUpload(): array {
        return [
            "upload_valid"       => [ "upload", "upload", "request.txt", "upload", "uploaded.txt", true, "upload" ],
            "upload_missing_tmp" => [ "upload", "upload", "request.txt", "missing", "uploaded.txt", false, null ],
            "path_backed"        => [ "path", "local.txt", "local.txt", "upload", "uploaded.txt", false, null ],
        ];
    }


    #[DataProvider("providerDelete")]
    public function testDelete(string $mode, string $name, bool $createFile, bool $expected, bool $expectedExists): void {
        if ($createFile) {
            Storage::writeFile(Storage::parsePath($this->tmpDir, $name), "delete");
        }
        $this->setUploadFile("upload", $name, "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $result = $this->createFile($mode, $name)->delete($this->tmpDir);

        $this->assertSame($expected, $result);
        $this->assertSame($expectedExists, Storage::fileExists($this->tmpDir, $name));
    }

    public static function providerDelete(): array {
        return [
            "existing_path" => [ "path", "delete.txt", true, true, false ],
            "missing_path"  => [ "path", "missing.txt", false, false, false ],
            "upload_file"   => [ "upload", "delete.txt", true, false, true ],
        ];
    }


    #[DataProvider("providerToString")]
    public function testToString(string $mode, string $key, string $expected): void {
        $this->setUploadFile("upload", "request.txt", "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $key)->toString());
    }

    public static function providerToString(): array {
        return [
            "upload" => [ "upload", "upload", "" ],
            "path"   => [ "path", "local.txt", "local.txt" ],
            "empty"  => [ "path", "", "" ],
        ];
    }


    #[DataProvider("providerJsonSerialize")]
    public function testJsonSerialize(string $mode, string $key, string $expected): void {
        $this->setUploadFile("upload", "request.txt", "text/plain", $this->files["text"], UPLOAD_ERR_OK);

        $this->assertSame($expected, $this->createFile($mode, $key)->jsonSerialize());
    }

    public static function providerJsonSerialize(): array {
        return [
            "upload" => [ "upload", "upload", "" ],
            "path"   => [ "path", "local.txt", "local.txt" ],
            "empty"  => [ "path", "", "" ],
        ];
    }


    private function createFile(string $mode, string $key): File {
        if ($mode === "upload") {
            return File::fromRequest("upload");
        }
        return new File($key);
    }

    private function setUploadFile(string $key, string $name, string $type, string $tmpName, int $error): void {
        $_FILES[$key] = $this->createFileRequest($name, $type, $tmpName, $error);
    }

    private function createFileRequest(string $name, string $type, string $tmpName, int $error): array {
        return [
            "name"     => $name,
            "type"     => $type,
            "tmp_name" => $tmpName,
            "error"    => $error,
            "size"     => is_file($tmpName) ? filesize($tmpName) : 0,
        ];
    }

    private function resolveFileToken(string $token): string {
        return $this->files[$token] ?? $token;
    }

    private function resolveExpectedString(string $value): string {
        return match ($value) {
            "__TEXT__" => $this->files["text"],
            default    => $value,
        };
    }
}
