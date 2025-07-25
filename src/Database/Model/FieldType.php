<?php
namespace Framework\Database\Model;

use Framework\Utils\Strings;

/**
 * The Field Type
 */
enum FieldType {

    case None;

    case Number;
    case Boolean;
    case Float;
    case String;
    case Text;
    case LongText;
    case JSON;
    case Encrypt;
    case File;



    /**
     * Creates an FieldType from a String
     * @param string $value
     * @return FieldType
     */
    public static function from(string $value): FieldType {
        foreach (self::cases() as $case) {
            if (Strings::isEqual($case->name, $value)) {
                return $case;
            }
        }
        return self::String;
    }
}
