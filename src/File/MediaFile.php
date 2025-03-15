<?php
namespace Framework\File;

use Framework\Framework;
use Framework\Request;
use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\File\File;
use Framework\File\FileType;
use Framework\File\Image;
use Framework\Database\Factory;
use Framework\Database\Assign;
use Framework\Database\Query;
use Framework\System\Path;
use Framework\Utils\Strings;

/**
 * The Media Files
 */
class MediaFile {

    private static bool  $loaded = false;
    private static int   $id     = 0;

    /** @var array{}[] */
    private static array $data   = [];


    /**
     * Loads the Media Data
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        self::$loaded = true;
        self::$data   = Discovery::loadData(DataFile::Files);
        return true;
    }

    /**
     * Sets the Current ID
     * @param integer $id
     * @return boolean
     */
    public static function setID(int $id): bool {
        self::$id = $id;
        return true;
    }

    /**
     * Updates the Paths in the Database
     * @param string $oldPath
     * @param string $newPath
     * @return boolean
     */
    public static function update(string $oldPath, string $newPath): bool {
        self::load();
        if (empty(self::$data["media"])) {
            return true;
        }

        $files = [
            [
                "old" => $oldPath,
                "new" => $newPath,
            ],
            [
                "old" => File::addFirstSlash($oldPath),
                "new" => File::addFirstSlash($newPath),
            ],
        ];

        $db = Framework::getDatabase();
        foreach (self::$data["media"] as $field) {
            $structure = Factory::getStructure($field["schema"]);

            foreach ($files as $file) {
                $old = $file["old"];
                $new = $file["new"];

                if (!empty($field["replace"])) {
                    $query = Query::create($field["field"], "LIKE", "\"$old\"");
                    $db->update($structure->table, [
                        $field["field"] => Assign::replace($old, $new),
                    ], $query);
                } else {
                    $query = Query::create($field["field"], "=", $old);
                    $db->update($structure->table, [
                        $field["field"] => $new,
                    ], $query);
                }
            }
        }
        return true;
    }



    /**
     * Returns the Source Path using the ID
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getPath(string|int ...$pathParts): string {
        return Path::getSourcePath(self::$id, ...$pathParts);
    }

    /**
     * Returns the Thumbs Path using the ID
     * @param string|integer ...$pathParts
     * @return string
     */
    private static function getThumbPath(string|int ...$pathParts): string {
        return Path::getThumbsPath(self::$id, ...$pathParts);
    }

    /**
     * Returns the Source Url
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getUrl(string|int ...$pathParts): string {
        return Path::getSourceUrl(self::$id, ...$pathParts);
    }

    /**
     * Returns the Thumb Url
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getThumbUrl(string|int ...$pathParts): string {
        return Path::getThumbsUrl(self::$id, ...$pathParts);
    }

    /**
     * Returns true if given Source File exists
     * @param string|integer ...$pathParts
     * @return boolean
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
     * @return array{list:array,path:string}
     */
    public static function getList(string $mediaType = "", string $path = "", string $basePath = ""): array {
        $path   = !empty($path) && self::exists($basePath, $path) ? $path : "";
        $source = self::getPath($basePath, $path);
        $files  = File::getAllInDir($source);
        $source = File::addLastSlash($source);
        $list   = new FileList();

        foreach ($files as $file) {
            $fileName = Strings::replace($file, $source, "");
            if (MediaType::isValid($mediaType, $file, $fileName)) {
                $isDir      = FileType::isDir($file);
                $sourcePath = self::getPath($basePath, $path, $fileName);
                $sourceUrl  = self::getUrl($basePath, $path, $fileName);
                $thumbUrl   = self::getThumbUrl($basePath, $path, $fileName);
                $filePath   = File::parsePath($path, $fileName);
                $list->add($fileName, $filePath, $isDir, $sourcePath, $sourceUrl, $thumbUrl);
            }
        }
        if (!empty($path) && $path !== "/") {
            $list->addBack($path);
        }

        return [
            "list" => $list->getSorted(),
            "path" => File::removeFirstSlash($path),
        ];
    }

    /**
     * Creates a Directory
     * @param string|integer ...$pathParts
     * @return boolean
     */
    public static function createDir(string|int ...$pathParts): bool {
        $source = self::getPath(...$pathParts);
        $thumbs = self::getThumbPath(...$pathParts);
        return File::createDir($source) && File::createDir($thumbs);
    }

    /**
     * Uploads a File
     * @param Request         $request
     * @param string|integer  ...$pathParts
     * @return boolean
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
     * @param string|integer ...$pathParts
     * @return boolean
     */
    public static function deletePath(string|int ...$pathParts): bool {
        $relPath = File::parsePath(...$pathParts);
        $source  = self::getPath(...$pathParts);
        $thumbs  = self::getThumbPath(...$pathParts);

        if (!File::deleteDir($source) || !File::deleteDir($thumbs)) {
            return false;
        }
        return self::update($relPath, "");
    }



    /**
     * Renames a File/Directory
     * @param string $path
     * @param string $oldName
     * @param string $newName
     * @return boolean
     */
    public static function renamePath(string $path, string $oldName, string $newName): bool {
        return self::updatePath($path, $path, $oldName, $newName);
    }

    /**
     * Moves a File/Directory
     * @param string $oldPath
     * @param string $newPath
     * @param string $name
     * @return boolean
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
     * @return boolean
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

        return self::update($oldRelPath, $newRelPath);
    }
}
