<?php
namespace Tests\File;

use Framework\File\Type\MediaType;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MediaTypeTest extends TestCase {

    private string $tmpDir = "";
    private string $fixtureDir = "";


    protected function setUp(): void {
        $this->tmpDir     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "media_type_test_" . uniqid();
        $this->fixtureDir = $this->tmpDir . DIRECTORY_SEPARATOR . "folder";

        @mkdir($this->fixtureDir, 0777, true);
    }

    protected function tearDown(): void {
        if (is_dir($this->fixtureDir)) {
            @rmdir($this->fixtureDir);
        }
        if (is_dir($this->tmpDir)) {
            @rmdir($this->tmpDir);
        }
    }


    #[DataProvider("providerIsValid")]
    public function testIsValid(string $type, string $file, string $name, bool $expected): void {
        if ($file === "__DIR__") {
            $file = $this->fixtureDir;
        }
        $this->assertSame($expected, MediaType::isValid($type, $file, $name));
    }

    public static function providerIsValid(): array {
        return [
            "any_visible_file"   => [ MediaType::Any, "photo.png", "photo.png", true ],
            "any_hidden_file"    => [ MediaType::Any, ".env", ".env", false ],
            "media_image"        => [ MediaType::Media, "photo.png", "photo.png", true ],
            "media_video"        => [ MediaType::Media, "clip.mp4", "clip.mp4", true ],
            "media_text"         => [ MediaType::Media, "notes.txt", "notes.txt", false ],
            "image"              => [ MediaType::Image, "photo.png", "photo.png", true ],
            "video"              => [ MediaType::Video, "clip.mp4", "clip.mp4", true ],
            "audio"              => [ MediaType::Audio, "track.mp3", "track.mp3", true ],
            "text"               => [ MediaType::Text, "notes.txt", "notes.txt", true ],
            "pdf"                => [ MediaType::PDF, "manual.pdf", "manual.pdf", true ],
            "file_type"          => [ MediaType::File, "archive.bin", "archive.bin", true ],
            "directory_fallback" => [ MediaType::Image, "__DIR__", "folder", true ],
            "unknown_type"       => [ "unknown", "archive.bin", "archive.bin", false ],
        ];
    }
}
