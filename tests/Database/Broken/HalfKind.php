<?php
namespace Tests\Database\Broken;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

/**
 * An Enum that is one of the Framework but cannot be written out as JSON
 */
enum HalfKind implements Enum {
    use IsEnum;

    case None;

    case First;
}
