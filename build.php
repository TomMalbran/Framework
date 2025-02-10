<?php
require_once __DIR__ . "/vendor/autoload.php";

use Framework\Framework;

/**
 * Runs the Code Generation
 * @return void
 */
function main() {
    if (!defined("STDIN")) {
        exit("This script can only be run from the command line.\n");
    }

    // Generate the code
    Framework::generateCode();
}

main();
