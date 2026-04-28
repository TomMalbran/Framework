<?php
namespace Tests\File;

use Framework\File\File;
use Framework\File\Picture;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PictureTest extends TestCase {
    use TestHelpers;

    private string $tmpDir = "";

    /** @var array<string,string> */
    private array $files = [];


    protected function setUp(): void {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "picture_test_" . uniqid();
        @mkdir($this->tmpDir);

        $this->files = [
            "png"     => $this->tmpDir . DIRECTORY_SEPARATOR . "sample.png",
            "jpeg"    => $this->tmpDir . DIRECTORY_SEPARATOR . "sample.jpg",
            "missing" => $this->tmpDir . DIRECTORY_SEPARATOR . "missing.png",
        ];

        $this->writeFixtureImage($this->files["png"], 3, 120, 60);
        $this->writeFixtureImage($this->files["jpeg"], 2, 200, 100);
    }

    protected function tearDown(): void {
        File::deleteDir($this->tmpDir);
    }


    #[DataProvider("providerConstruct")]
    public function testConstruct(string $token, int $type, int $width, int $height): void {
        $picture = new Picture($this->files[$token] ?? "");

        $this->assertSame($type, $picture->type);
        $this->assertSame($width, $picture->width);
        $this->assertSame($height, $picture->height);
    }

    public static function providerConstruct(): array {
        return [
            "jpeg"    => [ "jpeg", 2, 200, 100 ],
            "missing" => [ "missing", 0, 0, 0 ],
            "invalid" => [ "invalid", 0, 0, 0 ],
        ];
    }


    #[DataProvider("providerCreateColor")]
    public function testCreateColor(string $token, int $red, int $green, int $blue, int|string $expected): void {
        $picture = new Picture($this->files[$token] ?? "");
        $color   = $picture->createColor($red, $green, $blue);

        if ($expected === "positive") {
            $this->assertGreaterThanOrEqual(0, $color);
        } else {
            $this->assertSame($expected, $color);
        }
    }

    public static function providerCreateColor(): array {
        return [
            "valid"            => [ "png", 20, 80, 140, "positive" ],
            "negative_channel" => [ "png", -1, 80, 140, 0 ],
            "channel_too_high" => [ "png", 20, 256, 140, 0 ],
            "missing"          => [ "missing", 20, 80, 140, 0 ],
            "invalid"          => [ "invalid", 20, 80, 140, 0 ],
        ];
    }


    #[DataProvider("providerWriteText")]
    public function testWriteText(string $token, string $text, int $x, int $y, int $color, bool $centered, bool $expected): void {
        $picture = new Picture($this->files[$token] ?? "");
        $result  = $picture->writeText($text, $x, $y, $color, $this->findFontFile(), 16, $centered);

        $this->assertSame($expected, $result);
    }

    public static function providerWriteText(): array {
        return [
            "normal"   => [ "png", "Framework", 10, 30, 0, false, true ],
            "centered" => [ "png", "Framework", 60, 30, 0, true, true ],
            "missing"  => [ "missing", "Framework", 10, 30, 0, false, false ],
            "invalid"  => [ "invalid", "Framework", 10, 30, 0, false, false ],
        ];
    }


    #[DataProvider("providerPrint")]
    public function testPrint(string $token, bool $download, string $name, bool $expected, bool $expectOutput): void {
        $picture = new Picture($this->files[$token] ?? "");

        ob_start();
        $result = $picture->print($download, $name);
        $output = ob_get_clean();

        $this->assertSame($expected, $result);
        $this->assertIsString($output);

        if ($expectOutput) {
            $this->assertNotSame("", $output);
            return;
        }

        $this->assertSame("", $output);
    }

    public static function providerPrint(): array {
        return [
            "png"      => [ "png", false, "image", true, true ],
            "download" => [ "jpeg", true, "report", true, true ],
            "missing"  => [ "missing", false, "image", false, false ],
            "invalid"  => [ "invalid", false, "image", false, false ],
        ];
    }
}
