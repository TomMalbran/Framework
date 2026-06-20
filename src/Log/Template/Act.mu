<?php
namespace {{namespace}};

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Log Actions
 */
enum Act implements Enum, JsonSerializable {
    use IsEnum;

    case None;
{{#items}}
    case {{.}};
{{/items}}
}
