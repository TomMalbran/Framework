<?php
namespace Framework\Utils;

use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;
use stdClass;

/**
 * The JSON Utils
 */
class JSON {

    /**
     * Returns true if the given value is a JSON object
     * @param string|array $value
     * @return boolean
     */
    public static function isValid($value): bool {
        if (is_string($value)) {
            json_decode($value);
            return json_last_error() == JSON_ERROR_NONE;
        }
        return false;
    }

    /**
     * Encodes an Object as a string if it is not already encoded
     * @param array   $value
     * @param boolean $asPretty Optional.
     * @return string
     */
    public static function encode(array $value, bool $asPretty = false): string {
        if (self::isValid($value)) {
            return $value;
        }
        return json_encode($value, $asPretty ? JSON_PRETTY_PRINT : null);
    }

    /**
     * Decodes a String if it is not already decoded
     * @param string  $value
     * @param boolean $asArray Optional.
     * @return object|array
     */
    public static function decode(string $value, bool $asArray = false) {
        if (!self::isValid($value)) {
            return $value;
        }
        return json_decode($value, $asArray);
    }



    /**
     * Converts an encoded JSON into a Coma Separated Value
     * @param string $value
     * @return string
     */
    public static function toCSV(string $value): string {
        $value = self::decode($value);
        return Strings::join($value, ", ");
    }

    /**
     * Converts a Coma Separated Value into an encoded JSON
     * @param string $value
     * @return string
     */
    public static function fromCSV(string $value): string {
        $parts  = Strings::split($value, ",");
        $result = [];
        foreach ($parts as $part) {
            if (!empty($part)) {
                $result[] = trim($part);
            }
        }
        return self::encode($result);
    }



    /**
     * Reads a JSON file
     * @param string $path
     * @return object|array
     */
    public static function read(string $path) {
        if (File::exists($path)) {
            return self::decode(file_get_contents($path), true);
        }
        return new stdClass();
    }

    /**
     * Writes a JSON File
     * @param string          $file
     * @param string|string[] $contents
     * @return void
     */
    public static function write(string $file, $contents): void {
        $value = Arrays::toArray($contents);
        file_put_contents($path, self::encode($value, true));
    }
}
