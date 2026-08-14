<?php
namespace Tests\Database\Every;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Kind of a Thing, which is what an Enum field is typed as
 */
enum EveryKind implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case First;
    case Second;
}
