<?php
namespace Framework\File;

use Framework\File\Image;

/**
 * The Picture Utils
 */
class Picture {

    private mixed $image;

    public int $type;
    public int $width;
    public int $height;


    /**
     * Creates a Picture Element
     * @param string $path
     */
    public function __construct(string $path) {
        $size = Image::getSize($path);

        $this->type   = Image::getType($path);
        $this->width  = $size[0];
        $this->height = $size[1];
        $this->image  = Image::createSrcImage($this->type, $path);
    }



    /**
     * Creates a Color for the given Image
     * @param integer $red
     * @param integer $green
     * @param integer $blue
     * @return integer
     */
    public function createColor(int $red, int $green, int $blue): int {
        return imagecolorallocate($this->image, $red, $green, $blue);
    }

    /**
     * Writes Text to an Image
     * @param string  $text
     * @param integer $x
     * @param integer $y
     * @param integer $color
     * @param string  $fontFile
     * @param integer $fontSize
     * @param boolean $centered Optional.
     * @return boolean
     */
    public function writeText(string $text, int $x, int $y, int $color, string $fontFile, int $fontSize, bool $centered = false): bool {
        if ($centered) {
            $textWidth = Image::getTextWidth($text, $fontFile, $fontSize);
            $x -= $textWidth / 2;
        }
        $result = imagettftext($this->image, $fontSize, 0, $x, $y, $color, $fontFile, $text);
        return $result != false;
    }

    /**
     * Prints the Picture
     * @param boolean $download Optional.
     * @param string  $name     Optional.
     * @return void
     */
    public function print(bool $download = false, string $name = "image"): void {
        $contentType = Image::getContentType($this->type);

        header("Content-Type: $contentType");
        if ($download) {
            $extension = Image::getExtension($this->type);
            header("Content-Disposition: attachment; filename=\"$name.$extension\"");
        }
        Image::createImage($this->type, $this->image);
    }
}
