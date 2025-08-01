<?php
namespace Framework\Database\Status;

use Framework\Database\Status\StateColor;

/**
 * The Status used by the System
 */
enum Status {

    case None;

    case Active;
    case Inactive;



    /**
     * Returns the default values used in the Code generation
     * @return array<string,string>
     */
    public static function getValues(): array {
        $result = [];
        $result[self::Active->name]   = StateColor::Green->getColor();
        $result[self::Inactive->name] = StateColor::Red->getColor();
        return $result;
    }

    /**
     * Returns the default groups used in the Code generation
     * @return array<string,string[]>
     */
    public static function getGroups(): array {
        return [
            "General" => [ self::Active->name, self::Inactive->name ],
        ];
    }
}
