<?php
namespace Framework;

use Framework\Builder\Builder;
use Framework\Builder\Watcher;
use Framework\Database\Migration;
use Framework\System\Package;

/**
 * The Framework Console
 */
class Console {

    /**
     * Runs the Console
     * @return void
     */
    public static function run(): void {
        $argv      = is_array($_SERVER["argv"] ?? null) ? $_SERVER["argv"] : [];
        $utility   = $argv[1] ?? "";
        $canDelete = ($argv[2] ?? "") === "--delete";

        echo "FRAMEWORK\n";

        switch ($utility) {
        case "-v":
        case "version":
            print("Version: " . Package::Version . "\n");
            break;
        case "build":
            print("Building the Code...\n");
            Builder::build($canDelete);
            break;
        case "migrate":
            print("Migrating data...\n");
            Migration::migrate($canDelete);
            break;
        case "watch":
            print("Watching for changes...\n");
            Watcher::watch();
        default:
            echo "Available utilities: build, migrate, watch\n";
        }
    }
}
