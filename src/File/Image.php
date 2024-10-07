<?php
namespace Framework\File;

use Framework\File\Path;
use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use Imagick;
use Exception;

/**
 * The Image Utils
 */
class Image {

    // The Resize Types
    const Resize  = "resize";
    const Maximum = "maximum";
    const Thumb   = "thumb";



    /** @var int[] The image types */
    private static array $imageTypes = [ 1, 2, 3, 15, 16 ];

    /** @var int[] The transparent image type */
    private static array $transTypes = [ 1, 3 ];

    /** @var array{}[] */
    private static array $imageData  = [
        1  => [ "imagecreatefromgif",  "imagegif",  "image/gif",  "gif" ],
        2  => [ "imagecreatefromjpeg", "imagejpeg", "image/jpeg", "jpg" ],
        3  => [ "imagecreatefrompng",  "imagepng",  "image/png",  "png" ],
        15 => [ "imagecreatefromwbmp", "imagewbmp", "image/wbmp", "bmp" ],
        16 => [ "imagecreatefromxbm",  "imagexbm",  "image/xbm",  "xbm" ],
    ];



    /**
     * Returns true if the given type is valid
     * @param integer $fileType
     * @return boolean
     */
    public static function hasType(int $fileType): bool {
        return Arrays::contains(self::$imageTypes, $fileType);
    }

    /**
     * Returns true if the Image type is invalid
     * @param string $fileName
     * @return boolean
     */
    public static function isValidType(string $fileName): bool {
        $type = self::getType($fileName);
        return self::hasType($type);
    }

    /**
     * Returns the Type of the Image
     * @param string $fileName
     * @return integer
     */
    public static function getType(string $fileName): int {
        return exif_imagetype($fileName);
    }

    /**
     * Returns the Mime Type of the Image
     * @param string $fileUrl
     * @return string
     */
    public static function getMimeType(string $fileUrl): string {
        $fileUrl   = Strings::encodeUrl($fileUrl);
        $imageType = exif_imagetype($fileUrl);
        return image_type_to_mime_type($imageType);
    }

    /**
     * Returns the Size of the Image as [ width, height, type ]
     * @param string ...$pathParts
     * @return int[]
     */
    public static function getSize(string ...$pathParts): array {
        if (!File::exists(...$pathParts)) {
            return [ 0, 0, 0 ];
        }

        $filePath = Path::parsePath(...$pathParts);
        $size     = getimagesize($filePath);
        if ($size === false) {
            return [ 0, 0, 0 ];
        }
        return $size;
    }

    /**
     * Returns the Size of the Image as [ width, height, type ]
     * @param string $fileUrl
     * @return int[]
     */
    public static function getSizeFromUrl(string $fileUrl): array {
        $fileUrl = Strings::encodeUrl($fileUrl);
        $size    = getimagesize($fileUrl);
        if ($size === false) {
            return [ 0, 0, 0 ];
        }
        return $size;
    }

