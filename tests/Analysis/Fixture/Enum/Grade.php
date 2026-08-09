<?php
namespace Tests\Analysis\Fixture\Enum;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/** Declared the way a model field wants it — both interfaces, and the trait */
enum Grade implements Enum, JsonSerializable {
    use IsEnum;

    case None;
    case Pass;
    case Fail;
}
