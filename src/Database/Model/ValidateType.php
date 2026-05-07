<?php
namespace Framework\Database\Model;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The Validate Type
 */
enum ValidateType implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case String;
    case Email;
    case Url;

    case Number;
    case Price;
    case Date;

    case Enum;
    case List;
    case Status;
}
