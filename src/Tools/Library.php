<?php
namespace Framework\Tools;

use Framework\Application;
use Framework\Console;
use Framework\Analysis\Attr\NotTested;
use Framework\Discovery\Attr\ConsoleCommand;
use Framework\Discovery\Type\LibraryData;
use Framework\File\Storage;

/**
 * The Libraries copied into a Project
 */
class Library {

    /**
     * Places every Library the composer file of the App asks for
     * @return void
     */
    #[ConsoleCommand("libraries")]
    #[NotTested("It is one of the Tools")]
    public static function place(): void {
        $libraries = Application::getComposer()->libraries;
        if (count($libraries) === 0) {
            print("There are no Libraries to place\n");
            return;
        }

        foreach ($libraries as $library) {
            self::placeOne($library);
        }
    }

    /**
     * Places the one Library of the App that goes by the given name, asking
     * which one when it is not given
     * @param string $name Optional.
     * @return void
     */
    #[ConsoleCommand("library")]
    public static function placeByName(string $name = ""): void {
        $libraries = Application::getComposer()->libraries;
        if (count($libraries) === 0) {
            print("There are no Libraries to place\n");
            return;
        }

        if ($name === "") {
            $name = Console::choose("Which Library", array_keys($libraries));
            if ($name === "") {
                print("No Library was chosen\n");
                return;
            }
        }
        if (!isset($libraries[$name])) {
            print("There is no Library called $name\n");
            return;
        }

        self::placeOne($libraries[$name]);
    }

    /**
     * Places the given Library, over whatever is at its path
     * @param LibraryData $library
     * @return bool
     */
    #[NotTested("It is one of the Tools")]
    public static function placeOne(LibraryData $library): bool {
        if (!$library->isValid()) {
            print("- {$library->name} needs a tag or a branch, a url and a path to be placed\n");
            return false;
        }

        $toPath = Application::getIndexPath($library->path);
        print("- {$library->name} {$library->getReference()}... ");
        $fromPath = self::download($library);
        if ($fromPath === "") {
            print("could not be downloaded\n");
            return false;
        }

        $copied = self::copySource($library, $fromPath, $toPath);
        Storage::deleteDir(Storage::getDirectory($fromPath));

        if (!$copied) {
            print("could not be placed\n");
            return false;
        }

        print("placed in {$library->path}\n");
        return true;
    }



    /**
     * Downloads the Library, and returns the path the repository was left at
     * @param LibraryData $library
     * @return string
     */
    private static function download(LibraryData $library): string {
        $tempName = "framework-{$library->getFileName()}";
        $tempPath = Storage::parsePath(sys_get_temp_dir(), $tempName);
        $zipPath  = "{$tempPath}.zip";

        Storage::deleteDir($tempPath);
        Storage::deleteFile($zipPath);
        Storage::createDir($tempPath);
        if (!Storage::fileExists($tempPath)) {
            return "";
        }

        if (!Storage::writeFromUrl($zipPath, $library->getArchiveUrl())) {
            Storage::deleteDir($tempPath);
            return "";
        }

        $extracted = Storage::extractZip($zipPath, $tempPath);
        Storage::deleteFile($zipPath);

        // The archive of a tag holds the repository under one directory of its own,
        // named however the one it came from names it, so it is taken rather than guessed
        $directories = Storage::getDirectoriesInDir($tempPath);
        if (!$extracted || count($directories) !== 1) {
            Storage::deleteDir($tempPath);
            return "";
        }
        return $directories[0];
    }

    /**
     * Copies the source of the Library, replacing whatever is at the path
     * @param LibraryData $library
     * @param string      $fromPath
     * @param string      $toPath
     * @return bool
     */
    private static function copySource(
        LibraryData $library,
        string $fromPath,
        string $toPath,
    ): bool {
        $sourcePath = $fromPath;
        if ($library->source !== "") {
            $sourcePath = Storage::parsePath($fromPath, $library->source);
        }
        if (!is_dir($sourcePath)) {
            return false;
        }

        // What the version dropped would be left behind by a copy alone, and the
        // path is the one of the library, so nothing else of the project is there.
        // The source is known to be there by now, so this is not thrown away for nothing
        Storage::deleteDir($toPath);

        return Storage::copyDir($sourcePath, $toPath);
    }
}
