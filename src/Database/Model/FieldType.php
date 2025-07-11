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

    /**
     * Creates an FieldType from a Type
     * @param string $type
     * @return FieldType
     */
    public static function fromType(string $type): FieldType {
        return match ($type) {
            "bool"   => FieldType::Boolean,
            "float"  => FieldType::Float,
            "int"    => FieldType::Number,
            "string" => FieldType::String,
            default  => FieldType::None,
        };
    }

    /**
     * Returns the Name of the FieldType
     * @return string
     */
    public function getName(): string {
        return Strings::toLowerCase($this->name);
    }
}
