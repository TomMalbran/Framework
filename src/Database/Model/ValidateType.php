<?php
namespace Framework\Database\Model;

use Framework\Utils\Strings;

/**
 * The Validate Type
 */
enum ValidateType {

    case None;

    case String;
    case Email;
    case Url;
    case Color;

    case Number;
    case Price;
    case Date;

    case Status;



    /**
     * Creates a Validate Type from a String
     * @param string $value
     * @return ValidateType
     */
    public static function fromValue(string $value): ValidateType {
        foreach (self::cases() as $case) {
            if (Strings::isEqual($case->name, $value)) {
                return $case;
            }
        }
        return self::String;
    }
}
