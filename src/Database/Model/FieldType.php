<?php
namespace Framework\Database\Model;

use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Date\Date;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Field Type
 */
enum FieldType implements Enum, JsonSerializable {
    use IsEnum;


    case None;

    case Date;
    case Enum;
    case JSON;
    case Array;

    case Boolean;
    case Number;
    case Float;

    case String;
    case Text;
    case LongText;
    case Encrypt;
    case File;



    /**
     * Creates an FieldType from a Type
     * @param string $typeName
     * @return FieldType
     */
    public static function fromType(string $typeName): FieldType {
        if ($typeName === Date::class) {
            return self::Date;
        }
        if ($typeName === JSON::class) {
            return self::JSON;
        }

        return match ($typeName) {
            "array"  => FieldType::Array,
            "bool"   => FieldType::Boolean,
            "float"  => FieldType::Float,
            "int"    => FieldType::Number,
            "string" => FieldType::String,
            default  => FieldType::String,
        };
    }

    /**
     * Returns true if the given Type is a valid Class Type for a Field
     * @param string $typeName
     * @return bool
     */
    public static function isValidClass(string $typeName): bool {
        return $typeName === Date::class || $typeName === JSON::class;
    }



    /**
     * Returns true if the Field Type is a String type
     * @return bool
     */
    public function isString(): bool {
        return match ($this) {
            FieldType::String,
            FieldType::Text,
            FieldType::LongText => true,
            default             => false,
        };
    }

    /**
     * Returns the Native Type from the given Field Type
     * @param string $enumClass Optional.
     * @param bool   $forEntity Optional.
     * @return string
     */
    public function getCodeType(string $enumClass = "", bool $forEntity = false): string {
        $enumType = $enumClass !== "" ? Strings::substringAfter($enumClass, "\\") : "string";
        return match ($this) {
            FieldType::None    => "",

            FieldType::Date    => "Date",
            FieldType::Enum    => $enumType,
            FieldType::JSON    => $forEntity ? "Dictionary" : "JsonSerializable|array",
            FieldType::Array   => "array",

            FieldType::Boolean => "bool",
            FieldType::Number  => "int",
            FieldType::Float   => "float",

            FieldType::String,
            FieldType::Text,
            FieldType::LongText,
            FieldType::Encrypt,
            FieldType::File    => "string",
        };
    }

    /**
     * Returns the Native or Value Type from the given Field Type
     * @param string $enumClass Optional.
     * @return string
     */
    public function getValueType(string $enumClass = ""): string {
        return match ($this) {
            FieldType::None    => "",

            FieldType::Date    => "DateValue",
            FieldType::Enum    => "EnumValue",
            FieldType::JSON    => "",
            FieldType::Array   => "",

            FieldType::Boolean => "BoolValue",
            FieldType::Number  => "NumberValue",
            FieldType::Float   => "FloatValue",

            FieldType::String,
            FieldType::Text,
            FieldType::LongText,
            FieldType::Encrypt,
            FieldType::File    => "StringValue",
        };
    }

    /**
     * Returns the Default value for the given PHP Type
     * @param string $type
     * @return ?string
     */
    public static function getDefault(string $type): string|null {
        return match ($type) {
            "boolean", "bool" => "false",
            "integer", "int"  => "0",
            "float"           => "0",
            "string"          => '""',
            "array"           => '[]',
            "Date"            => null,
            "Dictionary"      => null,
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
