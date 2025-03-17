<?php
namespace Framework\Utils;

use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

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
        $result = json_encode($value, $asPretty ? JSON_PRETTY_PRINT : 0);
        return $result === false ? $value : $result;
    }

    /**
     * Decodes a String if it is not already decoded
     * @param mixed $value
     * @return array<string|integer,mixed>
     */
    public static function decodeAsArray(mixed $value): array {
        if (!self::isValid($value)) {
            return Arrays::isArray($value) ? $value : [];
        }
        return (array)json_decode($value, true);
    }

    /**
     * Decodes a String if it is not already decoded
     * @param mixed $value
     * @return Dictionary
     */
    public static function decodeAsDictionary(mixed $value): Dictionary {
        $result = self::decodeAsArray($value);
        return new Dictionary($result);
    }

    /**
     * Decodes a String as a list of Strings
     * @param mixed   $value
     * @param boolean $withoutEmpty Optional.
     * @return string[]
     */
    public static function decodeAsStrings(mixed $value, bool $withoutEmpty = false): array {
        $result = self::decodeAsArray($value);
        return Arrays::toStrings($result, withoutEmpty: $withoutEmpty);
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
     * @param string|integer ...$pathParts
     * @return array<string|integer,mixed>
     */
    public static function readFile(string|int ...$pathParts): array {
        $response = File::read(...$pathParts);
        if (empty($response)) {
            return [];
        }
        return self::decodeAsArray($response);
    }

    /**
     * Reads a JSON url
     * @param string $url
     * @return array<string|integer,mixed>
     */
    public static function readUrl(string $url): array {
        $response = File::readUrl($url);
        if (empty($response)) {
            return [];
        }
        return self::decodeAsArray($response);
    }

    /**
     * Posts to a JSON url
     * @param string                      $url
     * @param array<string|integer,mixed> $data
     * @return array<string|integer,mixed>
     */
    public static function postUrl(string $url, array $data): array {
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
            return [];
        }
        return self::decodeAsArray($response);
    }

    /**
     * Writes a JSON File
     * @param string $path
     * @param mixed  $contents
     * @return boolean
     */
    public static function writeFile(string $path, mixed $contents): bool {
        $value = Arrays::toArray($contents);
        return File::write($path, self::encode($value, true));
    }
}
