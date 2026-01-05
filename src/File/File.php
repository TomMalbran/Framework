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
     * Returns the directory of the given path
     * @param string  $path
     * @param integer $levels Optional.
     * @return string
     */
    public static function getDirectory(string $path, int $levels = 1): string {
        $directory = dirname($path, max(1, $levels));
        return Strings::replace($directory, "\\", "/");
    }

    /**
     * Returns the path used to store the files
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function parsePath(string|int ...$pathParts): string {
        $result = Strings::join($pathParts, "/");

        // Windows path
        $prefix = "";
        if (Strings::match($result, "/^[a-zA-Z]:\/\//")) {
            $prefix = Strings::substring($result, 0, 3);
            $result = Strings::substring($result, 3);
        }

        // Remove double slashes and last one
        while (Strings::contains($result, "//")) {
            $result = Strings::replace($result, "//", "/");
        }
        $result = self::removeLastSlash($result);

        // Add the prefix if it exists
        if ($prefix !== "") {
            $result = "{$prefix}{$result}";
        }
        return $result;
    }

    /**
     * Returns the path used to store the files
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function parseUrl(string|int ...$pathParts): string {
        $protocols = [ "http://", "https://" ];
        foreach ($protocols as $protocol) {
            if (isset($pathParts[0]) && is_string($pathParts[0]) && Strings::startsWith($pathParts[0], $protocol)) {
                $pathParts[0] = Strings::substringAfter($pathParts[0], $protocol);
                return $protocol . self::parsePath(...$pathParts);
            }
        }
        return self::parsePath(...$pathParts);
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
     * Returns true if given file exists
     * @param string|integer ...$pathParts
     * @return boolean
     */
    public static function exists(string|int ...$pathParts): bool {
        $fullPath = self::parsePath(...$pathParts);
        return $fullPath !== "" && file_exists($fullPath);
    }

    /**
     * Returns the modified time of a file
     * @param string|integer ...$pathParts
     * @return integer
     */
    public static function getModifiedTime(string|int ...$pathParts): int {
        $fullPath = self::parsePath(...$pathParts);
        if ($fullPath === "" || !file_exists($fullPath)) {
            return 0;
        }
        $time = filemtime($fullPath);
        return $time !== false ? $time : 0;
    }



    /**
     * Reads the contents of a file
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function read(string|int ...$pathParts): string {
        $fullPath = self::parsePath(...$pathParts);
        if ($fullPath === "" || !file_exists($fullPath)) {
            return "";
        }
        $result = file_get_contents($fullPath);
        return $result !== false ? $result : "";
    }

    /**
     * Reads the contents of a url
     * @param string $fileUrl
     * @return string
     */
    public static function readUrl(string $fileUrl): string {
        if ($fileUrl === "") {
            return "";
        }

        $fileUrl = Strings::encodeUrl($fileUrl);
        $result  = file_get_contents($fileUrl);
        return $result !== false ? $result : "";
    }

    /**
     * Uploads the given file to the given path
     * @param string $path
     * @param string $fileName
     * @param string $tmpFile
     * @return boolean
     */
    public static function upload(string $path, string $fileName, string $tmpFile): bool {
        $fullPath = self::parsePath($path, $fileName);
        return self::uploadPath($fullPath, $tmpFile);
    }

    /**
     * Uploads the given file to the given path
     * @param string $path
     * @param string $tmpFile
     * @return boolean
     */
    public static function uploadPath(string $path, string $tmpFile): bool {
        if ($path !== "" && $tmpFile !== "") {
            return move_uploaded_file($tmpFile, $path);
        }
        return false;
    }

    /**
     * Creates a file with the given content
     * @param string          $path
     * @param string          $fileName
     * @param string[]|string $content
     * @param boolean         $createDir Optional.
     * @return boolean
     */
    public static function create(string $path, string $fileName, array|string $content, bool $createDir = false): bool {
        $fullPath = self::parsePath($path, $fileName);
        if ($fullPath === "") {
            return false;
        }

        if ($createDir) {
            self::createDir($path);
        }
        return self::write($fullPath, Strings::join($content, "\n"));
    }

    /**
     * Writes the given content in the given file
     * @param string $path
     * @param mixed  $content
     * @return boolean
     */
    public static function write(string $path, mixed $content): bool {
        $result = file_put_contents($path, $content);
        return $result !== false;
    }

    /**
     * Writes the content from the given Url in the given file
     * @param string $path
     * @param string $fileUrl
     * @return boolean
     */
    public static function writeFromUrl(string $path, string $fileUrl): bool {
        $content = self::readUrl($fileUrl);
        if ($content === "") {
            return false;
        }
        return self::write($path, $content);
    }

    /**
     * Moves a file from one path to another
     * @param string $fromPath
     * @param string $toPath
     * @return boolean
     */
    public static function move(string $fromPath, string $toPath): bool {
        if ($fromPath === "" || $toPath === "") {
            return false;
        }
        return rename($fromPath, $toPath);
    }

    /**
     * Copies a file from one path to another
     * @param string $fromPath
     * @param string $toPath
     * @return boolean
     */
    public static function copy(string $fromPath, string $toPath): bool {
        if ($fromPath === "" || $toPath === "") {
            return false;
        }
        return copy($fromPath, $toPath);
    }

    /**
     * Deletes the given file/directory
     * @param string $path
     * @param string $name Optional.
     * @return boolean
     */
    public static function delete(string $path, string $name = ""): bool {
        $fullPath = self::parsePath($path, $name);
        if ($fullPath === "" || !file_exists($fullPath)) {
            return false;
        }
        return unlink($fullPath);
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
        if (Strings::contains($name, "?")) {
            $name = Strings::substringBefore($name, "?");
        }
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

        if ($newExt === "" && $oldExt !== "") {
            return "{$newName}.{$oldExt}";
        }
        return $newName;
    }



    /**
     * Returns the files inside the given path
     * @param string $path
     * @return string[]
     */
    private static function scanPath(string $path): array {
        if (!file_exists($path) || !is_dir($path)) {
            return [];
        }
        $files = scandir($path);
        return $files !== false ? $files : [];
    }

    /**
     * Returns all the Files and Directories inside the given path
     * @param string $path
     * @return string[]
     */
    public static function getAllInDir(string $path): array {
        $files  = self::scanPath($path);
        $result = [];
        foreach ($files as $file) {
            if ($file !== "." && $file !== "..") {
                $result[] = self::parsePath($path, $file);
            }
        }
        return $result;
    }

    /**
     * Returns all the Directories inside the given path
     * @param string $path
     * @return string[]
     */
    public static function getDirectoriesInDir(string $path): array {
        $result = self::getAllInDir($path);
        $result = array_filter($result, fn($file) => is_dir($file));
        return $result;
    }

    /**
     * Returns all the Files inside the given path
     * @param string  $path
     * @param boolean $recursive Optional.
     * @return string[]
     */
    public static function getFilesInDir(string $path, bool $recursive = false): array {
        $result = [];
        if ($path === "") {
            return $result;
        }
        if (is_dir($path)) {
            $files = self::scanPath($path);
            foreach ($files as $file) {
                if ($file === "." || $file === "..") {
                    continue;
                }
                if ($recursive) {
                    $response = self::getFilesInDir("$path/$file", true);
                    $result   = array_merge($result, $response);
                } else {
                    $result[] = $file;
                }
            }
        } else {
            $result[] = $path;
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
        if (isset($files[0]) && $files[0] !== "") {
            return "$path{$files[0]}";
        }
        return "";
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
     * Ensures that all the directories are created
     * @param string         $basePath
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function ensureDir(string $basePath, string|int ...$pathParts): string {
        $path        = trim(self::parsePath(...$pathParts));
        $pathParts   = Strings::split($path, "/");
        $totalParts  = count($pathParts);
        $partialPath = [];

        if (self::getExtension($path) !== "") {
            $totalParts -= 1;
        }
        for ($i = 0; $i < $totalParts; $i++) {
            $partialPath[] = $pathParts[$i];
            $fullPath      = self::parsePath($basePath, ...$partialPath);
            self::createDir($fullPath);
        }
        return self::parsePath($basePath, ...$pathParts);
    }

    /**
     * Deletes a directory and it's content
     * @param string  $path
     * @param integer $deleted Optional.
     * @return boolean
     */
    public static function deleteDir(string $path, int &$deleted = 0): bool {
        if (is_dir($path)) {
            $files = self::scanPath($path);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    self::deleteDir("$path/$file", $deleted);
                }
            }
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
            $deleted += 1;
        }
        return !file_exists($path);
    }

    /**
     * Deletes all the content from a directory
     * @param string  $path
     * @param integer $deleted Optional.
     * @return boolean
     */
    public static function emptyDir(string $path, int &$deleted = 0): bool {
        $files  = self::scanPath($path);
        $result = true;
        foreach ($files as $file) {
            if ($file !== "." && $file !== "..") {
                if (!self::deleteDir("$path/$file", $deleted)) {
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
        $files = Arrays::toStrings($files);

        if ($zip->open($name, ZIPARCHIVE::CREATE) === true) {
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
            $files = self::scanPath($src);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
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
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        $zip->extractTo($extractPath);
        $zip->close();
        return true;
    }
}
