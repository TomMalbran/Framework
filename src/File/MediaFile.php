<?php
namespace Framework\File;

use Framework\Framework;
use Framework\Request;
use Framework\Config\Config;
use Framework\File\File;
use Framework\File\Image;
use Framework\File\FileType;
use Framework\Schema\Database;
use Framework\Schema\Query;
use Framework\Utils\Strings;

/**
 * The Media Files
 */
class MediaFile {

    const Source = "source";
    const Thumbs = "thumbs";


    private static bool      $loaded = false;
    private static ?Database $db     = null;
    private static int       $id     = 0;

    /** @var array{}[] */
    private static array     $data   = [];


    /**
     * Loads the Media Data
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$db     = Framework::getDatabase();
        self::$data   = Framework::loadData(Framework::MediaData);
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
        $result = 0;

        if (!empty(self::$data["updates"])) {
            return false;
        }
        foreach (self::$data["updates"] as $field) {
            $updated = false;
            if (!empty($field["isJson"]) && $field["isJson"]) {
                $query   = Query::create($field["field"], "LIKE", ":\"$oldPath\"");
                $updated = self::$db->update($field["table"], [
                    $field["field"] => Query::replace($field["field"], $oldPath, $newPath),
                ], $query);
            } else {
                $query   = Query::create($field["field"], "=", $oldPath);
                $updated = self::$db->update($field["table"], [
                    $field["field"] => $newPath,
                ], $query);
            }
            if ($updated) {
                $result += 1;
            }
        }
        return $result > 0;
    }



    /**
     * Returns the Source Path using the ID
     * @param string ...$pathParts
     * @return string
     */
    public static function getPath(string ...$pathParts): string {
        return Framework::getFilesPath(self::Source, self::$id, ...$pathParts);
    }

    /**
     * Returns the Thumbs Path using the ID
     * @param string ...$pathParts
     * @return string
     */
    private static function getThumbPath(string ...$pathParts): string {
        return Framework::getFilesPath(self::Thumbs, self::$id, ...$pathParts);
    }

    /**
     * Returns the Source Url
     * @param string ...$pathParts
     * @return string
     */
    public static function getUrl(string ...$pathParts): string {
        return Config::getFileUrl(Framework::FilesDir, self::Source, self::$id, ...$pathParts);
    }

    /**
     * Returns the Thumb Url
     * @param string ...$pathParts
     * @return string
     */
    public static function getThumbUrl(string ...$pathParts): string {
        return Config::getFileUrl(Framework::FilesDir, self::Source, self::$id, ...$pathParts);
    }

    /**
     * Returns true if given Source File exists
     * @param string ...$pathParts
     * @return boolean
     */
    public static function exists(string ...$pathParts): bool {
        $path = self::getPath(...$pathParts);
        return File::exists($path);
    }



    /**
     * Returns all the Media Elements
     * @param string $mediaType Optional.
     * @param string $path      Optional.
     * @return array{}
     */
    public static function getList(string $mediaType = "", string $path = ""): array {
        $path   = !empty($path) && self::exists($path) ? $path : "";
        $source = self::getPath($path);
        $files  = File::getAllInDir($source);
        $source = File::addLastSlash($source);
        $list   = new FileList();

        foreach ($files as $file) {
            $fileName = Strings::replace($file, $source, "");
            if (MediaType::isValid($mediaType, $file, $fileName)) {
                $isDir      = FileType::isDir($file);
                $sourcePath = self::getPath($path, $fileName);
                $sourceUrl  = self::getUrl($path, $fileName);
                $thumbUrl   = self::getThumbUrl($path, $fileName);
                $filePath   = !empty($path) ? "{$path}/{$fileName}" : $fileName;
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
     * @param string $path
     * @param string $name
     * @return boolean
     */
    public static function createDir(string $path, string $name): bool {
        $source = self::getPath($path, $name);
        $thumbs = self::getThumbPath($path, $name);
        return File::createDir($source) && File::createDir($thumbs);
    }

    /**
     * Uploads a File
     * @param Request $request
     * @param string  $path
     * @return boolean
     */
    public static function uploadFile(Request $request, string $path): bool {
        $fileName = $request->getFileName("file");
        $tmpFile  = $request->getTmpName("file");
        $source   = self::getPath($path);

        if (!File::upload($source, $fileName, $tmpFile)) {
            return false;
        }
        if (!FileType::isImage($fileName)) {
            return true;
        }

        $src = self::getPath($path, $fileName);
        $dst = self::getThumbPath($path, $fileName);
        return Image::resize($src, $dst, 200, 200, Image::Resize);
    }

    /**
     * Deletes a Media Element
     * @param string $path
     * @param string $name
     * @return boolean
     */
    public static function deletePath(string $path, string $name): bool {
        $relPath = File::getPath($path, $name);
        $source  = self::getPath($path, $name);
        $thumbs  = self::getThumbPath($path, $name);

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
        $oldRelPath = File::removeFirstSlash(File::getPath($oldPath, $oldName));
        $newRelPath = File::removeFirstSlash(File::getPath($newPath, $newName));
        $oldSource  = self::getPath($oldPath, $oldName);
        $newSource  = self::getPath($newPath, $newName);
        $oldThumbs  = self::getThumbPath($oldPath, $oldName);
        $newThumbs  = self::getThumbPath($newPath, $newName);

        if (!File::move($oldSource, $newSource)) {
            return null;
        }
        if (FileType::isImage($oldName) && !File::move($oldThumbs, $newThumbs)) {
            return null;
        }

        return self::update($oldRelPath, $newRelPath);
    }



    /**
     * Creates the paths for the given ID
     * @param integer $id Optional.
     * @return string[]
     */
    public static function createPaths(int $id = 0): array {
        self::load();
        $result = [];
        $paths  = [ self::Source, self::Thumbs ];

        if (!empty(self::$data["paths"])) {
            $paths = array_merge($paths, self::$data["paths"]);
        }

        foreach ($paths as $pathDir) {
            $path = Framework::getFilesPath($pathDir);
            if (File::createDir($path)) {
                $result[] = $pathDir;
            }

            $path = Framework::getFilesPath($pathDir, $id);
            if (File::createDir($path)) {
                $result[] = "$pathDir/$id";
            }

            if (!empty(self::$data["directories"])) {
                foreach (self::$data["directories"] as $directory) {
                    $path = Framework::getFilesPath($pathDir, $id, $directory);
                    if (File::createDir($path)) {
                        $result[] = "$pathDir/$id/$directory";
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Ensures that the Paths are created
     * @return boolean
     */
    public static function ensurePaths(): bool {
        $paths = self::createPaths();

        if (!empty($paths)) {
            print("<br>Added <i>" . count($paths) . " media</i><br>");
            print(Strings::join($paths, ", ") . "<br>");
        } else {
            print("<br>No <i>media</i> added<br>");
        }
        return true;
    }
}
