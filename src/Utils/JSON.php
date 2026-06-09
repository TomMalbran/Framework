<?php
namespace Framework\Utils;

use Framework\File\Storage;
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
     * @return bool
     */
    public static function isValid(mixed $value): bool {
        if (is_string($value) && !is_numeric($value)) {
            json_decode($value);
            return json_last_error() === JSON_ERROR_NONE;
        }
        return false;
    }

    /**
     * Encodes an Object as a string if it is not already encoded
     * @param mixed $value
     * @param bool  $asPretty Optional.
     * @return string
     */
    public static function encode(mixed $value, bool $asPretty = false): string {
        if (is_null($value)) {
            return "";
        }
        if (is_string($value)) {
            return $value;
        }

        $options = JSON_UNESCAPED_SLASHES;
        if ($asPretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        $result = json_encode($value, $options);
        return is_string($result) ? $result : "";
    }

    /**
     * Decodes a String without checks
     * @param string $value
     * @return mixed
     */
    public static function decode(string $value): mixed {
        return json_decode($value, associative: true);
    }

    /**
     * Decodes a String if it is not already decoded
     * @param mixed $value
     * @return array<int|string,mixed>
     */
    public static function decodeAsArray(mixed $value): array {
        if (!is_string($value) || !self::isValid($value)) {
            return is_array($value) ? $value : [];
        }
        return (array)self::decode($value);
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
     * @param mixed $value
     * @param bool  $withoutEmpty Optional.
     * @return list<string>
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
        $content = Strings::split($value, ",", trim: true, skipEmpty: true);
        return self::encode($content);
    }



    /**
     * Reads a JSON file
     * @param int|string ...$pathParts
     * @return array<int|string,mixed>
     */
    public static function readFile(int|string ...$pathParts): array {
        $response = Storage::readFile(...$pathParts);
        if ($response === "") {
            return [];
        }
        return self::decodeAsArray($response);
    }

    /**
     * Reads a JSON url
     * @param string $url
     * @return array<int|string,mixed>
     */
    public static function readUrl(string $url): array {
        $response = Storage::readUrl($url);
        if ($response === "") {
            return [];
        }
        return self::decodeAsArray($response);
    }

    /**
     * Posts to a JSON url
     * @param string                  $url
     * @param array<int|string,mixed> $data
     * @return array<int|string,mixed>
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
        $response = file_get_contents($url, context: $context);
        if ($response === false || $response === "") {
            return [];
        }
        return self::decodeAsArray($response);
    }

    /**
     * Writes a JSON File
     * @param string $path
     * @param mixed  $contents
     * @return bool
     */
    public static function writeFile(string $path, mixed $contents): bool {
        $value = Arrays::toArray($contents);
        return Storage::writeFile($path, self::encode($value, asPretty: true));
    }
}
