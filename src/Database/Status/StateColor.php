<?php
namespace Framework\Database\Status;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The State Colors used by the System
 */
enum StateColor implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case Green;
    case Yellow;
    case Red;



    /**
     * Returns the color name in lowercase
     * @return string
     */
    public function getColor(): string {
        return Strings::lowerCaseFirst($this->name);
    }
}
