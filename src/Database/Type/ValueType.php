<?php
namespace Framework\Database\Type;

use Framework\Database\Model\FieldType;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Date\Date;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Value Type
 */
enum ValueType implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case Enum;
    case Date;
    case Hour;
    case String;
    case Encrypt;
    case Boolean;
    case Number;
    case Float;
    case File;

    case Array;
    case Dictionary;



    /**
     * Creates an ValueType from a FieldType
     * @param FieldType $fieldType
     * @return ValueType
     */
    public static function fromField(FieldType $fieldType): ValueType {
        return match ($fieldType) {
            FieldType::Date    => self::Date,
            FieldType::Enum    => self::Enum,
            FieldType::JSON    => self::Dictionary,
            FieldType::Array   => self::Array,
            FieldType::Boolean => self::Boolean,
            FieldType::Number  => self::Number,
            FieldType::Float   => self::Float,
            FieldType::Encrypt => self::Encrypt,
            FieldType::File    => self::File,
            default            => self::String,
        };
    }

    /**
     * Creates an ValueType from a Type
     * @param string $typeName
     * @return ValueType
     */
    public static function fromType(string $typeName): ValueType {
        if ($typeName === Date::class) {
            return self::Date;
        }
        if ($typeName === JSON::class) {
            return self::Dictionary;
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
     * Returns the Native Type from the given Field Type
     * @param string $enumClass Optional.
     * @return string
     */
    public function getCodeType(string $enumClass = ""): string {
        return match ($this) {
            self::None       => "",

            self::Enum       => $enumClass !== "" ? Strings::substringAfter($enumClass, "\\") : "string",
            self::Date       => "Date",
            self::Hour       => "string",

            self::String,
            self::Encrypt    => "string",
            self::Boolean    => "bool",
            self::Number     => "int",
            self::Float      => "float",
            self::File       => "string",

            self::Dictionary => "Dictionary",
            self::Array      => "array",
        };
    }
}
