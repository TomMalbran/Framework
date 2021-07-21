<?php
namespace Framework\File;

use Framework\File\Image;
use Framework\File\FileType;

/**
 * The FileList wrapper
 */
class FileList {

    private $list = [];


    /**
     * Adds a File/Directory
     * @param string  $name
     * @param string  $path
     * @param boolean $isDir
     * @param string  $sourcePath
     * @param string  $sourceUrl
     * @param string  $thumbUrl
     * @return void
     */
    public function add(string $name, string $path, bool $isDir, string $sourcePath, string $sourceUrl, string $thumbUrl): void {
        $isImage   = !$isDir && FileType::isImage($name);
        $imgWidth  = 0;
        $imgHeight = 0;
        if ($isImage && file_exists($sourcePath)) {
            [ $imgWidth, $imgHeight ] = getimagesize($sourcePath);
        }
        $this->list[] = [
            "name"          => $name,
            "path"          => $path,
            "mvPath"        => !empty($path) ? $path : "/",
            "canSelect"     => !$isDir,
            "isBack"        => false,
            "isDir"         => $isDir,
            "isImage"       => $isImage,
            "isTransparent" => $isImage && FileType::isPNG($name),
            "isFile"        => !$isImage,
            "isPDF"         => FileType::isPDF($name),
            "isAudio"       => FileType::isAudio($name),
            "isDocument"    => FileType::isDocument($name),
            "icon"          => $isDir ? "directory" : FileType::getIcon($name),
            "source"        => $sourceUrl,
            "url"           => $sourceUrl,
            "thumb"         => $thumbUrl,
            "width"         => $imgWidth,
            "height"        => $imgHeight,
        ];
    }

    /**
     * Adds the Go Back element
     * @param string $path
     * @return void
     */
    public function addBack(string $path): void {
        $dir = dirname($path);
        $this->list[] = [
            "name"      => "...",
            "path"      => $dir != "." ? $dir : "",
            "mvPath"    => $dir != "." ? $dir : "/",
            "canSelect" => false,
            "isBack"    => true,
            "isFile"    => true,
            "icon"      => "back",
        ];
    }



    /**
     * Returns the List
     * @return array
     */
    public function get(): array {
        return $this->list;
    }

    /**
     * Sorts and restuns the List
     * @return array
     */
    public function getSorted(): array {
        usort($this->list, function ($a, $b) {
            // Back goes first
            if ($a["isBack"] && !$b["isBack"]) {
                return -1;
            }
            if (!$a["isBack"] && $b["isBack"]) {
                return 1;
            }

            // Directories go on top
            if ($a["isDir"] && !$b["isDir"]) {
                return -1;
            }
            if (!$a["isDir"] && $b["isDir"]) {
                return 1;
            }

            // If the type is the same sort by name
            return strnatcasecmp($a["name"], $b["name"]);
        });

        return $this->list;
    }
}
