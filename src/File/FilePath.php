<?php
namespace Framework\File;

use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\Discovery\Package;
use Framework\File\File;
use Framework\System\Config;
use Framework\Utils\Arrays;
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


    private static bool  $loaded      = false;

    /** @var string[] */
    private static array $paths       = [];

    /** @var string[] */
    private static array $directories = [];


    /**
     * Loads the Path Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        /** @var array{paths:string[],directories:string[]} */
        $data = Discovery::loadData(DataFile::Files);
        if (Arrays::isEmpty($data, "paths")) {
            self::$loaded = true;
            return false;
        }

        self::$loaded      = true;
        self::$paths       = $data["paths"];
        self::$directories = $data["directories"];
        return true;
    }



    /**
     * Returns the Files Path with the given path parts
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getPath(string|int ...$pathParts): string {
        $basePath = self::getBasePath();
        return File::parsePath($basePath, Package::FilesDir, ...$pathParts);
    }

    /**
     * Returns the Internal Files Path with the given path parts
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getInternalPath(string|int ...$pathParts): string {
        $basePath = self::getBasePath(forBackend: true);
        return File::parsePath($basePath, Package::DataFilesDir, ...$pathParts);
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
            return File::getDirectory($result);
        }
        return $result;
    }

    /**
     * Returns the Private Path
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getPrivatePath(string|int ...$pathParts): string {
        $basePath = self::getBasePath(forPrivate: true);
        return File::parsePath($basePath, ...$pathParts);
    }

    /**
     * Returns the FTP Path
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getFTPPath(string|int ...$pathParts): string {
        $basePath = self::getBasePath(forPrivate: true);
        return File::parsePath($basePath, Package::FTPDir, ...$pathParts);
    }



    /**
     * Returns the directory used to store the files
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getDir(string|int ...$pathParts): string {
        return File::parsePath(Package::FilesDir, ...$pathParts);
    }

    /**
     * Returns the directory used for the internal files
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getInternalDir(string|int ...$pathParts): string {
        return File::parsePath(Package::getAppBaseDir(), Package::FilesDir, ...$pathParts);
    }



    /**
     * Returns the url for the given path
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getUrl(string|int ...$pathParts): string {
        return Config::getFileUrl(Package::FilesDir, ...$pathParts);
    }

    /**
     * Returns the url for the given internal path
     * @param string|integer ...$pathParts
     * @return string
     */
    public static function getInternalUrl(string|int ...$pathParts): string {
        return Config::getUrl(Package::getAppBaseDir(), Package::DataFilesDir, ...$pathParts);
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
     * @param integer         $credentialID
     * @param string|integer  ...$pathParts
     * @return string
     */
    public static function getTempUrl(int $credentialID, string|int ...$pathParts): string {
        return Config::getFileUrl(self::Temp, $credentialID, ...$pathParts);
    }



    /**
     * Returns the Code variables
     * @return array<string,mixed>
     */
    public static function getCode(): array {
        self::load();
        $basePaths = [ self::Source, self::Thumbs, self::Avatars ];
        $paths     = [];

        if (count(self::$paths) > 0) {
            $basePaths = array_merge($basePaths, self::$paths);
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

            foreach (self::$directories as $directory) {
                $path = self::getPath($basePath, $id, $directory);
                if (File::createDir($path)) {
                    $result[] = "$basePath/$id/$directory";
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

        if (count(self::$paths) > 0) {
            $basePaths = array_merge($basePaths, self::$paths);
        }
        foreach ($basePaths as $basePath) {
            $path = self::getPath($basePath);
            if (File::createDir($path)) {
                $paths[] = $basePath;
            }
        }

        $directories = self::createDirs();

        if (count($paths) > 0) {
            print("- Added " . count($paths) . " paths\n");
            print(Strings::join($paths, ", ") . "\n");
        }
        if (count($directories) > 0) {
            print("- Added " . count($directories) . " directories\n");
            print(Strings::join($directories, ", ") . "\n");
        }
        if (count($paths) === 0 && count($directories) === 0) {
            print("- No paths added\n");
        }
        return true;
    }
}
