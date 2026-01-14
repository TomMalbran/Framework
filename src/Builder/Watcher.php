<?php
namespace Framework\Builder;

use Framework\Application;
use Framework\Discovery\ConsoleCommand;
use Framework\File\File;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * The Discovery Watcher
 */
class Watcher {

    private const Interval = 2;



    /**
     * Watches the App source for changes
     * @return never
     */
    #[ConsoleCommand("watch")]
    public static function watch(): never {
        print("Watching for changes...\n");

        $watchPath      = Application::getBasePath();
        $basePath       = Application::getIndexPath();

        $ignorePatterns = self::parseGitignore($basePath);
        $previousState  = self::scanDirectory($watchPath, $basePath, $ignorePatterns);

        // @phpstan-ignore while.alwaysTrue
        while (true) {
            sleep(self::Interval);

            $currentState = self::scanDirectory($watchPath, $basePath, $ignorePatterns);
            $fileChanged  = false;

            // Check for added or modified files
            foreach ($currentState as $file => $mtime) {
                if (!isset($previousState[$file])) {
                    $fileChanged = true;
                } elseif ($previousState[$file] !== $mtime) {
                    $fileChanged = true;
                }
            }

            // Check for deleted files
            foreach ($previousState as $file => $mtime) {
                if (!isset($currentState[$file])) {
                    $fileChanged = true;
                }
            }

            // Update the previous state
            $previousState = $currentState;
            if (!$fileChanged) {
                continue;
            }

            // Build the project
            $startTime = microtime(true);
            print("\n│ Building app...");
            $result   = exec("./framework build");
            $duration = Numbers::round(microtime(true) - $startTime, 1);
            print("\n│ $result in {$duration}s\n");
        }
    }

    /**
     * Parses the .gitignore file and returns an array of ignore patterns.
     * @param string $basePath
     * @return string[]
     */
    private static function parseGitignore(string $basePath): array {
        if (!File::exists($basePath, ".gitignore")) {
            return [];
        }

        $content  = File::read($basePath, ".gitignore");
        $patterns = Strings::split($content, "\n", true, true);
        $regexes  = [];

        foreach ($patterns as $pattern) {
            // Skip comments
            if ($pattern === "" || Strings::startsWith($pattern, "#")) {
                continue;
            }

            // Escape regex special characters
            $regex = preg_quote($pattern, "/");

            // Convert glob wildcards
            $regex = Strings::replace($regex, "\*", ".*");
            $regex = Strings::replace($regex, "\?", ".");

            // Handle ** (simplified - a full implementation would be more complex)
            $regex = Strings::replace($regex, "\*\*", "(?:[^/]+/)*");

            // Handle leading and trailing slashes for anchoring
            if (Strings::startsWith($pattern, "/")) {
                $regex = "^" . ltrim($regex, "\/");
            }

            // Only match directories
            if (Strings::endsWith($pattern, "/")) {
                $regex .= "$";
            }

            // Add delimiters
            $regexes[] = "/$regex/";
        }

        return $regexes;
    }

    /**
     * Scans a directory for files and their modification times.
     * @param string   $path
     * @param string   $basePath
     * @param string[] $ignorePatterns
     * @return array<string,integer>
     */
    private static function scanDirectory(string $path, string $basePath, array $ignorePatterns): array {
        $files  = File::getFilesInDir($path, true);
        $result = [];
        foreach ($files as $file) {
            if (!self::isIgnored($file, $basePath, $ignorePatterns)) {
                $result[$file] = (int)filemtime($file);
            }
        }
        return $result;
    }

    /**
     * Checks if a file path matches any of the .gitignore regex patterns.
     * @param string   $filePath
     * @param string   $basePath
     * @param string[] $ignorePatterns
     * @return bool
     */
    private static function isIgnored(string $filePath, string $basePath, array $ignorePatterns): bool {
        $relativePath = Strings::stripStart($filePath, "$basePath/");
        foreach ($ignorePatterns as $pattern) {
            if (Strings::match($relativePath, $pattern)) {
                return true;
            }
        }
        return false;
    }
}
