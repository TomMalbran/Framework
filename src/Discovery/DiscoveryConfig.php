<?php
namespace Framework\Discovery;

use Framework\Application;
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

        // Dont load the Config inside the Framework
        if (Application::isFramework()) {
            self::$loaded = true;
            return false;
        }

        // Load all the Config files
        $appPath   = Application::getAppPath();
        $filePaths = File::getFilesInDir($appPath, recursive: true, skipVendor: true);
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
        $configPath = Application::getFramePath(Package::FrameConfigDir, $file . self::Extension);
        if (file_exists($configPath)) {
            include_once $configPath;
            return true;
        }
        return false;
    }
}
