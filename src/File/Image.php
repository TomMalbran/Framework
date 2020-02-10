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
    private static $imageTrans = [ 1, 3 ];
    private static $imageFuncs = [
        1  => [ "imagecreatefromgif",  "imagegif"  ],
        2  => [ "imagecreatefromjpeg", "imagejpeg" ],
        3  => [ "imagecreatefrompng",  "imagepng"  ],
        15 => [ "imagecreatefromwbmp", "imagewbmp" ],
        16 => [ "imagecreatefromxbm",  "imagexbm"  ],
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
     * Returns true if the image type is invalid
     * @param string $file
     * @return boolean
     */
    public static function isValidType(string $file): bool {
        if (!empty($file)) {
            $type = exif_imagetype($file);
            return self::hasType($type);
        }
        return false;
    }

    /**
     * Returns the Size of the Image as [ width, height, type ]
     * @param string $file
     * @return array
     */
    public static function getSize(string $file): array {
        if (file_exists($path)) {
            return getimagesize($file);
        }
        return [ 0, 0, 0 ];
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
        $srcResize = self::$imageFuncs[$imgType][0]($src);
        $dstResize = self::createDestImage($imgType, $width, $height);
        imagecopyresampled($dstResize, $srcResize, 0, 0, $xCorner, $yCorner, $width, $height, $oldWidth, $oldHeight);
        
        // Create Image
        self::createImage($imgType, $dstResize, $dst);
        
        // Free Resources
        imagedestroy($srcResize);
        imagedestroy($dstResize);
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
        $srcResize = self::$imageFuncs[$imgType][0]($src);
        $dstResize = self::createDestImage($imgType, $resWidth, $resHeight);
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
        imagedestroy($dstCrop);
        return true;
    }

    /**
     * Creates the Destiny Image based on the Type
     * @param integer $imgType
     * @param integer $width
     * @param integer $height
     * @return mixed
     */
    private static function createDestImage(int $imgType, int $width, int $height) {
        $result = imagecreatetruecolor($width, $height);
        if (Arrays::contains(self::$imageTrans, $imgType)) {
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
     * @param mixed   $src
     * @param mixed   $dst
     * @return void
     */
    private static function createImage(int $imgType, $src, $dst) {
        if ($imgType == 2) {
            self::$imageFuncs[$imgType][1]($src, $dst, 90);
        } else {
            self::$imageFuncs[$imgType][1]($src, $dst);
        }
    }
}
