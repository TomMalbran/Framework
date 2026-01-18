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
     * Creates a Field Type from a String
     * @param string $value
     * @return FieldType
     */
    public static function fromValue(string $value): FieldType {
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
     * Returns true if the Field Type is a String type
     * @param FieldType $type
     * @return bool
     */
    public static function isString(FieldType $type): bool {
        return match ($type) {
            FieldType::String,
            FieldType::Text,
            FieldType::LongText => true,
            default => false,
        };
    }

    /**
     * Returns the PHP Type from the given Field Type
     * @param FieldType $type
     * @return string
     */
    public static function getCodeType(FieldType $type): string {
        return match ($type) {
            FieldType::Boolean => "bool",
            FieldType::Number  => "int",
            FieldType::Float   => "float",
            default            => "string",
        };
    }

    /**
     * Returns the Default value for the given PHP Type
     * @param string $type
     * @return string
     */
    public static function getDefault(string $type): string {
        return match ($type) {
            "boolean", "bool" => "false",
            "integer", "int"  => "0",
            "float"           => "0",
            "string"          => '""',
            "array"           => '[]',
            default           => "null",
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
