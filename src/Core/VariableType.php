<?php
namespace Framework\Core;

use Framework\Utils\Arrays;
use Framework\Utils\JSON;

/**
 * The Variable Types used by the System
 */
enum VariableType {

    case None;

    case Array;
    case Boolean;
    case Integer;
    case Float;
    case String;



    /**
     * Creates a Variable Type from a String
     * @param string $value
     * @return VariableType
     */
    public static function from(string $value): VariableType {
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }
        return self::None;
    }

    /**
     * Returns the Setting Type based on the value
     * @param mixed $value
     * @return VariableType
     */
    public static function get(mixed $value): VariableType {
        if (Arrays::isArray($value)) {
            return self::Array;
        }
        if (gettype($value) == "boolean") {
            return self::Boolean;
        }
        if (gettype($value) == "integer") {
            return self::Integer;
        }
        if (gettype($value) == "double") {
            return self::Float;
        }
        return self::String;
    }

    /**
     * Returns the Setting Type based on the value
     * @param VariableType $type
     * @return string
     */
    public static function getType(VariableType $type): string {
        return match ($type) {
            self::Array   => "array",
            self::Boolean => "bool",
            self::Integer => "int",
            self::Float   => "float",
            default       => "string",
        };
    }

    /**
     * Returns the Setting Doc Type based on the value
     * @param VariableType $type
     * @return string
     */
    public static function getDocType(VariableType $type): string {
        return match ($type) {
            self::Array   => "array<string,string>",
            self::Boolean => "boolean",
            self::Integer => "integer",
            self::Float   => "float",
            default       => "string",
        };
    }

    /**
     * Encodes the Settings Value for the Database
     * @param VariableType $type
     * @param mixed        $value
     * @return string
     */
    public static function encodeValue(VariableType $type, mixed $value): string {
        return match ($type) {
            self::Boolean => !empty($value) ? "1" : "0",
            self::Array   => JSON::encode($value),
            default       => (string)$value,
        };
    }
}
