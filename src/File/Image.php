<?php
namespace Framework\File;

use Framework\Utils\Arrays;

/**
 * The Image Utils
 */
class Image {

    // The Resize Types
    const Resize  = "resize";
    const Maximum = "maximum";
    const Thumb   = "thumb";

    // The Image Types and Functions
    private static $imageTypes = [ 1, 2, 3, 15, 16 ];
    private static $transTypes = [ 1, 3 ];
    private static $imageData  = [
        1  => [ "imagecreatefromgif",  "imagegif",  "image/gif",  "gif" ],
        2  => [ "imagecreatefromjpeg", "imagejpeg", "image/jpeg", "jpg" ],
        3  => [ "imagecreatefrompng",  "imagepng",  "image/png",  "png" ],
        15 => [ "imagecreatefromwbmp", "imagewbmp", "image/wbmp", "bmp" ],
        16 => [ "imagecreatefromxbm",  "imagexbm",  "image/xbm",  "xbm" ],
    ];



    /**
     * Returns true if the given type is valid
     * @param integer $type
     * @return boolean
     */
    public static function hasType(int $type): bool {
        return Arrays::contains(self::$imageTypes, $type);
    }

    /**
     * Returns true if the Image type is invalid
     * @param string $file
     * @return boolean
     */
    public static function isValidType(string $file): bool {
        $type = self::getType($file);
        return self::hasType($type);
    }

    /**
     * Returns the Type of the Image
     * @param string $file
     * @return integer
     */
    public static function getType(string $file): int {
        if (file_exists($file)) {
            return exif_imagetype($file);
        }
        return 0;
    }

    /**
     * Returns the Size of the Image as [ width, height, type ]
     * @param string $file
     * @return array
     */
    public static function getSize(string $file): array {
        if (file_exists($file)) {
            return getimagesize($file);
        }
        return [ 0, 0, 0 ];
    }

    /**
     * Returns the Orientation for the given Image
     * @param string $file
     * @return integer
     */
    public static function getOrientation(string $file): int {
        if (!file_exists($file)) {
            return 0;
        }
        $exif = @exif_read_data($file);
        if ($exif !== false && !empty($exif["Orientation"])) {
            return $exif["Orientation"];
        }
        return 0;
    }

    /**
     * Returns the Width of the given Text
     * @param string  $text
     * @param string  $fontFile
     * @param integer $fontSize
     * @return integer
     */
    public static function getTextWidth(string $text, string $fontFile, int $fontSize): int {
        $dimensions = imagettfbbox($fontSize, 0, $fontFile, $text);
        return abs($dimensions[4] - $dimensions[0]);
    }



    /**
     * Resamples the given image
     * @param string  $src
     * @param string  $dst
     * @param integer $orientation Optional.
     * @return boolean
     */
    public static function resample(string $src, string $dst, int $orientation = null): bool {
        if ($orientation == null) {
            $orientation = self::getOrientation($src);
        }
        if (empty($orientation)) {
            return false;
        }

        [ $imgWidth, $imgHeight, $imgType ] = getimagesize($src);
        if (!self::hasType($imgType)) {
            return false;
        }

        // Resample
        $srcImage = self::createSrcImage($imgType, $src);
        $dstImage = self::createDstImage($imgType, $imgWidth, $imgHeight);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $imgWidth, $imgHeight, $imgWidth, $imgHeight);

        // Fix Orientation
        switch ($orientation) {
        case 3:
            $dstImage = imagerotate($dstImage, 180, 0);
            break;
        case 6:
            $dstImage = imagerotate($dstImage, -90, 0);
            break;
        case 8:
            $dstImage = imagerotate($dstImage, 90, 0);
            break;
        }

        // Create the Image
        self::createImage($imgType, $dstImage, $dst);

