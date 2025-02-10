<?php
namespace Framework\File;

use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\File\File;
use Framework\System\Package;
use Framework\System\Config;
use Framework\Utils\Server;
use Framework\Utils\Strings;

/**
 * The File Paths
 */
class FilePath {

    private const Temp    = "temp";
    private const Source  = "source";
    private const Thumbs  = "thumbs";
    private const Avatars = "avatars";


    private static bool  $loaded = false;

    /** @var array{}[] */
    private static array $data   = [];


    /**
     * Loads the Path Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$data   = Discovery::loadData(DataFile::Files);
        return true;
    }



    /**
     * Returns the Files Path with the given path parts
     * @param string ...$pathParts
     * @return string
     */
    public static function getPath(string ...$pathParts): string {
        $basePath = self::getBasePath();
        return File::parsePath($basePath, Package::FilesDir, ...$pathParts);
    }

    /**
     * Returns the Internal Files Path with the given path parts
     * @param string ...$pathParts
     * @return string
     */
    public static function getInternalPath(string ...$pathParts): string {
        $basePath = self::getBasePath(forBackend: true);
        return File::parsePath($basePath, Package::FilesDir, ...$pathParts);
    }

    /**
     * Returns the base path used to store the files
     * @param boolean $forFramework Optional.
     * @param boolean $forBackend   Optional.
     * @param boolean $forPrivate   Optional.
     * @return string
     */
    public static function getBasePath(bool $forFramework = false, bool $forBackend = false, bool $forPrivate = false): string {
        $result = Discovery::getBasePath($forFramework, $forBackend);
        if ($forPrivate && !Server::isLocalHost()) {
            return dirname($result);
        }
        return $result;
    }

    /**
     * Returns the Private Path
     * @param string ...$pathParts
     * @return string
     */
    public static function getPrivatePath(string ...$pathParts): string {
        $basePath = self::getBasePath(forPrivate: true);
        return File::parsePath($basePath, ...$pathParts);
    }

    /**
     * Returns the FTP Path
     * @param string ...$pathParts
     * @return string
     */
    public static function getFTPPath(string ...$pathParts): string {
        $basePath = self::getBasePath(forPrivate: true);
        return File::parsePath($basePath, Package::FTPDir, ...$pathParts);
    }



    /**
     * Returns the directory used to store the files
     * @param string ...$pathParts
     * @return string
     */
    public static function getDir(string ...$pathParts): string {
        return File::parsePath(Package::FilesDir, ...$pathParts);
    }

    /**
     * Returns the directory used for the internal files
     * @param string ...$pathParts
     * @return string
     */
    public static function getInternalDir(string ...$pathParts): string {
        return File::parsePath(Package::AppDir, Package::FilesDir, ...$pathParts);
    }



    /**
     * Returns the url for the given path
     * @param string ...$pathParts
     * @return string
     */
    public static function getUrl(string ...$pathParts): string {
        return Config::getFileUrl(Package::FilesDir, ...$pathParts);
    }

    /**
     * Returns the url for the given internal path
     * @param string ...$pathParts
     * @return string
     */
    public static function getInternalUrl(string ...$pathParts): string {
        return Config::getUrl(Package::AppDir, Package::FilesDir, ...$pathParts);
    }



    /**
     * Returns the path used to store the temp files
     * @param integer $credentialID
     * @param boolean $create       Optional.
     * @return string
     */
    public static function getTempPath(int $credentialID, bool $create = true): string {
        $path   = self::getPath(self::Temp, $credentialID);
        $exists = File::exists($path);

        if (!$exists && $create) {
            File::createDir($path);
            return $path;
        }
        return $exists ? $path : "";
    }

    /**
     * Creates an url to the files temp directory
     * @param integer $credentialID
     * @param string  ...$pathParts
     * @return string
     */
    public static function getTempUrl(int $credentialID, string ...$pathParts): string {
        return Config::getFileUrl(self::Temp, $credentialID, ...$pathParts);
    }



    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        self::load();
        $basePaths = [ self::Source, self::Thumbs, self::Avatars ];
        $paths     = [];

        if (!empty(self::$data["paths"])) {
            $basePaths = array_merge($basePaths, self::$data["paths"]);
        }
        foreach ($basePaths as $basePath) {
            $paths[] = [
                "name"  => $basePath,
                "title" => Strings::upperCaseFirst($basePath),
            ];
        }

        return [
            "paths" => $paths,
        ];
    }

    /**
     * Creates the Directories for the given ID
     * @param integer $id Optional.
     * @return string[]
     */
    public static function createDirs(int $id = 0): array {
        self::load();
        $basePaths = [ self::Source, self::Thumbs ];
        $result    = [];

        foreach ($basePaths as $basePath) {
            $path = self::getPath($basePath);
            if (File::createDir($path)) {
                $result[] = $basePath;
            }

            $path = self::getPath($basePath, $id);
            if (File::createDir($path)) {
                $result[] = "$basePath/$id";
            }

            if (!empty(self::$data["directories"])) {
                foreach (self::$data["directories"] as $directory) {
                    $path = self::getPath($basePath, $id, $directory);
                    if (File::createDir($path)) {
                        $result[] = "$basePath/$id/$directory";
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
        self::load();
        $basePaths = [ self::Temp, self::Source, self::Thumbs, self::Avatars ];
        $paths     = [];

        if (!empty(self::$data["paths"])) {
            $basePaths = array_merge($basePaths, self::$data["paths"]);
        }
        foreach ($basePaths as $basePath) {
            $path = self::getPath($basePath);
            if (File::createDir($path)) {
                $paths[] = $basePath;
            }
        }

        $directories = self::createDirs();

        if (!empty($paths)) {
            print("<br>Added <i>" . count($paths) . " paths</i><br>");
            print(Strings::join($paths, ", ") . "<br>");
        }
        if (!empty($directories)) {
            print("<br>Added <i>" . count($directories) . " directories</i><br>");
            print(Strings::join($directories, ", ") . "<br>");
        }
        if (empty($paths) && empty($directories)) {
            print("<br>No <i>paths</i> added<br>");
        }
        return true;
    }
}
