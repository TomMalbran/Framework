<?php
namespace Framework\Database\Status;

use Framework\Utils\Strings;

/**
 * The State Colors used by the System
 */
enum StateColor {

    case None;

    case Green;
    case Yellow;
    case Red;



    /**
     * Creates a State Color from a String
     * @param string $value
     * @return StateColor
     */
    public static function fromValue(string $value): StateColor {
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }
        return self::None;
    }

    /**
     * Returns the color name in lowercase
     * @return string
     */
    public function getColor(): string {
        return Strings::lowerCaseFirst($this->name);
    }
}
