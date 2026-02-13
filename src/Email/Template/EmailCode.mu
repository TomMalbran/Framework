<?php
namespace {{namespace}};

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Email Codes
 */
enum EmailCode implements Enum, JsonSerializable {
    use IsEnum;

    case None;

{{#codes}}
    case {{.}};
{{/codes}}
}
