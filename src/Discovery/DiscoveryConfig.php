<?php
namespace Framework\Discovery;

use Framework\File\File;
use Framework\Utils\Strings;

/**
 * The Discovery Config
 */
class DiscoveryConfig {

    public const Extension = ".config.php";

    private static bool $loaded = false;



    /**
     * Finds and loads all Config files
     * NOTE 1: A config file must end with ".config.php" to be loaded
     * NOTE 2: Only files from the App are loaded
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        // Determine the Base Path
        $appPath   = Discovery::getAppPath();
        $framePath = Discovery::getFramePath();

        // Dont load the Config inside the Framework
        if ($appPath === $framePath) {
            self::$loaded = true;
            return false;
        }

        // Read all the files skipping the main vendor directory
        $basePaths = File::getFilesInDir($appPath, false);
        $filePaths = [];
        foreach ($basePaths as $basePath) {
            $fullPath = "$appPath/$basePath";
            if (is_dir($fullPath) && $basePath !== "vendor") {
                $subFiles  = File::getFilesInDir($fullPath, true);
                $filePaths = array_merge($filePaths, $subFiles);
            } else {
                $filePaths[] = $fullPath;
            }
        }

        // Load all the Config files
        foreach ($filePaths as $filePath) {
            if (Strings::endsWith($filePath, self::Extension)) {
                include_once $filePath;
            }

        }

        self::$loaded = true;
        return true;
    }

    /**
     * Loads a Default Config file from the Framework
     * @param string $file
     * @return boolean
     */
    public static function loadDefault(string $file): bool {
        $configPath = Discovery::getFramePath(Package::FrameConfigDir, $file . self::Extension);
        if (file_exists($configPath)) {
            include_once $configPath;
            return true;
        }
        return false;
    }
}
