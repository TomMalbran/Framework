<?php
namespace Framework\File;

use Framework\File\Image;
use Framework\Utils\Numbers;

use GdImage;

/**
 * The Picture Utils
 */
class Picture {

    private GdImage|null $image;

    public int $type;
    public int $width;
    public int $height;


    /**
     * Creates a Picture Element
     * @param string $path
     */
    public function __construct(string $path) {
        $size  = Image::getSize($path);

        $this->type   = Image::getType($path);
        $this->width  = $size[0];
        $this->height = $size[1];
        $this->image  = Image::createSrcImage($this->type, $path);
    }



    /**
     * Creates a Color for the given Image
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return int
     */
    public function createColor(int $red, int $green, int $blue): int {
        if ($this->image === null) {
            return 0;
        }
        if ($red < 0 || $red > 255 || $green < 0 || $green > 255 || $blue < 0 || $blue > 255) {
            return 0;
        }

        $result = imagecolorallocate($this->image, $red, $green, $blue);
        return $result !== false ? $result : 0;
    }

    /**
     * Writes Text to an Image
     * @param string $text
     * @param int    $x
     * @param int    $y
     * @param int    $color
     * @param string $fontFile
     * @param int    $fontSize
     * @param bool   $centered Optional.
     * @return bool
     */
    public function writeText(string $text, int $x, int $y, int $color, string $fontFile, int $fontSize, bool $centered = false): bool {
        if ($this->image === null) {
            return false;
        }

        if ($centered) {
            $textWidth = Image::getTextWidth($text, $fontFile, $fontSize);
            $x -= Numbers::roundInt($textWidth / 2);
        }

        $result = imagettftext($this->image, $fontSize, 0, $x, $y, $color, $fontFile, $text);
        return $result !== false;
    }

    /**
     * Prints the Picture
     * @param bool   $download Optional.
     * @param string $name     Optional.
     * @return bool
     */
    public function print(bool $download = false, string $name = "image"): bool {
        if ($this->image === null) {
            return false;
        }
        $contentType = Image::getContentType($this->type);

        header("Content-Type: $contentType");
        if ($download) {
            $extension = Image::getExtension($this->type);
            header("Content-Disposition: attachment; filename=\"$name.$extension\"");
        }

        Image::createImage($this->type, $this->image);
        return true;
    }
}
