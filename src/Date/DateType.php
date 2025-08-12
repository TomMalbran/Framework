<?php
namespace Framework\Date;

use Framework\Utils\Strings;

/**
 * The Date Types used by the System
 */
enum DateType {

    case None;

    case Start;
    case Middle;
    case End;



    /**
     * Creates a Date Type from a String
     * @param string $value
     * @return DateType
     */
    public static function from(string $value): DateType {
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }
        return self::None;
    }

    /**
     * Returns the name in lowercase
     * @return string
     */
    public function getName(): string {
        return Strings::lowerCaseFirst($this->name);
    }
}
