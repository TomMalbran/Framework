<?php
namespace Framework\Core;

use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The Variable Types used by the System
 */
enum VariableType {

    case None;

    case Array;
    case List;
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
     * @param mixed   $value
     * @param boolean $useLists
     * @return VariableType
     */
    public static function get(mixed $value, bool $useLists): VariableType {
        if (is_array($value)) {
            return $useLists ? self::List : self::Array;
        }
        if (gettype($value) === "boolean") {
            return self::Boolean;
        }
        if (gettype($value) === "integer") {
            return self::Integer;
        }
        if (gettype($value) === "double") {
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
            self::List    => "array",
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
            self::Array   => "array<string|integer,mixed>",
            self::List    => "string[]",
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
            self::Boolean => !Arrays::isEmpty($value) ? "1" : "0",
            self::Array   => JSON::encode($value),
            default       => Strings::toString($value),
        };
    }
}
