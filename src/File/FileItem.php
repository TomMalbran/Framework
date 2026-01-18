<?php
namespace Framework\File;

use Framework\File\File;
use Framework\File\FileType;
use Framework\File\Image;

/**
 * The File Item
 */
class FileItem {

    public string $name         = "";
    public string $path         = "";
    public string $mvPath       = "";

    public bool   $canSelect    = false;
    public bool   $isBack       = false;
    public bool   $isDir        = false;
    public bool   $isImage      = false;
    public bool   $isTransparent = false;
    public bool   $isFile        = false;
    public bool   $isPDF         = false;
    public bool   $isAudio       = false;
    public bool   $isDocument    = false;

    public string $icon          = "";
    public string $source        = "";
    public string $url           = "";
    public string $thumb         = "";
    public int    $width         = 0;
    public int    $height        = 0;



    /**
     * Creates a File/Document instance
     * @param string $name
     * @param string $path
     * @param bool   $isDir
     * @param string $sourcePath
     * @param string $sourceUrl
     * @param string $thumbPath
     * @param string $thumbUrl
     * @return FileItem
     */
    public static function createFile(
        string $name,
        string $path,
        bool   $isDir,
        string $sourcePath,
        string $sourceUrl,
        string $thumbPath,
        string $thumbUrl,
    ): FileItem {
        $isImage = !$isDir && FileType::isImage($name) && File::exists($thumbPath);
        [ $imgWidth, $imgHeight ] = Image::getSize($sourcePath);

        $item = new FileItem();

        $item->name          = $name;
        $item->path          = $path;
        $item->mvPath        = $path !== "" ? $path : "/";

        $item->canSelect     = !$isDir;
        $item->isBack        = false;
        $item->isDir         = $isDir;
        $item->isImage       = $isImage;
        $item->isTransparent = Image::hasTransparency($sourcePath);
        $item->isFile        = !$isImage;
        $item->isPDF         = FileType::isPDF($name);
        $item->isAudio       = FileType::isAudio($name);
        $item->isDocument    = FileType::isDocument($name);

        $item->icon          = $isDir ? "directory" : FileType::getIcon($name);
        $item->source        = $sourceUrl;
        $item->url           = $sourceUrl;
        $item->thumb         = $thumbUrl;
        $item->width         = $imgWidth;
        $item->height        = $imgHeight;
        return $item;
    }

    /**
     * Creates a Back instance
     * @param string $path
     * @return FileItem
     */
    public static function createBack(string $path): FileItem {
        $dir  = File::getDirectory($path);
        $item = new FileItem();

        $item->name      = "...";
        $item->path      = $dir !== "." ? $dir : "";
        $item->mvPath    = $dir !== "." ? $dir : "/";
        $item->canSelect = false;
        $item->isBack    = true;
        $item->isFile    = true;
        $item->icon      = "back";
        return $item;
    }
}
