<?php
namespace Framework\File;

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
     * Returns true if the image type is invalid
     * @param string $file
     * @return boolean
     */
    public static function isValidType($file) {
        if (!empty($file)) {
            $type = exif_imagetype($file);
            return in_array($type, self::$imageTypes);
        }
        return false;
    }

    /**
     * Returns the Size of the Image as [ width, height, type ]
     * @param string $file
     * @return array
     */
    public static function getSize($file) {
        return getimagesize($file);
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
    public static function resize($src, $dst, $width, $height, $action) {
        [ $imgWidth, $imgHeight, $imgType ] = getimagesize($src);
        if (!in_array($imgType, self::$imageTypes)) {
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
                $newWidth  = round($width  * $xScale);
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
    public static function resizeCrop($src, $dst, $resWidth, $resHeight, $cropX, $cropY, $cropWidth, $cropHeight) {
        [ $imgWidth, $imgHeight, $imgType ] = getimagesize($src);
        if (!in_array($imgType, self::$imageTypes)) {
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
    private static function createDestImage($imgType, $width, $height) {
        $result = imagecreatetruecolor($width, $height);
        if (in_array($imgType, self::$imageTrans)) {
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
    private static function createImage($imgType, $src, $dst) {
        if ($imgType == 2) {
            self::$imageFuncs[$imgType][1]($src, $dst, 90);
        } else {
            self::$imageFuncs[$imgType][1]($src, $dst);
        }
    }
}
