<?php
namespace {{namespace}};

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Log Sections
 */
enum Sec implements Enum, JsonSerializable {
    use IsEnum;

    case None;
{{#items}}
    case {{.}};
{{/items}}
}
