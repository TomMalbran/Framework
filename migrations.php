<?php
require_once __DIR__ . "/vendor/autoload.php";

use Framework\Framework;

/**
 * Runs the Migrations
 * @return void
 */
function main() {
    if (!defined("STDIN")) {
        exit("This script can only be run from the command line.\n");
    }

    $canDelete = !empty($_SERVER["argv"][1]) && $_SERVER["argv"][1] === "--delete";
    Framework::migrateData($canDelete);

    print("\n\nMigrations completed\n\n");
}

main();
