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
     * NOTE 2: A config in the App will override a Frame config when configuring the same class
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        $configFiles = [];

        $framePath = Discovery::getFramePath();
        self::loadConfigs($framePath, $configFiles);

        $appPath = Discovery::getAppPath();
        self::loadConfigs($appPath, $configFiles);

        foreach ($configFiles as $configFile) {
            include_once $configFile;
        }

        self::$loaded = true;
        return true;
    }

    /**
     * Finds and loads all Config files from a Path
     * @param string               $path
     * @param array<string,string> $configFiles
     * @return boolean
     */
    private static function loadConfigs(string $path, array &$configFiles): bool {
        // Read all the files skipping the main vendor directory
        $basePaths = File::getFilesInDir($path, false);
        $filePaths = [];
        foreach ($basePaths as $basePath) {
            $fullPath = "$path/$basePath";
            if (is_dir($fullPath) && $basePath !== "vendor") {
                $subFiles  = File::getFilesInDir($fullPath, true);
                $filePaths = array_merge($filePaths, $subFiles);
            } else {
                $filePaths[] = $fullPath;
            }
        }

        // Find all Config files
        foreach ($filePaths as $filePath) {
            // Only consider Config files
            if (!Strings::endsWith($filePath, self::Extension)) {
                continue;
            }

            // Try to determine a File Name from the first "use" statement
            $file     = File::read($filePath);
            $lines    = Strings::split($file, "\n", trim: true, skipEmpty: true);
            $fileName = "";

            foreach ($lines as $line) {
                if (Strings::startsWith($line, "use ")) {
                    $fileName = Strings::substringBetween($line, "use ", ";");
                    break;
                }
            }
            if ($fileName === "") {
                continue;
            }

            // Save the Config File. It might replace an existing one
            $configFiles[$fileName] = $filePath;
        }
        return true;
    }
}
