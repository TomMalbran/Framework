<?php
namespace Framework\File;

use Framework\Request;
use Framework\File\File;
use Framework\File\FileType;
use Framework\File\FileItem;
use Framework\File\Image;
use Framework\System\Path;
use Framework\System\MediaSchema;
use Framework\Utils\Strings;

/**
 * The Media Files
 */
class MediaFile {

    private static int $id = 0;

    /**
     * Sets the Current ID
     * @param int $id
     * @return bool
     */
    public static function setID(int $id): bool {
        self::$id = $id;
        return true;
    }



    /**
     * Returns the Source Path using the ID
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getPath(string|int ...$pathParts): string {
        return Path::getSourcePath(self::$id, ...$pathParts);
    }

    /**
     * Returns the Thumbs Path using the ID
     * @param string|int ...$pathParts
     * @return string
     */
    private static function getThumbPath(string|int ...$pathParts): string {
        return Path::getThumbsPath(self::$id, ...$pathParts);
    }

    /**
     * Returns the Source Url
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getUrl(string|int ...$pathParts): string {
        return Path::getSourceUrl(self::$id, ...$pathParts);
    }

    /**
     * Returns the Thumb Url
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getThumbUrl(string|int ...$pathParts): string {
        return Path::getThumbsUrl(self::$id, ...$pathParts);
    }

    /**
     * Returns true if given Source File exists
     * @param string|int ...$pathParts
     * @return bool
     */
    public static function exists(string|int ...$pathParts): bool {
        $path = self::getPath(...$pathParts);
        return File::exists($path);
    }



    /**
     * Returns all the Media Elements
     * @param string $mediaType Optional.
     * @param string $path      Optional.
     * @param string $basePath  Optional.
     * @return array{list:FileItem[],path:string}
     */
    public static function getList(string $mediaType = "", string $path = "", string $basePath = ""): array {
        $path   = $path !== "" && self::exists($basePath, $path) ? $path : "";
        $source = self::getPath($basePath, $path);
        $files  = File::getAllInDir($source);
        $source = File::addLastSlash($source);
        $list   = new FileList();

        foreach ($files as $file) {
            $fileName = Strings::replace($file, $source, "");
            if (MediaType::isValid($mediaType, $file, $fileName)) {
                $list->add(
                    name       : $fileName,
                    path       : File::parsePath($path, $fileName),
                    isDir      : FileType::isDir($file),
                    sourcePath : self::getPath($basePath, $path, $fileName),
                    sourceUrl  : self::getUrl($basePath, $path, $fileName),
                    thumbPath  : self::getThumbPath($basePath, $path, $fileName),
                    thumbUrl   : self::getThumbUrl($basePath, $path, $fileName),
                );
            }
        }
        if ($path !== "" && $path !== "/") {
            $list->addBack($path);
        }

        return [
            "list" => $list->getSorted(),
            "path" => File::removeFirstSlash($path),
        ];
    }

    /**
     * Creates a Directory
     * @param string|int ...$pathParts
     * @return bool
     */
    public static function createDir(string|int ...$pathParts): bool {
        $source = self::getPath(...$pathParts);
        $thumbs = self::getThumbPath(...$pathParts);
        return File::createDir($source) && File::createDir($thumbs);
    }

    /**
     * Uploads a File
     * @param Request    $request
     * @param string|int ...$pathParts
     * @return bool
     */
    public static function uploadFile(Request $request, string|int ...$pathParts): bool {
        $fileName = $request->getFileName("file");
        $tmpFile  = $request->getTmpName("file");
        $source   = self::getPath(...$pathParts);

        if (!File::upload($source, $fileName, $tmpFile)) {
            return false;
        }
        if (!FileType::isImage($fileName)) {
            return true;
        }

        $pathParts[] = $fileName;
        $src = self::getPath(...$pathParts);
        $dst = self::getThumbPath(...$pathParts);
        return Image::resize($src, $dst, 200, 200, Image::Resize);
    }

    /**
     * Deletes a Media Element
     * @param string|int ...$pathParts
     * @return bool
     */
    public static function deletePath(string|int ...$pathParts): bool {
        $relPath = File::parsePath(...$pathParts);
        $source  = self::getPath(...$pathParts);
        $thumbs  = self::getThumbPath(...$pathParts);

        if (!File::deleteDir($source) || !File::deleteDir($thumbs)) {
            return false;
        }
        return MediaSchema::updatePaths($relPath, "");
    }



    /**
     * Renames a File/Directory
     * @param string $path
     * @param string $oldName
     * @param string $newName
     * @return bool
     */
    public static function renamePath(string $path, string $oldName, string $newName): bool {
        return self::updatePath($path, $path, $oldName, $newName);
    }

    /**
     * Moves a File/Directory
     * @param string $oldPath
     * @param string $newPath
     * @param string $name
     * @return bool
     */
    public static function movePath(string $oldPath, string $newPath, string $name): bool {
        return self::updatePath($oldPath, $newPath, $name, $name);
    }

    /**
     * Moves or Renames a File/Directory
     * @param string $oldPath
     * @param string $newPath
     * @param string $oldName
     * @param string $newName
     * @return bool
     */
    private static function updatePath(string $oldPath, string $newPath, string $oldName, string $newName): bool {
        $oldRelPath = File::removeFirstSlash(File::parsePath($oldPath, $oldName));
        $newRelPath = File::removeFirstSlash(File::parsePath($newPath, $newName));
        $oldSource  = self::getPath($oldPath, $oldName);
        $newSource  = self::getPath($newPath, $newName);
        $oldThumbs  = self::getThumbPath($oldPath, $oldName);
        $newThumbs  = self::getThumbPath($newPath, $newName);

        if (!File::move($oldSource, $newSource)) {
            return false;
        }
        if (FileType::isImage($oldName)) {
            if (!File::exists($oldThumbs)) {
                if (!Image::resize($oldSource, $oldThumbs, 200, 200, Image::Resize)) {
                    return false;
                }
            } elseif (!File::move($oldThumbs, $newThumbs)) {
                return false;
            }
        }

        return MediaSchema::updatePaths($oldRelPath, $newRelPath);
    }
}
