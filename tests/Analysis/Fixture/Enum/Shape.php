<?php
namespace Tests\Analysis\Fixture\Enum;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

/** Uses IsEnum but never declares a None case */
enum Shape implements Enum {
    use IsEnum;

    case Circle;
    case Square;
}
