<?php
namespace Framework\File;

use Framework\Utils\Strings;
use Framework\Utils\Utils;
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
    public static function getPath(...$pathParts) {
        $result = Strings::join($pathParts, "/");
        $result = Strings::replace($result, "//", "/");
        return $result;
    }
    
    /**
     * Returns true if given file exists
     * @param string ...$pathParts
     * @return boolean
     */
    public static function exists(...$pathParts) {
        $path = self::getPath(...$pathParts);
        return !empty($path) && file_exists($path);
    }

    /**
     * Uplods the given file to the given path
     * @param string $path
     * @param string $fileName
     * @param string $tmpFile
     * @return string
     */
    public static function upload($path, $fileName, $tmpFile) {
        $path = self::getPath($path, $fileName);
        if (!empty($path)) {
            move_uploaded_file($tmpFile, $path);
        }
        return $path;
    }
    
    /**
     * Creates a file with the given content
     * @param string          $path
     * @param string          $fileName
     * @param string|string[] $content
     * @return string
     */
    public static function create($path, $fileName, $content) {
        $path = self::getPath($path, $fileName);
        if (!empty($path)) {
            file_put_contents($path, Strings::join($content, "\n"));
        }
        return $path;
    }
    
    /**
     * Moves a file from one path to another
     * @param string $fromPath
     * @param string $toPath
     * @return boolean
     */
    public static function move($fromPath, $toPath) {
        if (!empty($fromPath) && !empty($toPath)) {
            rename($fromPath, $toPath);
            return true;
        }
        return false;
    }
    
    /**
     * Deletes the given file/directory
     * @param string $path
     * @param string $name Optional.
     * @return boolean
     */
    public static function delete($path, $name = "") {
        $path = self::getPath($path, $name);
        if (!empty($path) && file_exists($path)) {
            unlink($path);
            return true;
        }
        return false;
    }



    /**
     * Adds the last slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function addLastSlash($path) {
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
    public static function addFirstSlash($path) {
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
    public static function removeLastSlash($path) {
        return Strings::stripEnd($path, "/");
    }

    /**
     * Removes the first slash for dir processing functions
     * @param string $path
     * @return string
     */
    public static function removeFirstSlash($path) {
        return Strings::stripStart($path, "/");
    }
    
    
    
    /**
     * Returns the file name component of the path
     * @param string $path
     * @return string
     */
    public static function getBaseName($path) {
        return basename($path);
    }

    /**
     * Returns the name without the extension
     * @param string $name
     * @return string
     */
    public static function getName($name) {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        return Strings::replace($name, ".$extension", "");
    }

    /**
     * Returns the extension of the given name
     * @param string $name
     * @return string
     */
    public static function getExtension($name) {
        return pathinfo($name, PATHINFO_EXTENSION);
    }

    /**
     * Returns true if the file has the given extension
     * @param string          $name
     * @param string|string[] $extensions
     * @return boolean
     */
    public static function hasExtension($name, $extensions) {
        $extension = self::getExtension($name);
        return in_array($extension, Utils::toArray($extensions));
    }

    /**
     * Returns the new name of the given file using the old extension
     * @param string $newName
     * @param string $oldName
     * @return string
     */
    public static function parseName($newName, $oldName) {
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
    public static function getAllInDir($path) {
        $result = [];
        if (!file_exists($path) || !is_dir($path)) {
            return $result;
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $result[] = self::getpath($path, $file);
            }
        }
        return $result;
    }
    
    /**
     * Returns all the Files inside the given path
     * @param string $path
     * @return string[]
     */
    public static function getFilesInDir($path) {
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
     * Creates a directory at the given path if it doesn't exists
     * @param string $path
     * @return string
     */
    public static function createDir($path) {
        if (!self::exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * Deletes a directory and it's content
     * @param string $path
     * @return boolean
     */
    public static function deleteDir($path) {
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
     * @return void
     */
    public static function emptyDir($path) {
        if (file_exists($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    self::deleteDir("$path/$file");
                }
            }
        }
    }
    
    
    
    /**
     * Creates a new zip archive and adds the given files/directories
     * @param string          $name
     * @param string|string[] $files
     * @return ZipArchive
     */
    public static function createZip($name, $files) {
        $zip   = new ZipArchive();
        $files = Utils::toArray($files);

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
     * @return void
     */
    private static function addDirToZip(ZipArchive $zip, $src, $dst) {
        if (is_dir($src)) {
            $zip->addEmptyDir($dst);
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    self::addDirToZip($zip, "$src/$file", "$dst/$file");
                }
            }
        } elseif (file_exists($src)) {
            $zip->addFile($src, $dst);
        }
    }
    
    /**
     * Extracts the given zip to the given path
     * @param string $zipPath
     * @param string $extractPath
     * @return void
     */
    public function extractZip($zipPath, $extractPath) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath)) {
            $zip->extractTo($extractPath);
            $zip->close();
        }
    }
}
