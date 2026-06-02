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
 * The Requested Type
 */
enum RequestedType implements Enum, JsonSerializable {
    use IsEnum;

    case None;

    case Date;
    case Enum;
    case File;
    case Status;

    case String;
    case Encrypt;
    case Boolean;
    case Number;
    case Float;

    case Array;
    case Dictionary;



    /**
     * Creates an RequestType from a FieldType
     * @param FieldType $fieldType
     * @return RequestedType
     */
    public static function fromField(FieldType $fieldType): RequestedType {
        return match ($fieldType) {
            FieldType::Date    => self::Date,
            FieldType::Enum    => self::Enum,
            FieldType::File    => self::File,
            FieldType::Boolean => self::Boolean,
            FieldType::Number  => self::Number,
            FieldType::Float   => self::Float,
            FieldType::Encrypt => self::Encrypt,
            FieldType::Array   => self::Array,
            FieldType::JSON    => self::Dictionary,
            default            => self::String,
        };
    }

    /**
     * Creates an RequestType from a Type
     * @param string $typeName
     * @return RequestedType
     */
    public static function fromType(string $typeName): RequestedType {
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

            self::Date       => "Date",
            self::Enum       => $enumType !== "" ? $enumType : "string",
            self::File       => "File",
            self::Status     => "string",

            self::String,
            self::Encrypt    => "string",
            self::Boolean    => "bool",
            self::Number     => "int",
            self::Float      => "float",

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

            self::Date       => "null",
            self::Enum       => $enumType !== "" ? "$enumType::None" : "\"\"",
            self::File       => "new File()",
            self::Status     => "\"\"",

            self::String,
            self::Encrypt    => "\"\"",
            self::Boolean    => "false",
            self::Number,
            self::Float      => "0",

            self::Dictionary => "new Dictionary()",
            self::Array      => "[]",
        };
    }
}
