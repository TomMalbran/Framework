<?php
namespace Tests\File;

use Framework\File\Image;
use Framework\File\File;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use GdImage;

class ImageTest extends TestCase {

    private string $tmpDir = "";

    /** @var array<string,string> */
    private array $files = [];


    protected function setUp(): void {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "image_test_" . uniqid();
        @mkdir($this->tmpDir);

        $this->files = [
            "gif"         => $this->tmpDir . DIRECTORY_SEPARATOR . "sample.gif",
            "jpeg"        => $this->tmpDir . DIRECTORY_SEPARATOR . "sample.jpg",
            "png"         => $this->tmpDir . DIRECTORY_SEPARATOR . "sample.png",
            "transparent" => $this->tmpDir . DIRECTORY_SEPARATOR . "transparent.png",
            "large"       => $this->tmpDir . DIRECTORY_SEPARATOR . "large-transparent.png",
            "text"        => $this->tmpDir . DIRECTORY_SEPARATOR . "sample.txt",
            "missing"     => $this->tmpDir . DIRECTORY_SEPARATOR . "missing.png",
        ];

        $this->writeFixtureImage($this->files["gif"], 1, 30, 15);
        $this->writeFixtureImage($this->files["jpeg"], 2, 200, 100);
        $this->writeFixtureImage($this->files["png"], 3, 20, 10);
        $this->writeFixtureImage($this->files["transparent"], 3, 20, 10, transparent: true);
        $this->writeFixtureImage($this->files["large"], 3, 60, 60, transparent: true);

        @file_put_contents($this->files["text"], "not an image");
    }

    private function writeFixtureImage(string $path, int $imgType, int $width, int $height, bool $transparent = false): void {
        $image = imagecreatetruecolor($width, $height);
        $this->assertNotFalse($image);

        if ($transparent) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $fillColor = imagecolorallocatealpha($image, 255, 255, 255, 127);
        } else {
            $fillColor = imagecolorallocate($image, 20, 80, 140);
        }

        $this->assertNotFalse($fillColor);
        imagefilledrectangle($image, 0, 0, $width, $height, $fillColor);

        if ($transparent) {
            $dotColor = imagecolorallocatealpha($image, 255, 0, 0, 80);
            $this->assertNotFalse($dotColor);
            imagesetpixel($image, 0, 0, $dotColor);
        }

