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
     * @param array|string $value
     * @param boolean      $asPretty Optional.
     * @return string
     */
    public static function encode($value, bool $asPretty = false): string {
        if (self::isValid($value)) {
            return $value;
        }
        return json_encode($value, $asPretty ? JSON_PRETTY_PRINT : null);
    }

    /**
     * Decodes a String if it is not already decoded
     * @param array|string $value
     * @param boolean      $asArray Optional.
     * @return object|array
     */
    public static function decode($value, bool $asArray = false) {
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
        $content = self::decode($value);
        return Strings::join($value, ", ");
    }

    /**
     * Converts a Coma Separated Value into an encoded JSON
     * @param string $value
     * @return string
     */
    public static function fromCSV(string $value): string {
        $content = Strings::split($value, ",", true, true);
        return self::encode($content);
    }



    /**
     * Reads a JSON file
     * @param string  $path
     * @param boolean $asArray Optional.
     * @return object|array
     */
    public static function readFile(string $path, bool $asArray = false) {
        if (File::exists($path)) {
            return self::decode(file_get_contents($path), $asArray);
        }
        return $asArray ? [] : new stdClass();
    }

    /**
     * Reads a JSON url
     * @param string  $url
     * @param boolean $asArray Optional.
     * @return object|array
     */
    public static function readUrl(string $url, bool $asArray = false) {
        return self::decode(file_get_contents($url), $asArray);
    }

    /**
     * Writes a JSON File
     * @param string          $file
     * @param string|string[] $contents
     * @return void
     */
    public static function writeFile(string $file, $contents): void {
        $value = Arrays::toArray($contents);
        file_put_contents($path, self::encode($value, true));
    }
}
