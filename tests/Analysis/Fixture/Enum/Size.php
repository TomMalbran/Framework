<?php
namespace Tests\Analysis\Fixture\Enum;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

/** Uses IsEnum and declares the None case it is asked for */
enum Size implements Enum {
    use IsEnum;

    case None;
    case Small;
    case Large;
}