        // Free Resources
        imagedestroy($srcImage);
        return true;
    }

    /**
     * Resizes an Image
     * @param string  $src
     * @param string  $dst
     * @param integer $width
     * @param integer $height
     * @param string  $action
     * @return boolean
     */
    public static function resize(
        string $src,
        string $dst,
        int    $width,
        int    $height,
        string $action
    ): bool {
        [ $imgWidth, $imgHeight, $imgType ] = getimagesize($src);
        if (!self::hasType($imgType)) {
            return false;
        }

        switch ($action) {
        // Resize to a Size respecting aspect ratio
        case self::Resize:
            $oldWidth  = $imgWidth;
            $oldHeight = $imgHeight;
            $xCorner   = 0;
            $yCorner   = 0;

            if ($imgWidth > $imgHeight) {
                $height = $imgHeight * $width / $imgWidth;
            } else {
                $width  = $imgWidth * $height / $imgHeight;
            }
            break;

        // Resize if the image is greater
        case self::Maximum:
            $xCorner = 0;
            $yCorner = 0;

            if ($imgWidth > $width || $imgHeight > $height) {
                $oldWidth  = $imgWidth;
                $oldHeight = $imgHeight;

                if ($imgWidth > $imgHeight) {
                    $height = $imgHeight * $width / $imgWidth;
                } else {
                    $width  = $imgWidth * $height / $imgHeight;
                }
            } else {
                $width     = $imgWidth;
                $height    = $imgHeight;
                $oldWidth  = $imgWidth;
                $oldHeight = $imgHeight;
            }
            break;

        // Resize to a specific Size
        case self::Thumb:
            $xScale = $imgWidth  / $width;
            $yScale = $imgHeight / $height;

            if ($yScale < $xScale) {
                $oldWidth  = round($width  * $yScale);
                $oldHeight = round($height * $yScale);
                $xCorner   = ($imgWidth - $oldWidth) / 2;
                $yCorner   = 0;
            } else {
                $oldWidth  = round($width  * $xScale);
                $oldHeight = round($height * $xScale);
                $xCorner   = 0;
                $yCorner   = ($imgHeight - $oldHeight) / 2;
            }
            break;
        }

        // Creation Process
        $srcResize = self::createSrcImage($imgType, $src);
        $dstResize = self::createDstImage($imgType, $width, $height);
        imagecopyresampled($dstResize, $srcResize, 0, 0, $xCorner, $yCorner, $width, $height, $oldWidth, $oldHeight);

        // Create Image
        self::createImage($imgType, $dstResize, $dst);

        // Free Resources
        imagedestroy($srcResize);
        return true;
    }

    /**
     * Resizes and Crops an Image
     * @param string  $src
     * @param string  $dst
     * @param integer $resWidth
     * @param integer $resHeight
     * @param integer $cropX
     * @param integer $cropY
     * @param integer $cropWidth
     * @param integer $cropHeight
     * @return boolean
     */
    public static function resizeCrop(
        string $src,
        string $dst,
        int    $resWidth,
        int    $resHeight,
        int    $cropX,
        int    $cropY,
        int    $cropWidth,
        int    $cropHeight
    ): bool {
        [ $imgWidth, $imgHeight, $imgType ] = getimagesize($src);
        if (!self::hasType($imgType)) {
            return false;
        }

        // Resize Image
        $srcResize = self::createSrcImage($imgType, $src);
        $dstResize = self::createDstImage($imgType, $resWidth, $resHeight);
        imagecopyresampled($dstResize, $srcResize, 0, 0, 0, 0, $resWidth, $resHeight, $imgWidth, $imgHeight);

        // Crop Image
        $dstCrop = imagecreatetruecolor($cropWidth, $cropHeight);
        $bgColor = imagecolorallocate($dstCrop, 255, 255, 255);
        imagefill($dstCrop, 0, 0, $bgColor);
        imagecopy($dstCrop, $dstResize, -$cropX, -$cropY, 0, 0, $resWidth, $resHeight);

        // Create Image
        self::createImage($imgType, $dstCrop, $dst);

        // Free Resources
        imagedestroy($srcResize);
        imagedestroy($dstResize);
        return true;
    }



    /**
     * Returns the Image Content Type
     * @param integer $imgType
     * @return string
     */
    public static function getContentType(int $imgType) {
        return self::$imageData[$imgType][2];
    }

    /**
     * Returns the Image Extension
     * @param integer $imgType
     * @return string
     */
    public static function getExtension(int $imgType) {
        return self::$imageData[$imgType][3];
    }

    /**
     * Creates an Image based on the Type
     * @param integer $imgType
     * @param mixed   $image
     * @return mixed
     */
    public static function createSrcImage(int $imgType, $image) {
        return self::$imageData[$imgType][0]($image);
    }

    /**
     * Creates the Destination Image based on the Type
     * @param integer $imgType
     * @param integer $width
     * @param integer $height
     * @return mixed
     */
    public static function createDstImage(int $imgType, int $width, int $height) {
        $result = imagecreatetruecolor($width, $height);
        if (Arrays::contains(self::$transTypes, $imgType)) {
            imagealphablending($result, false);
            imagesavealpha($result,true);
            $transparent = imagecolorallocatealpha($result, 255, 255, 255, 127);
            imagefilledrectangle($result, 0, 0, $width, $height, $transparent);
        }
        return $result;
    }

    /**
     * Creates an Image based on the Type
     * @param integer $imgType
     * @param mixed   $image
     * @param string  $fileName Optional.
     * @param integer $quality  Optional.
     * @return void
     */
    public static function createImage(int $imgType, $image, string $fileName = null, int $quality = 90) {
        if ($imgType == 2) {
            self::$imageData[$imgType][1]($image, $fileName, $quality);
        } else {
            self::$imageData[$imgType][1]($image, $fileName);
        }
        imagedestroy($image);
    }

    /**
     * Destroys the Image
     * @param mixed $image
     * @return void
     */
    public function destroy($image) {
        imagedestroy($image);
    }
}