        match ($imgType) {
            1 => imagegif($image, $path),
            2 => imagejpeg($image, $path, 90),
            3 => imagepng($image, $path),
            default => false,
        };
    }


    protected function tearDown(): void {
        File::deleteDir($this->tmpDir);
    }


    #[DataProvider("providerHasType")]
    public function testHasType(int $fileType, bool $expected): void {
        $this->assertSame($expected, Image::hasType($fileType));
    }

    public static function providerHasType(): array {
        return [
            "gif"         => [ 1, true ],
            "jpeg"        => [ 2, true ],
            "png"         => [ 3, true ],
            "wbmp"        => [ 15, true ],
            "xbm"         => [ 16, true ],
            "unknown"     => [ 0, false ],
            "unsupported" => [ 4, false ],
        ];
    }


    #[DataProvider("providerIsValidType")]
    public function testIsValidType(string $token, bool $expected): void {
        $path = $this->files[$token] ?? "";
        $this->assertSame($expected, Image::isValidType($path));
    }

    public static function providerIsValidType(): array {
        return [
            "gif"     => [ "gif", true ],
            "jpeg"    => [ "jpeg", true ],
            "png"     => [ "png", true ],
            "text"    => [ "text", false ],
            "missing" => [ "missing", false ],
            "invalid" => [ "invalid", false ],
        ];
    }


    #[DataProvider("providerGetType")]
    public function testGetType(string $token, int $expected): void {
        $path = $this->files[$token] ?? "";
        $this->assertSame($expected, Image::getType($path));
    }

    public static function providerGetType(): array {
        return [
            "gif"     => [ "gif", 1 ],
            "jpeg"    => [ "jpeg", 2 ],
            "png"     => [ "png", 3 ],
            "text"    => [ "text", 0 ],
            "missing" => [ "missing", 0 ],
            "invalid" => [ "invalid", 0 ],
        ];
    }


    #[DataProvider("providerGetMimeType")]
    public function testGetMimeType(string $token, string $expected): void {
        $path = $this->files[$token] ?? "";
        $this->assertSame($expected, Image::getMimeType($path));
    }

    public static function providerGetMimeType(): array {
        return [
            "gif"     => [ "gif", "image/gif" ],
            "jpeg"    => [ "jpeg", "image/jpeg" ],
            "png"     => [ "png", "image/png" ],
            "text"    => [ "text", "application/octet-stream" ],
            "missing" => [ "missing", "" ],
            "invalid" => [ "invalid", "" ],
        ];
    }


    #[DataProvider("providerGetSize")]
    public function testGetSize(string $base, array $pathParts, array $expected): void {
        $base = $this->files[$base] ?? $this->tmpDir;
        $size = Image::getSize($base, ...$pathParts);
        $this->assertSame($expected, [ $size[0], $size[1], $size[2] ]);
    }

    public static function providerGetSize(): array {
        return [
            "jpeg_full_path" => [ "jpeg", [], [ 200, 100, 2 ] ],
            "png_split_path" => [ "tmpDir", [ "sample.png" ], [ 20, 10, 3 ] ],
            "text_file"      => [ "text", [], [ 0, 0, 0 ] ],
            "missing_file"   => [ "missing", [], [ 0, 0, 0 ] ],
        ];
    }


    #[DataProvider("providerGetSizeFromUrl")]
    public function testGetSizeFromUrl(string $token, array $expected): void {
        $path = $this->files[$token] ?? "";
        $size = Image::getSizeFromUrl($path);
        $this->assertSame($expected, [ $size[0], $size[1], $size[2] ]);
    }

    public static function providerGetSizeFromUrl(): array {
        return [
            "gif"     => [ "gif", [ 30, 15, 1 ] ],
            "jpeg"    => [ "jpeg", [ 200, 100, 2 ] ],
            "png"     => [ "png", [ 20, 10, 3 ] ],
            "text"    => [ "text", [ 0, 0, 0 ] ],
            "missing" => [ "missing", [ 0, 0, 0 ] ],
            "invalid" => [ "invalid", [ 0, 0, 0 ] ],
        ];
    }


    #[DataProvider("providerGetOrientation")]
    public function testGetOrientation(string $token, int $expected): void {
        $path = $this->files[$token] ?? "";
        $this->assertSame($expected, Image::getOrientation($path));
    }

    public static function providerGetOrientation(): array {
        return [
            "opaque_png"            => [ "png", 0 ],
            "transparent_png"       => [ "transparent", 0 ],
            "large_transparent_png" => [ "large", 0 ],
            "jpeg"                  => [ "jpeg", 0 ],
            "missing"               => [ "missing", 0 ],
            "invalid"               => [ "invalid", 0 ],
        ];
    }


    #[DataProvider("providerHasTransparency")]
    public function testHasTransparency(string $token, bool $expected): void {
        $path = $this->files[$token] ?? "";
        $this->assertSame($expected, Image::hasTransparency($path));
    }

    public static function providerHasTransparency(): array {
        return [
            "opaque_png"            => [ "png", false ],
            "transparent_png"       => [ "transparent", true ],
            "large_transparent_png" => [ "large", true ],
            "jpeg"                  => [ "jpeg", false ],
            "missing"               => [ "missing", false ],
            "invalid"               => [ "invalid", false ],
        ];
    }


    public function testGetTextWidth(): void {
        $fontFile = $this->findFontFile();

        $smallWidth = Image::getTextWidth("Framework", $fontFile, 12);
        $largeWidth = Image::getTextWidth("Framework", $fontFile, 24);
        $emptyWidth = Image::getTextWidth("", $fontFile, 12);

        $this->assertGreaterThan(0, $smallWidth);
        $this->assertGreaterThan($smallWidth, $largeWidth);
        $this->assertSame(0, $emptyWidth);
    }

    private function findFontFile(): string {
        $candidates = [
            "/System/Library/Fonts/Supplemental/NotoSansLepcha-Regular.ttf",
            "/System/Library/Fonts/Supplemental/Arial.ttf",
            "/System/Library/Fonts/SFNS.ttf",
            "/System/Library/Fonts/Supplemental/Times New Roman.ttf",
            "/Library/Fonts/Arial.ttf",
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $this->markTestSkipped("No TrueType/OpenType font file available for imagettfbbox");
    }


    #[DataProvider("providerResample")]
    public function testResample(
        string $srcToken,
        string $dstName,
        int $orientation,
        bool $expected,
        ?array $expectedSize,
    ): void {
        $srcPath = $this->files[$srcToken] ?? "";
        $dstPath = $this->tmpDir . DIRECTORY_SEPARATOR . $dstName;
        $result  = Image::resample($srcPath, $dstPath, $orientation);

        $this->assertSame($expected, $result);

        if ($expectedSize === null) {
            $this->assertFileDoesNotExist($dstPath);
            return;
        }

        $size = Image::getSize($dstPath);
        $this->assertSame($expectedSize, [ $size[0], $size[1], $size[2] ]);
    }

    public static function providerResample(): array {
        return [
            "jpeg_rotate_180" => [ "jpeg", "resample-180.jpg", 3, true, [ 200, 100, 2 ] ],
            "jpeg_rotate_90"  => [ "jpeg", "resample-90.jpg", 6, true, [ 100, 200, 2 ] ],
            "png_rotate_270"  => [ "png", "resample-270.png", 8, true, [ 10, 20, 3 ] ],
            "invalid"         => [ "jpeg", "resample-invalid.jpg", 0, false, null ],
        ];
    }


    #[DataProvider("providerResize")]
    public function testResize(
        string $srcToken,
        string $dstName,
        int $width,
        int $height,
        string $action,
        bool $expected,
        ?array $expectedSize,
    ): void {
        $srcPath = $this->files[$srcToken] ?? "";
        $dstPath = $this->tmpDir . DIRECTORY_SEPARATOR . $dstName;
        $result  = Image::resize($srcPath, $dstPath, $width, $height, $action);

        $this->assertSame($expected, $result);

        if ($expectedSize === null) {
            $this->assertFileDoesNotExist($dstPath);
            return;
        }

        $size = Image::getSize($dstPath);
        $this->assertSame($expectedSize, [ $size[0], $size[1], $size[2] ]);
    }

    public static function providerResize(): array {
        return [
            "resize_jpeg"    => [ "jpeg", "resize.jpg", 50, 50, Image::Resize, true, [ 50, 25, 2 ] ],
            "maximum_small"  => [ "png", "maximum.png", 50, 50, Image::Maximum, true, [ 20, 10, 3 ] ],
            "thumb_jpeg"     => [ "jpeg", "thumb.jpg", 50, 50, Image::Thumb, true, [ 50, 50, 2 ] ],
            "missing_source" => [ "missing", "missing.jpg", 50, 50, Image::Resize, false, null ],
        ];
    }


    #[DataProvider("providerResizeCrop")]
    public function testResizeCrop(
        string $srcToken,
        string $dstName,
        int $resWidth,
        int $resHeight,
        int $cropX,
        int $cropY,
        int $cropWidth,
        int $cropHeight,
        bool $expected,
        ?array $expectedSize,
    ): void {
        $srcPath = $this->files[$srcToken] ?? "";
        $dstPath = $this->tmpDir . DIRECTORY_SEPARATOR . $dstName;
        $result  = Image::resizeCrop(
            $srcPath,
            $dstPath,
            $resWidth,
            $resHeight,
            $cropX,
            $cropY,
            $cropWidth,
            $cropHeight,
        );

        $this->assertSame($expected, $result);

        if ($expectedSize === null) {
            $this->assertFileDoesNotExist($dstPath);
            return;
        }

        $size = Image::getSize($dstPath);
        $this->assertSame($expectedSize, [ $size[0], $size[1], $size[2] ]);
    }

    public static function providerResizeCrop(): array {
        return [
            "jpeg_crop"      => [ "jpeg", "crop.jpg", 100, 50, 10, 10, 30, 20, true, [ 30, 20, 2 ] ],
            "png_crop"       => [ "png", "crop.png", 40, 20, 5, 5, 10, 10, true, [ 10, 10, 3 ] ],
            "invalid_size"   => [ "jpeg", "crop-invalid.jpg", 0, 50, 0, 0, 30, 20, false, null ],
            "invalid_crop"   => [ "jpeg", "crop-invalid.jpg", 100, 50, 0, 0, 0, 20, false, null ],
            "missing_source" => [ "missing", "crop-missing.jpg", 100, 50, 10, 10, 30, 20, false, null ],
        ];
    }


    #[DataProvider("providerGetContentType")]
    public function testGetContentType(int $imgType, string $expected): void {
        $this->assertSame($expected, Image::getContentType($imgType));
    }

    public static function providerGetContentType(): array {
        return [
            "gif"     => [ 1, "image/gif" ],
            "jpeg"    => [ 2, "image/jpeg" ],
            "png"     => [ 3, "image/png" ],
            "wbmp"    => [ 15, "image/wbmp" ],
            "xbm"     => [ 16, "image/xbm" ],
            "unknown" => [ 99, "image/unknown" ],
        ];
    }


    #[DataProvider("providerGetExtension")]
    public function testGetExtension(int $imgType, string $expected): void {
        $this->assertSame($expected, Image::getExtension($imgType));
    }

    public static function providerGetExtension(): array {
        return [
            "gif"     => [ 1, "gif" ],
            "jpeg"    => [ 2, "jpg" ],
            "png"     => [ 3, "png" ],
            "wbmp"    => [ 15, "bmp" ],
            "xbm"     => [ 16, "xbm" ],
            "unknown" => [ 99, "unknown" ],
        ];
    }


    #[DataProvider("providerCreateSrcImage")]
    public function testCreateSrcImage(int $imgType, string $token, bool $expected): void {
        $image = Image::createSrcImage($imgType, $this->files[$token] ?? "");

        if ($expected) {
            $this->assertInstanceOf(GdImage::class, $image);
            return;
        }

        $this->assertNull($image);
    }

    public static function providerCreateSrcImage(): array {
        return [
            "gif"        => [ 1, "gif", true ],
            "jpeg"       => [ 2, "jpeg", true ],
            "png"        => [ 3, "png", true ],
            "wrong_type" => [ 99, "jpeg", false ],
        ];
    }


    #[DataProvider("providerCreateDstImage")]
    public function testCreateDstImage(int $imgType, int $width, int $height, ?bool $expectedTransparent): void {
        $image = Image::createDstImage($imgType, $width, $height);

        if ($expectedTransparent === null) {
            $this->assertNull($image);
            return;
        }

        $this->assertInstanceOf(GdImage::class, $image);

        $color = imagecolorat($image, 0, 0);
        $alpha = imagecolorsforindex($image, $color)["alpha"];
        $this->assertSame($expectedTransparent ? 127 : 0, $alpha);
    }

    public static function providerCreateDstImage(): array {
        return [
            "gif"            => [ 1, 10, 10, true ],
            "jpeg"           => [ 2, 10, 10, false ],
            "png"            => [ 3, 10, 10, true ],
            "invalid_width"  => [ 2, 0, 10, null ],
            "invalid_height" => [ 3, 10, -1, null ],
        ];
    }


    #[DataProvider("providerCreateImage")]
    public function testCreateImage(int $imgType, string $fileName, bool $expected): void {
        $dstPath = $this->tmpDir . DIRECTORY_SEPARATOR . $fileName;
        $image   = imagecreatetruecolor(12, 8);

        $this->assertNotFalse($image);
        $result = Image::createImage($imgType, $image, $dstPath);
        $this->assertSame($expected, $result);

        if ($expected) {
            $this->assertFileExists($dstPath);
            $this->assertSame($imgType, Image::getType($dstPath));
            return;
        }

        $this->assertFileDoesNotExist($dstPath);
    }

    public static function providerCreateImage(): array {
        return [
            "gif"     => [ 1, "created.gif", true ],
            "jpeg"    => [ 2, "created.jpg", true ],
            "png"     => [ 3, "created.png", true ],
            "unknown" => [ 99, "created.bin", false ],
        ];
    }


    #[DataProvider("providerThumbnail")]
    public function testThumbnail(
        string $srcToken,
        string $dstName,
        int $width,
        int $height,
        string $action,
        bool $expected,
        ?array $expectedSize,
    ): void {
        $srcPath = $this->files[$srcToken] ?? "";
        $dstPath = $this->tmpDir . DIRECTORY_SEPARATOR . $dstName;
        $result  = Image::thumbnail($srcPath, $dstPath, $width, $height, $action);

        $this->assertSame($expected, $result);

        if ($expectedSize === null) {
            $this->assertFileDoesNotExist($dstPath);
            return;
        }

        $size = Image::getSize($dstPath);
        $this->assertSame($expectedSize, [ $size[0], $size[1], $size[2] ]);
    }

    public static function providerThumbnail(): array {
        return [
            "small_thumb_copies" => [ "png", "thumb-copy.png", 50, 50, Image::Thumb, true, [ 20, 10, 3 ] ],
            "maximum_jpeg"       => [ "jpeg", "thumb-maximum.jpg", 50, 50, Image::Maximum, true, [ 50, 25, 2 ] ],
            "thumb_large_png"    => [ "large", "thumb-large.png", 30, 30, Image::Thumb, true, [ 30, 30, 3 ] ],
        ];
    }
}
