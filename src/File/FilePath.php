<?php
namespace Framework\File;

use Framework\Application;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\ConsoleCommand;
use Framework\Discovery\Package;
use Framework\Builder\Builder;
use Framework\File\File;
use Framework\System\Config;
use Framework\Utils\Server;
use Framework\Utils\Strings;

/**
 * The File Paths
 */
class FilePath implements DiscoveryBuilder {

    private const Temp    = "temp";
    private const Source  = "source";
    private const Thumbs  = "thumbs";
    private const Avatars = "avatars";


    /** @var string[] */
    private static array $paths       = [];

    /** @var string[] */
    private static array $directories = [];


    /**
     * Registers a Path
     * @param string $name
     * @return bool
     */
    public static function register(string $name): bool {
        self::$paths[] = $name;
        return true;
    }

    /**
     * Registers a Directory
     * @param string $name
     * @return bool
     */
    public static function registerDirectory(string $name): bool {
        if ($name === "" || $name === "example") {
            return false;
        }

        self::$directories[] = $name;
        return true;
    }



    /**
     * Returns the Files Path with the given path parts
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getPath(string|int ...$pathParts): string {
        $basePath = self::getBasePath();
        return File::parsePath($basePath, Config::getFileDir(), ...$pathParts);
    }

    /**
     * Returns the base path used to store the files
     * @param bool $forFramework Optional.
     * @param bool $forBackend   Optional.
     * @param bool $forPrivate   Optional.
     * @return string
     */
    public static function getBasePath(
        bool $forFramework = false,
        bool $forBackend = false,
        bool $forPrivate = false,
    ): string {
        if ($forFramework) {
            $result = Package::getBasePath();
        } elseif ($forBackend) {
            $result = Application::getBasePath();
        } else {
            $result = Application::getIndexPath();
        }

        if ($forPrivate && !Server::isLocalHost()) {
            return File::getDirectory($result);
        }
        return $result;
    }

    /**
     * Returns the Private Path
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getPrivatePath(string|int ...$pathParts): string {
        $basePath = self::getBasePath(forPrivate: true);
        return File::parsePath($basePath, ...$pathParts);
    }

    /**
     * Returns the FTP Path
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getFTPPath(string|int ...$pathParts): string {
        $basePath = self::getBasePath(forPrivate: true);
        return File::parsePath($basePath, Config::getFileFtp(), ...$pathParts);
    }



    /**
     * Returns the directory used to store the files
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getDir(string|int ...$pathParts): string {
        return File::parsePath(Config::getFileDir(), ...$pathParts);
    }

    /**
     * Returns the directory used for the internal files
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getInternalDir(string|int ...$pathParts): string {
        return File::parsePath(Application::getBaseDir(), Config::getFileDir(), ...$pathParts);
    }

    /**
     * Returns the url for the given path
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getUrl(string|int ...$pathParts): string {
        return Config::getFileUrl(Config::getFileDir(), ...$pathParts);
    }



    /**
     * Returns the path used to store the temp files
     * @param int  $credentialID
     * @param bool $create       Optional.
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
     * @param int        $credentialID
     * @param string|int ...$pathParts
     * @return string
     */
    public static function getTempUrl(int $credentialID, string|int ...$pathParts): string {
        return Config::getFileUrl(self::Temp, $credentialID, ...$pathParts);
    }



    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
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

        // Builds the code
        return Builder::generateCode("Path", [
            "paths" => $paths,
            "total" => count(self::$paths),
        ]);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }

    /**
     * Ensures that the Paths are created
     * @return bool
     */
    #[ConsoleCommand("ensurePaths")]
    public static function ensurePaths(): bool {
        print("\nENSURE PATHS\n");

        DiscoveryConfig::load();
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

    /**
     * Creates the Directories for the given ID
     * @param int $id Optional.
     * @return string[]
     */
    public static function createDirs(int $id = 0): array {
        DiscoveryConfig::load();
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
}
