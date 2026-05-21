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
            "array"  => self::Array,
            "bool"   => self::Boolean,
            "float"  => self::Float,
            "int"    => self::Number,
            "string" => self::String,
            default  => self::String,
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
            self::String,
            self::Text,
            self::LongText => true,
            default        => false,
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
            self::None    => "",

            self::Date    => "Date",
            self::Enum    => $enumType,
            self::JSON    => $forEntity ? "Dictionary" : "JsonSerializable|array",
            self::Array   => "array",

            self::Boolean => "bool",
            self::Number  => "int",
            self::Float   => "float",

            self::String,
            self::Text,
            self::LongText,
            self::Encrypt,
            self::File    => "string",
        };
    }

    /**
     * Returns the Native or Value Type from the given Field Type
     * @param string $enumClass Optional.
     * @return string
     */
    public function getValueType(string $enumClass = ""): string {
        return match ($this) {
            self::None    => "",

            self::Date    => "DateValue",
            self::Enum    => "EnumValue",
            self::JSON    => "",
            self::Array   => "",

            self::Boolean => "BoolValue",
            self::Number  => "NumberValue",
            self::Float   => "FloatValue",

            self::String,
            self::Text,
            self::LongText,
            self::Encrypt,
            self::File    => "StringValue",
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
