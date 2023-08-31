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
     * @param mixed $value
     * @return boolean
     */
    public static function isValid(mixed $value): bool {
        if (Strings::isString($value)) {
            json_decode($value);
            return json_last_error() == JSON_ERROR_NONE;
        }
        return false;
    }

    /**
     * Encodes an Object as a string if it is not already encoded
     * @param mixed   $value
     * @param boolean $asPretty Optional.
     * @return string
     */
    public static function encode(mixed $value, bool $asPretty = false): string {
        if (self::isValid($value)) {
            return $value;
        }
        return json_encode($value, $asPretty ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * Decodes a String if it is not already decoded
     * @param mixed   $value
     * @param boolean $asArray Optional.
     * @return object|array
     */
    public static function decode(mixed $value, bool $asArray = false): mixed {
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
     * Creates the read response
     * @param boolean $asArray Optional.
     * @return object|array
     */
    private static function createResponse(bool $asArray = false): mixed {
        return $asArray ? [] : new stdClass();
    }

    /**
     * Reads a JSON file
     * @param string  $path
     * @param boolean $asArray Optional.
     * @return object|array
     */
    public static function readFile(string $path, bool $asArray = false): mixed {
        if (!File::exists($path)) {
            return self::createResponse($asArray);
        }
        $response = file_get_contents($path);
        if (empty($response)) {
            return self::createResponse($asArray);
        }
        return self::decode($response, $asArray);
    }

    /**
     * Reads a JSON url
     * @param string  $url
     * @param boolean $asArray Optional.
     * @return object|array
     */
    public static function readUrl(string $url, bool $asArray = false): mixed {
        $response = file_get_contents($url);
        if (empty($response)) {
            return self::createResponse($asArray);
        }
        return self::decode($response, $asArray);
    }

    /**
     * Posts to a JSON url
     * @param string  $url
     * @param array{} $data
     * @param boolean $asArray Optional.
     * @return object|array
     */
    public static function postUrl(string $url, array $data, bool $asArray = false): mixed {
        $options = [
            "http" => [
                "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
                "method"  => "POST",
                "content" => http_build_query($data),
            ],
        ];
        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if (empty($response)) {
            return self::createResponse($asArray);
        }
        return self::decode($response, $asArray);
    }

    /**
     * Writes a JSON File
     * @param string $path
     * @param mixed  $contents
     * @return boolean
     */
    public static function writeFile(string $path, mixed $contents): bool {
        if (!File::exists($path)) {
            return false;
        }
        $value = Arrays::toArray($contents);
        return File::write($path, self::encode($value, true));
    }
}
