<?php
namespace Framework\Discovery;

use Framework\File\File;
use Framework\System\Package;
use Framework\Utils\Strings;
use Framework\Utils\JSON;

/**
 * The Discovery
 */
class Discovery {

    private const DataDir       = "data";
    private const TemplateDir   = "data/templates";
    private const MigrationsDir = "migrations";
    private const EmailFile     = "email.html";



    /**
     * Returns the BasePath
     * @param boolean $forFramework Optional.
     * @param boolean $forBackend   Optional.
     * @return string
     */
    public static function getBasePath(bool $forFramework = false, bool $forBackend = false): string {
        if ($forFramework) {
            return self::getFramePath();
        }
        if ($forBackend) {
            return self::getIndexPath(Package::AppDir);
        }
        return self::getIndexPath();
    }

    /**
     * Returns the path to the Framework
     * @param string ...$pathParts
     * @return string
     */
    public static function getFramePath(string ...$pathParts): string {
        $path = dirname(__FILE__, 3);
        return File::parsePath($path, ...$pathParts);
    }

    /**
     * Returns the path to the App
     * @param string ...$pathParts
     * @return string
     */
    public static function getIndexPath(string ...$pathParts): string {
        $path = dirname(__FILE__, 3);
        if (Strings::contains($path, "vendor")) {
            $path = Strings::substringBefore($path, "/vendor");
            $path = Strings::substringBefore($path, "/", false);
        }
        return File::parsePath($path, ...$pathParts);
    }

    /**
     * Returns the path to the App
     * @param string ...$pathParts
     * @return string
     */
    public static function getAppPath(string ...$pathParts): string {
        return self::getIndexPath(Package::AppDir, ...$pathParts);
    }

    /**
     * Returns the path to the Migrations
     * @return string
     */
    public static function getMigrationsPath(): string {
        return self::getAppPath(Package::DataDir, self::MigrationsDir);
    }



    /**
     * Returns the Environment
     * @return string
     */
    public static function getEnvironment(): string {
        $basePath = self::getBasePath(false);
        if (Strings::contains($basePath, "public_html")) {
            $environment = Strings::substringAfter($basePath, "domains/");
            $environment = Strings::substringBefore($environment, "/public_html");
            return $environment;
        }
        return "localhost";
    }

    /**
     * Finds the Classes in the given Directory
     * @param string  $dir         Optional.
     * @param boolean $skipIgnored Optional.
     * @return array<string,string>
     */
    public static function findClasses(string $dir = "", bool $skipIgnored = false): array {
        $sourcePath = self::getAppPath(Package::SourceDir);
        $basePath   = self::getAppPath(Package::SourceDir, $dir);
        $files      = File::getFilesInDir($basePath, true);
        $result     = [];

        foreach ($files as $file) {
            if (!Strings::endsWith($file, ".php")) {
                continue;
            }

            // Skip some ignored directories
            if ($skipIgnored && Strings::contains($file, "/Schema/", "/System/")) {
                continue;
            }

            $className = Strings::replace($file, [ $sourcePath, ".php" ], "");
            $className = Strings::substringAfter($className, "/", true);
            $className = Strings::replace($className, "/", "\\");
            $className = "\\" . Package::Namespace . $className;

            if (empty($dir)) {
                $result[] = $className;
            } else {
                $classKey = Strings::substringAfter($className, "\\");
                $result[$classKey] = $className;
            }
        }
        return $result;
    }

    /**
     * Loads a File from the App or defaults to the Framework
     * @return string
     */
    public static function loadEmailTemplate(): string {
        $path   = self::getAppPath(Package::DataDir, self::EmailFile);
        $result = "";
        if (File::exists($path)) {
            $result = File::read($path);
        }
        if (empty($result)) {
            $path   = self::getFramePath(self::DataDir, self::EmailFile);
            $result = File::read($path);
        }
        return $result;
    }

    /**
     * Loads a JSON File
     * @param string $dir
     * @param string $fileName
     * @return array{}
     */
    public static function loadJSON(string $dir, string $fileName): array {
        $file = Strings::addSuffix($fileName, ".json");
        $path = self::getAppPath($dir, $file);
        return JSON::readFile($path);
    }

    /**
     * Loads a Data File
     * @param DataFile $file
     * @return array{}
     */
    public static function loadData(DataFile $file): array {
        return self::loadJSON(Package::DataDir, $file->name());
    }

    /**
     * Loads a Template File
     * @param string $fileName
     * @return string
     */
    public static function loadTemplate(string $fileName): string {
        $file = Strings::addSuffix($fileName, ".mu");
        $path = self::getAppPath(Package::TemplateDir, $file);
        return File::read($path);
    }

    /**
     * Saves a Data File
     * @param string          $fileName
     * @param string[]|string $contents
     * @return boolean
     */
    public static function saveData(string $fileName, array|string $contents): bool {
        $file = Strings::addSuffix($fileName, ".json");
        $path = self::getAppPath(Package::DataDir, $file);
        return JSON::writeFile($path, $contents);
    }

    /**
     * Logs a JSON File
     * @param string $file
     * @param mixed  $contents
     * @return boolean
     */
    public static function logFile(string $file, mixed $contents): bool {
        $path = self::getAppPath(Package::DataDir, Package::LogDir);
        File::createDir($path);
        return File::write("$path/$file.json", JSON::encode($contents, true));
    }



    /**
     * Loads a Framework JSON File
     * @param string $dir
     * @param string $fileName
     * @return array{}
     */
    public static function loadFrameJSON(string $dir, string $fileName): array {
        $file = Strings::addSuffix($fileName, ".json");
        $path = self::getFramePath($dir, $file);
        return JSON::readFile($path);
    }

    /**
     * Loads a Data File
     * @param DataFile $file
     * @return array{}
     */
    public static function loadFrameData(DataFile $file): array {
        return self::loadFrameJSON(self::DataDir, $file->name());
    }

    /**
     * Loads a Template File
     * @param string $fileName
     * @return string
     */
    public static function loadFrameTemplate(string $fileName): string {
        $file = Strings::addSuffix($fileName, ".mu");
        $path = self::getFramePath(self::TemplateDir, $file);
        return File::read($path);
    }
}
