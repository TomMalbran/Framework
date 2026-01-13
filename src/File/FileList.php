<?php
namespace Framework\File;

use Framework\File\FileItem;
use Framework\Utils\Arrays;

/**
 * The FileList wrapper
 */
class FileList {

    /** @var FileItem[] */
    private array $list = [];


    /**
     * Adds a File/Directory
     * @param string  $name
     * @param string  $path
     * @param boolean $isDir
     * @param string  $sourcePath
     * @param string  $sourceUrl
     * @param string  $thumbPath
     * @param string  $thumbUrl
     * @return FileList
     */
    public function add(
        string $name,
        string $path,
        bool   $isDir,
        string $sourcePath,
        string $sourceUrl,
        string $thumbPath,
        string $thumbUrl,
    ): FileList {
        $this->list[] = FileItem::createFile(
            name:       $name,
            path:       $path,
            isDir:      $isDir,
            sourcePath: $sourcePath,
            sourceUrl:  $sourceUrl,
            thumbPath:  $thumbPath,
            thumbUrl:   $thumbUrl,
        );
        return $this;
    }

    /**
     * Adds the Go Back element
     * @param string $path
     * @return FileList
     */
    public function addBack(string $path): FileList {
        $this->list[] = FileItem::createBack($path);
        return $this;
    }



    /**
     * Returns the List
     * @return FileItem[]
     */
    public function get(): array {
        return $this->list;
    }

    /**
     * Sorts and returns the List
     * @return FileItem[]
     */
    public function getSorted(): array {
        return Arrays::sort($this->list, function (FileItem $a, FileItem $b) {
            // Back goes first
            if ($a->isBack && !$b->isBack) {
                return -1;
            }
            if (!$a->isBack && $b->isBack) {
                return 1;
            }

            // Directories go on top
            if ($a->isDir && !$b->isDir) {
                return -1;
            }
            if (!$a->isDir && $b->isDir) {
                return 1;
            }

            // If the type is the same sort by name
            return strnatcasecmp($a->name, $b->name);
        });
    }
}
