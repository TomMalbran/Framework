<?php
namespace Framework\Database\Type;

use Framework\Database\Model\FieldType;
use Framework\Date\Date;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\File\File;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Request Type
 */
enum RequestType implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case Date;
    case Enum;

    case String;
    case Encrypt;
    case Boolean;
    case Number;
    case Float;
    case File;

    case Array;
    case Dictionary;



    /**
     * Creates an RequestType from a FieldType
     * @param FieldType $fieldType
     * @return RequestType
     */
    public static function fromField(FieldType $fieldType): RequestType {
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
     * Creates an RequestType from a Type
     * @param string $typeName
     * @return RequestType
     */
    public static function fromType(string $typeName): RequestType {
        if ($typeName === Date::class) {
            return self::Date;
        }
        if ($typeName === JSON::class) {
            return self::Dictionary;
        }
        if ($typeName === File::class) {
            return self::File;
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
        $enumType = Strings::substringAfter($enumClass, "\\");
        return match ($this) {
            self::None       => "",

            self::Enum       => $enumType !== "" ? $enumType : "string",
            self::Date       => "Date",

            self::String,
            self::Encrypt    => "string",
            self::Boolean    => "bool",
            self::Number     => "int",
            self::Float      => "float",

            self::File       => "File",
            self::Dictionary => "Dictionary",
            self::Array      => "array",
        };
    }

    /**
     * Returns the Default Value for the given Type
     * @param string $enumClass Optional.
     * @return string
     */
    public function getDefaultValue(string $enumClass = ""): string {
        $enumType = Strings::substringAfter($enumClass, "\\");
        return match ($this) {
            self::None       => "null",

            self::Enum       => $enumType !== "" ? "$enumType::None" : "\"\"",
            self::Date       => "null",

            self::String,
            self::Encrypt    => "\"\"",
            self::Boolean    => "false",
            self::Number,
            self::Float      => "0",

            self::File       => "new File()",
            self::Dictionary => "new Dictionary()",
            self::Array      => "[]",
        };
    }
}
