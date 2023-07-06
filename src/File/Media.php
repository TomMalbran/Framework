<?php
namespace Framework\File;

use Framework\Framework;
use Framework\Request;
use Framework\File\Path;
use Framework\File\File;
use Framework\File\Image;
use Framework\File\FileType;
use Framework\Schema\Database;
use Framework\Schema\Query;

/**
 * The Media Utils
 */
class Media {

    private static bool      $loaded = false;
    private static ?Database $db     = null;

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
     * Updates the Paths in the Database
     * @param string $oldPath
     * @param string $newPath
     * @return boolean
     */
    public static function update(string $oldPath, string $newPath): bool {
        self::load();
        $result = 0;

        foreach (self::$data as $field) {
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
     * Creates a Directory
     * @param string $path
     * @param string $name
     * @return boolean
     */
    public static function create(string $path, string $name): bool {
        $source = Path::getPath("source", $path, $name);
        $thumbs = Path::getPath("thumbs", $path, $name);

        if (File::createDir($source) && File::createDir($thumbs)) {
            return true;
        }
        return false;
    }

    /**
     * Uploads a Media Element
     * @param Request $request
     * @param string  $path
     * @return boolean
     */
    public static function upload(Request $request, string $path): bool {
        $fileName = $request->getFileName("file");
        $tmpFile  = $request->getTmpName("file");
        $source   = Path::getPath("source", $path);
        $uploaded = File::upload($source, $fileName, $tmpFile);

        if (!File::exists($uploaded)) {
            return false;
        }
        if (!FileType::isImage($fileName)) {
            return true;
        }
        $src = Path::getPath("source", $path, $fileName);
        $dst = Path::getPath("thumbs", $path, $fileName);
        return Image::resize($src, $dst, 200, 200, Image::Resize);
    }

    /**
     * Deletes a Media Element
     * @param string $path
     * @param string $name
     * @return boolean
     */
    public static function delete(string $path, string $name): bool {
        $relPath = File::getPath($path, $name);
        $source  = Path::getPath("source", $path, $name);
        $thumbs  = Path::getPath("thumbs", $path, $name);

        if (!File::deleteDir($source) || !File::deleteDir($thumbs)) {
            return false;
        }

        self::update($relPath, "");
        return true;
    }



    /**
     * Renames a Media Element
     * @param string $path
     * @param string $oldName
     * @param string $newName
     * @return string
     */
    public static function rename(string $path, string $oldName, string $newName): string {
        return self::mv($path, $path, $oldName, $newName);
    }

    /**
     * Moves a Media Element
     * @param string $oldPath
     * @param string $newPath
     * @param string $name
     * @return string
     */
    public static function move(string $oldPath, string $newPath, string $name): string {
        return self::mv($oldPath, $newPath, $name, $name);
    }

    /**
     * Moves or Renames a Media Element
     * @param string $oldPath
     * @param string $newPath
     * @param string $oldName
     * @param string $newName
     * @return string
     */
    private static function mv(string $oldPath, string $newPath, string $oldName, string $newName): string {
        $oldRelPath = File::getPath($oldPath, $oldName);
        $newRelPath = File::getPath($newPath, $newName);
        $oldSource  = Path::getPath("source", $oldPath, $oldName);
        $newSource  = Path::getPath("source", $newPath, $newName);
        $oldThumbs  = Path::getPath("thumbs", $oldPath, $oldName);
        $newThumbs  = Path::getPath("thumbs", $newPath, $newName);

        if (!File::move($oldSource, $newSource)) {
            return null;
        }
        if (FileType::isImage($oldName) && !File::move($oldThumbs, $newThumbs)) {
            return null;
        }

        self::update($oldRelPath, $newRelPath);
        return $newRelPath;
    }
}
