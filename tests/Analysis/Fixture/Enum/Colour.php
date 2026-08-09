<?php
namespace Tests\Analysis\Fixture\Enum;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

enum Colour implements Enum {
    use IsEnum;

    case Red;
    case Blue;
}
