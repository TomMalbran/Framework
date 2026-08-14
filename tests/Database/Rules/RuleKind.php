<?php
namespace Tests\Database\Rules;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Kind of a Rule row, which is what a typeOf is checked against
 */
enum RuleKind implements Enum, JsonSerializable {
    case None;

    case First;
    case Second;

    use IsEnum;
}
