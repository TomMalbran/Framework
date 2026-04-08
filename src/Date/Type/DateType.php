<?php
namespace Framework\Date\Type;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Date Types used by the System
 */
enum DateType implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case Start;
    case Middle;
    case End;



    /**
     * Returns the name in lowercase
     * @return string
     */
    public function getName(): string {
        return Strings::lowerCaseFirst($this->name);
    }
}
