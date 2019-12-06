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
     * @param string $value
     * @return boolean
     */
    public static function is($value) {
        if (is_string($value)) {
            json_decode($value);
            return (json_last_error() == JSON_ERROR_NONE);
        }
        return false;
    }

    /**
     * Encodes an Object as a string if it is not already encoded
     * @param mixed   $value
     * @param boolean $asPretty Optional.
     * @return string
     */
    public static function encode($value, $asPretty = false) {
        if (self::is($value)) {
            return $value;
        }
        return json_encode($value, $asPretty ? JSON_PRETTY_PRINT : null);
    }

    /**
     * Decodes a String if it is not already decoded
     * @param mixed   $value
     * @param boolean $asArray Optional.
     * @return string
     */
    public static function decode($value, $asArray = false) {
        if (!self::is($value)) {
            return $value;
        }
        return json_decode($value, $asArray);
    }



    /**
     * Converts an encoded JSON into a Coma Separated Value
     * @param string $value
     * @return string
     */
    public static function toCSV($value) {
        $value = self::decode($value);
        return Strings::join($value, ", ");
    }

    /**
     * Converts a Coma Separated Value into an encoded JSON
     * @param string $value
     * @return string
     */
    public static function fromCSV($value) {
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
     * @return object
     */
    public static function read($path) {
        if (File::exists($path)) {
            return self::decode(file_get_contents($path), true);
        }
        return new stdClass();
    }

    /**
     * Writes a JSON File
     * @param string $file
     * @param mixed  $contents
     * @return void
     */
    public static function write($file, $contents) {
        $value = Arrays::toArray($contents);
        file_put_contents($path, self::encode($value, true));
    }
}
