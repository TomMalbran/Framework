<?php
// spell-checker: ignore  msvideo flac quicktime
namespace Tests\File;

use Framework\File\FileType;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FileTypeTest extends TestCase {

    protected string $tmpDir = "";
    protected string $tmpFile = "";


    protected function setUp(): void {
        $tmpBase = sys_get_temp_dir();
        $this->tmpDir  = $tmpBase . DIRECTORY_SEPARATOR . "ft_test_dir_" . uniqid();
        $this->tmpFile = $tmpBase . DIRECTORY_SEPARATOR . "ft_test_file_" . uniqid() . ".txt";

        @mkdir($this->tmpDir);
        @file_put_contents($this->tmpFile, "hello");
    }

    protected function tearDown(): void {
        if ($this->tmpFile !== "" && file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
        if ($this->tmpDir !== "" && file_exists($this->tmpDir)) {
            @rmdir($this->tmpDir);
        }
    }


    #[DataProvider("providerIsDir")]
    public function testIsDir(string $input, bool $expected) {
        if ($input === "tmpDir") {
            $value = $this->tmpDir;
        } elseif ($input === "tmpFile") {
            $value = $this->tmpFile;
        } else {
            $value = $input;
        }
        $this->assertSame($expected, FileType::isDir($value));
    }

    public static function providerIsDir() {
        return [
            "tmp_dir"      => [ "tmpDir", true ],
            "tmp_file"     => [ "tmpFile", false ],
            "non_existent" => [ "/path/that/does/not/exist", false ],
        ];
    }


    #[DataProvider("providerIsHidden")]
    public function testIsHidden(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isHidden($input));
    }

    public static function providerIsHidden() {
        return [
            "hidden"       => [ ".hidden", true ],
            "visible"      => [ "visible.txt", false ],
            "hidden_file"  => [ ".hidden.txt", true ],
            "empty"        => [ "", false ],
        ];
    }


    #[DataProvider("providerIsImage")]
    public function testIsImage(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isImage($input));
    }

    public static function providerIsImage() {
        return [
            "jpg"    => [ "image.jpg", true ],
            "jpeg"   => [ "photo.jpeg", true ],
            "gif"    => [ "graphic.gif", true ],
            "png"    => [ "photo.png", true ],
            "ico"    => [ "icon.ico", true ],
            "avif"   => [ "picture.avif", true ],
            "webp"   => [ "image.webp", true ],

            "pdf"    => [ "document.pdf", false ],
            "zip"    => [ "archive.zip", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsPNG")]
    public function testIsPNG(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isPNG($input));
    }

    public static function providerIsPNG() {
        return [
            "png"    => [ "photo.png", true ],
            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsICO")]
    public function testIsICO(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isICO($input));
    }

    public static function providerIsICO() {
        return [
            "ico"    => [ "icon.ico", true ],
            "png"    => [ "photo.png", false ],
            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsVideo")]
    public function testIsVideo(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isVideo($input));
    }

    public static function providerIsVideo() {
        return [
            "mov"    => [ "film.mov", true ],
            "mpeg"   => [ "clip.mpeg", true ],
            "m4v"    => [ "movie.m4v", true ],
            "mp4"    => [ "video.mp4", true ],
            "avi"    => [ "movie.avi", true ],
            "mpg"    => [ "video.mpg", true ],
            "wma"    => [ "audio.wma", true ],
            "flv"    => [ "animation.flv", true ],
            "webm"   => [ "video.webm", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsAudio")]
    public function testIsAudio(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isAudio($input));
    }

    public static function providerIsAudio() {
        return [
            "mp3"    => [ "music.mp3", true ],
            "mpga"   => [ "audio.mpga", true ],
            "m4a"    => [ "song.m4a", true ],
            "ac3"    => [ "sound.ac3", true ],
            "aiff"   => [ "track.aiff", true ],
            "mid"    => [ "music.mid", true ],
            "ogg"    => [ "audio.ogg", true ],
            "wav"    => [ "sound.wav", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsCode")]
    public function testIsCode(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isCode($input));
    }

    public static function providerIsCode() {
        return [
            "html"   => [ "index.html", true ],
            "xhtml"  => [ "page.xhtml", true ],
            "sql"    => [ "query.sql", true ],
            "xml"    => [ "data.xml", true ],
            "js"     => [ "app.js", true ],
            "json"   => [ "config.json", true ],
            "css"    => [ "style.css", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsText")]
    public function testIsText(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isText($input));
    }

    public static function providerIsText() {
        return [
            "txt"    => [ "notes.txt", true ],
            "md"     => [ "readme.md", true ],
            "csv"    => [ "data.csv", true ],
            "log"    => [ "app.log", true ],
            "rtf"    => [ "document.rtf", true ],
            "json"   => [ "config.json", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsDocument")]
    public function testIsDocument(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isDocument($input));
    }

    public static function providerIsDocument() {
        return [
            "doc"    => [ "document.doc", true ],
            "docx"   => [ "report.docx", true ],
            "odt"    => [ "notes.odt", true ],
            "ott"    => [ "document.ott", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsSpreadsheet")]
    public function testIsSpreadsheet(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isSpreadsheet($input));
    }

    public static function providerIsSpreadsheet() {
        return [
            "xls"    => [ "data.xls", true ],
            "xlsx"   => [ "report.xlsx", true ],
            "ods"    => [ "numbers.ods", true ],
            "ots"    => [ "data.ots", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsPresentation")]
    public function testIsPresentation(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isPresentation($input));
    }

    public static function providerIsPresentation() {
        return [
            "ppt"    => [ "slides.ppt", true ],
            "pptx"   => [ "presentation.pptx", true ],
            "odp"    => [ "talk.odp", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsPDF")]
    public function testIsPDF(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isPDF($input));
    }

    public static function providerIsPDF() {
        return [
            "pdf"    => [ "document.pdf", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsZip")]
    public function testIsZip(string $input, bool $expected) {
        $this->assertSame($expected, FileType::isZip($input));
    }

    public static function providerIsZip() {
        return [
            "zip"    => [ "archive.zip", true ],
            "rar"    => [ "archive.rar", true ],
            "gz"     => [ "archive.gz", true ],
            "tar"    => [ "archive.tar", true ],
            "iso"    => [ "disk.iso", true ],
            "7zip"   => [ "archive.7zip", true ],

            "jpg"    => [ "image.jpg", false ],
            "no_ext" => [ "file", false ],
            "empty"  => [ "", false ],
        ];
    }


    #[DataProvider("providerIsFile")]
    public function testIsFile(string $input, bool $expected) {
        if ($input === "tmpDir") {
            $value = $this->tmpDir;
        } else {
            $value = $input;
        }
        $this->assertSame($expected, FileType::isFile($value));
    }

    public static function providerIsFile() {
        return [
            "txt"   => [ "notes.txt", true ],
            "dir"   => [ "tmpDir", false ],
            "jpg"   => [ "image.jpg", false ],
            "mp4"   => [ "video.mp4", false ],
            "empty" => [ "", false ],
        ];
    }


    #[DataProvider("providerGetIcon")]
    public function testGetIcon(string $input, string $expected) {
        $this->assertSame($expected, FileType::getIcon($input));
    }

    public static function providerGetIcon() {
        return [
            "image"        => [ "photo.png", "file-image" ],
            "video"        => [ "movie.mp4", "file-video" ],
            "audio"        => [ "music.mp3", "file-audio" ],
            "code"         => [ "index.html", "file-code" ],
            "text"         => [ "notes.txt", "file-text" ],
            "document"     => [ "document.docx", "file-document" ],
            "spreadsheet"  => [ "data.xlsx", "file-spreadsheet" ],
            "presentation" => [ "slides.pptx", "file-presentation" ],
            "pdf"          => [ "document.pdf", "file-pdf" ],
            "zip"          => [ "archive.zip", "file-zip" ],
            "unknown"      => [ "file.unknown", "file" ],
        ];
    }


    #[DataProvider("providerGetExtension")]
    public function testGetExtension(string $input, string $expected) {
        $this->assertSame($expected, FileType::getExtension($input));
    }

    public static function providerGetExtension() {
        return [
            "3gp"               => [ "video/3gp", "3gp" ],
            "aac"               => [ "audio/x-acc", "aac" ],
            "avi"               => [ "video/x-msvideo", "avi" ],
            "7zip"              => [ "application/x-compressed", "7zip" ],
            "bmp"               => [ "image/bmp", "bmp" ],
            "csv"               => [ "text/comma-separated-values", "csv" ],
            "doc"               => [ "application/msword", "doc" ],
            "docx"              => [ "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "docx" ],
            "flac"              => [ "audio/x-flac", "flac" ],
            "flv"               => [ "video/x-flv", "flv" ],
            "gif"               => [ "image/gif", "gif" ],
            "html"              => [ "text/html", "html" ],
            "ico"               => [ "image/x-icon", "ico" ],
            "jpeg"              => [ "image/jpeg", "jpeg" ],
            "json"              => [ "application/json", "json" ],
            "m4a"               => [ "audio/mp4", "m4a" ],
            "mov"               => [ "video/quicktime", "mov" ],
            "mp3"               => [ "audio/mpeg", "mp3" ],
            "mp4"               => [ "video/mp4", "mp4" ],
            "ogg"               => [ "application/ogg", "ogg" ],
            "pdf"               => [ "application/pdf", "pdf" ],
            "php"               => [ "application/x-httpd-php", "php" ],
            "png"               => [ "image/png", "png" ],
            "pptx"              => [ "application/vnd.openxmlformats-officedocument.presentationml.presentation", "pptx" ],
            "rar"               => [ "application/x-rar-compressed", "rar" ],
            "svg"               => [ "image/svg+xml", "svg" ],
            "txt"               => [ "text/plain", "txt" ],
            "wav"               => [ "audio/wav", "wav" ],
            "webm"              => [ "video/webm", "webm" ],
            "webp"              => [ "image/webp", "webp" ],
            "woff2"             => [ "font/woff2", "woff2" ],
            "xlsx"              => [ "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "xlsx" ],
            "xml"               => [ "application/xml", "xml" ],
            "zip"               => [ "application/zip", "zip" ],
            "mime_with_params"  => [ "text/html; charset=UTF-8", "html" ],
            "unknown"           => [ "application/not-registered", "" ],
        ];
    }
}
