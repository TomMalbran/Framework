<?php
namespace Framework\System;

use Framework\Utils\Arrays;
use Framework\Utils\JSON;

/**
 * The Variable Types used by the System
 */
class VariableType {

    const Array   = "Array";
    const Boolean = "Boolean";
    const Integer = "Integer";
    const Float   = "Float";
    const String  = "String";



    /**
     * Returns the Setting Type based on the value
     * @param mixed $value
     * @return string
     */
    public static function get(mixed $value): string {
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
     * @param string $type
     * @return string
     */
    public static function getType(string $type): string {
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
     * @param string $type
     * @return string
     */
    public static function getDocType(string $type): string {
        return match ($type) {
            self::Array   => "array{}",
            self::Boolean => "boolean",
            self::Integer => "integer",
            self::Float   => "float",
            default       => "string",
        };
    }

    /**
     * Encodes the Settings Value for the Database
     * @param integer $type
     * @param mixed   $value
     * @return string
     */
    public static function encodeValue(int $type, mixed $value): string {
        return match ($type) {
            self::Boolean => !empty($value) ? "1" : "0",
            self::Array   => JSON::encode($value),
            default       => (string)$value,
        };
    }
}