    /**
     * Returns the Orientation for the given Image
     * @param string ...$pathParts
     * @return integer
     */
    public static function getOrientation(string ...$pathParts): int {
        if (!File::exists(...$pathParts)) {
            return 0;
        }

        $filePath = Path::parsePath(...$pathParts);
        $exif     = @exif_read_data($filePath);
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
     * @param string       $src
     * @param string       $dst
     * @param integer|null $orientation Optional.
     * @return boolean
     */
    public static function resample(string $src, string $dst, ?int $orientation = null): bool {
        if ($orientation == null) {
            $orientation = self::getOrientation($src);
        }
        if (empty($orientation)) {
            return false;
        }

        [ $imgWidth, $imgHeight, $imgType ] = self::getSize($src);
        if (!self::hasType($imgType)) {
            return false;
        }

        // Resample
        $srcImage = self::createSrcImage($imgType, $src);
        $dstImage = self::createDstImage($imgType, $imgWidth, $imgHeight);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $imgWidth, $imgHeight, $imgWidth, $imgHeight);

        // Fix Orientation
        $dstImage = match ($orientation) {
            3 => imagerotate($dstImage, 180, 0),
            6 => imagerotate($dstImage, -90, 0),
            8 => imagerotate($dstImage, 90, 0),
        };

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
        [ $imgWidth, $imgHeight, $imgType ] = self::getSize($src);
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
                $height = round($imgHeight * $width / $imgWidth);
            } else {
                $width  = round($imgWidth * $height / $imgHeight);
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
                    $height = round($imgHeight * $width / $imgWidth);
                } else {
                    $width  = round($imgWidth * $height / $imgHeight);
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
                $xCorner   = round(($imgWidth - $oldWidth) / 2);
                $yCorner   = 0;
            } else {
                $oldWidth  = round($width  * $xScale);
                $oldHeight = round($height * $xScale);
                $xCorner   = 0;
                $yCorner   = round(($imgHeight - $oldHeight) / 2);
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
        [ $imgWidth, $imgHeight, $imgType ] = self::getSize($src);
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
    public static function getContentType(int $imgType): string {
        return self::$imageData[$imgType][2];
    }

    /**
     * Returns the Image Extension
     * @param integer $imgType
     * @return string
     */
    public static function getExtension(int $imgType): string {
        return self::$imageData[$imgType][3];
    }

    /**
     * Creates an Image based on the Type
     * @param integer $imgType
     * @param mixed   $image
     * @return mixed
     */
    public static function createSrcImage(int $imgType, mixed $image): mixed {
        return self::$imageData[$imgType][0]($image);
    }

    /**
     * Creates the Destination Image based on the Type
     * @param integer $imgType
     * @param integer $width
     * @param integer $height
     * @return mixed
     */
    public static function createDstImage(int $imgType, int $width, int $height): mixed {
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
     * @param integer     $imgType
     * @param mixed       $image
     * @param string|null $fileName Optional.
     * @param integer     $quality  Optional.
     * @return boolean
     */
    public static function createImage(int $imgType, mixed $image, ?string $fileName = null, int $quality = 90): bool {
        if ($imgType == 2) {
            self::$imageData[$imgType][1]($image, $fileName, $quality);
        } else {
            self::$imageData[$imgType][1]($image, $fileName);
        }
        return imagedestroy($image);
    }

    /**
     * Destroys the Image
     * @param mixed $image
     * @return boolean
     */
    public static function destroy(mixed $image): bool {
        return imagedestroy($image);
    }



    /**
     * Creates a Thumbnail using ImageMagick
     * @param string  $src
     * @param string  $dst
     * @param integer $width
     * @param integer $height
     * @param string  $action
     * @return boolean
     */
    public static function thumbnail(
        string $src,
        string $dst,
        int    $width,
        int    $height,
        string $action
    ): bool {
        $bestFit = $action === self::Maximum;
        $fill    = $action === self::Thumb;

        // Do not resize if the image is smaller than the requested size
        [ $imgWidth, $imgHeight, $imgType ] = self::getSize($src);
        if ($bestFit && $imgWidth <= $width && $imgHeight <= $height) {
            return true;
        }

        // Use the normal resize if Imagick is not available
        if (!class_exists("Imagick")) {
            return self::resize($src, $dst, $width, $height, $action);
        }

        // Try to resize with Imagick
        try {
            $image = new Imagick($src);
            $image->thumbnailImage($width, $height, $bestFit, $fill);
            $image->writeImage($dst);
            return true;
        } catch (Exception $e) {
            $error = $e->getMessage();

            // Try to resize with GD if there is an error with JPEG
            if (Strings::contains($error, "Not a JPEG")) {
                return self::resize($src, $dst, $width, $height, $action);
            }

            // Log the error
            trigger_error("Imagick Error: " . $error, E_USER_ERROR);
            return false;
        }
    }
}
