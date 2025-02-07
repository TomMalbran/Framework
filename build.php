<?php
require_once __DIR__ . "/vendor/autoload.php";

use Framework\Framework;
use Framework\Database\Generator;
use Framework\Builder\Builder;

/**
 * Runs the Code Generation
 * @return void
 */
function main() {
    if (!defined("STDIN")) {
        exit("This script can only be run from the command line.\n");
    }

    // Generate the code
    Framework::create(__DIR__, "", false);
    Generator::generateInternal();
    Builder::generateCode();
}

main();
