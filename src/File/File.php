<?php
namespace Framework\File;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use ZipArchive;

/**
 * The File Utils
 */
class File {

    /**
     * Returns the path used to store the files
     * @param string ...$pathParts
     * @return string
     */
    public static function getPath(string ...$pathParts): string {
        $result = Strings::join($pathParts, "/");
        $result = Strings::replace($result, "//", "/");
        $result = self::removeLastSlash($result);
        return $result;
    }

    /**
     * Returns true if given file exists
     * @param string ...$pathParts
     * @return boolean
     */
    public static function exists(string ...$pathParts): bool {
        $fullPath = self::getPath(...$pathParts);
        return !empty($fullPath) && file_exists($fullPath);
    }



    /**
     * Uploads the given file to the given path
     * @param string $path
     * @param string $fileName
     * @param string $tmpFile
     * @return boolean
     */
    public static function upload(string $path, string $fileName, string $tmpFile): bool {
        $fullPath = self::getPath($path, $fileName);
        return self::uploadPath($fullPath, $tmpFile);
    }

    /**
     * Uploads the given file to the given path
     * @param string $path
     * @param string $tmpFile
     * @return boolean
     */
    public static function uploadPath(string $path, string $tmpFile): bool {
        if (!empty($path)) {
            return move_uploaded_file($tmpFile, $path);
        }
        return false;
    }

    /**
     * Creates a file with the given content
     * @param string          $path
     * @param string          $fileName
     * @param string[]|string $content
     * @return boolean
     */
    public static function create(string $path, string $fileName, array|string $content): bool {
        $fullPath = self::getPath($path, $fileName);
        if (!empty($fullPath)) {
            return self::write($fullPath, Strings::join($content, "\n"));
        }
        return false;
    }

    /**
     * Writes the given content in the given file
     * @param string $path
     * @param mixed  $content
     * @return boolean
     */
    public static function write(string $path, mixed $content): bool {
        $result = file_put_contents($path, $content);
        return $result != false;
    }

    /**
     * Reads the contents of a file
     * @param string ...$pathParts
     * @return string
     */
    public static function read(string ...$pathParts): string {
        $fullPath = self::getPath(...$pathParts);
        if (!empty($fullPath) && file_exists($fullPath)) {
            $result = file_get_contents($fullPath);
            return $result != false ? $result : "";
        }
        return "";
    }

    /**
     * Moves a file from one path to another
     * @param string $fromPath
     * @param string $toPath
     * @return boolean
     */
    public static function move(string $fromPath, string $toPath): bool {
        if (!empty($fromPath) && !empty($toPath)) {
            return rename($fromPath, $toPath);
        }
        return false;
    }

    /**
     * Deletes the given file/directory
     * @param string $path
     * @param string $name Optional.
     * @return boolean
     */
    public static function delete(string $path, string $name = ""): bool {
        $fullPath = self::getPath($path, $name);
        if (!empty($fullPath) && file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }



    /**
     * Adds the last slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function addLastSlash(string $path): string {
        if (!Strings::endsWith($path, "/")) {
            return "$path/";
        }
        return $path;
    }

    /**
     * Adds the first slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function addFirstSlash(string $path): string {
        if (!Strings::startsWith($path, "/")) {
            return "/$path";
        }
        return $path;
    }

    /**
     * Removes the last slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function removeLastSlash(string $path): string {
        return Strings::stripEnd($path, "/");
    }

    /**
     * Removes the first slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function removeFirstSlash(string $path): string {
        return Strings::stripStart($path, "/");
    }



    /**
     * Returns the file name component of the path
     * @param string $path
     * @return string
     */
    public static function getBaseName(string $path): string {
        return basename($path);
    }

    /**
     * Returns the name without the extension
     * @param string $name
     * @return string
     */
    public static function getName(string $name): string {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        return Strings::replace($name, ".$extension", "");
    }

    /**
     * Returns the extension of the given name
     * @param string $name
     * @return string
     */
    public static function getExtension(string $name): string {
        return pathinfo($name, PATHINFO_EXTENSION);
    }

    /**
     * Returns true if the file has the given extension
     * @param string          $name
     * @param string[]|string $extensions
     * @return boolean
     */
    public static function hasExtension(string $name, array|string $extensions): bool {
        $extension = self::getExtension($name);
        $extension = Strings::toLowerCase($extension);
        return Arrays::contains($extensions, $extension);
    }

    /**
     * Returns the new name of the given file using the old extension
     * @param string $newName
     * @param string $oldName
     * @return string
     */
    public static function parseName(string $newName, string $oldName): string {
        $newExt = self::getExtension($newName);
        $oldExt = self::getExtension($oldName);
        if (empty($newExt) && !empty($oldExt)) {
            return "{$newName}.{$oldExt}";
        }
        return $newName;
    }



    /**
     * Returns all the Files and Directories inside the given path
     * @param string $path
     * @return string[]
     */
    public static function getAllInDir(string $path): array {
        $result = [];
        if (!file_exists($path) || !is_dir($path)) {
            return $result;
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $result[] = self::getPath($path, $file);
            }
        }
        return $result;
    }

