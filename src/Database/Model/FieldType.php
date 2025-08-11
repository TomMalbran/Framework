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
     * Converts a PHP Type to a Document Type
     * @param string $type
     * @return string
     */
    public static function getDocType(string $type): string {
        return match ($type) {
            "bool"  => "boolean",
            "int"   => "integer",
            default => $type,
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