    /**
     * Returns all the Files inside the given path
     * @param string $path
     * @return string[]
     */
    public static function getFilesInDir(string $path): array {
        $result = [];
        if (!empty($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file != "." && $file != ".." && !is_dir("$path/$file")) {
                    $result[] = $file;
                }
            }
        }
        return $result;
    }

    /**
     * Returns the first File inside the given path
     * @param string $path
     * @return string
     */
    public static function getFirstFileInDir(string $path): string {
        $files = self::getFilesInDir($path);
        return !empty($files[0]) ? $path . $files[0] : "";
    }

    /**
     * Creates a directory at the given path if it doesn't exists
     * @param string $path
     * @return boolean
     */
    public static function createDir(string $path): bool {
        if (!self::exists($path)) {
            mkdir($path, 0777, true);
            return true;
        }
        return false;
    }

    /**
     * Deletes a directory and it's content
     * @param string $path
     * @return boolean
     */
    public static function deleteDir(string $path): bool {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    self::deleteDir("$path/$file");
                }
            }
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
        return !file_exists($path);
    }

    /**
     * Deletes all the content from a directory
     * @param string $path
     * @return boolean
     */
    public static function emptyDir(string $path): bool {
        if (!file_exists($path)) {
            return false;
        }
        $result = true;
        $files  = scandir($path);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (!self::deleteDir("$path/$file")) {
                    $result = false;
                }
            }
        }
        return $result;
    }



    /**
     * Creates a new zip archive and adds the given files/directories
     * @param string          $name
     * @param string[]|string $files
     * @return ZipArchive|null
     */
    public static function createZip(string $name, array|string $files): ?ZipArchive {
        $zip   = new ZipArchive();
        $files = Arrays::toArray($files);

        if ($zip->open($name, ZIPARCHIVE::CREATE)) {
            foreach ($files as $file) {
                self::addDirToZip($zip, $file, pathinfo($file, PATHINFO_BASENAME));
            }
            $zip->close();
            return $zip;
        }
        return null;
    }

    /**
     * Adds a directory and all the files/directories inside or just a single file
     * @param ZipArchive $zip
     * @param string     $src
     * @param string     $dst
     * @return boolean
     */
    private static function addDirToZip(ZipArchive $zip, string $src, string $dst): bool {
        if (!file_exists($src)) {
            return false;
        }

        if (is_dir($src)) {
            $result = true;
            $zip->addEmptyDir($dst);
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    if (!self::addDirToZip($zip, "$src/$file", "$dst/$file")) {
                        $result = false;
                    }
                }
            }
            return $result;
        }

        $zip->addFile($src, $dst);
        return true;
    }

    /**
     * Extracts the given zip to the given path
     * @param string $zipPath
     * @param string $extractPath
     * @return boolean
     */
    public static function extractZip(string $zipPath, string $extractPath): bool {
        $zip = new ZipArchive();
        if (!$zip->open($zipPath)) {
            return false;
        }
        $zip->extractTo($extractPath);
        $zip->close();
        return true;
    }
}
