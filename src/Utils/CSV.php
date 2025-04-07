<?php
namespace Framework\Utils;

use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The CSV Utils
 */
class CSV {

    /**
     * Converts an array or string to a CSV string
     * @param string[]|string $value
     * @param string          $separator Optional.
     * @return string
     */
    public static function encode(array|string $value, string $separator = ","): string {
        if (is_array($value)) {
            $parts = Arrays::removeEmpty($value);
            return Strings::join($parts, $separator);
        }

        $parts = Strings::split($value, $separator);
        $parts = Arrays::removeEmpty($parts);
        return Strings::join($parts, $separator);
    }

    /**
     * Converts an array or string to a CSV array
     * @param string[]|string $value
     * @param string          $separator Optional.
     * @param string[]        $fields    Optional.
     * @return string[]|string[][]
     */
    public static function decode(array|string $value, string $separator = ",", array $fields = []): array {
        if (is_array($value)) {
            return self::parseLine($value, $fields);
        }

        if (!Strings::contains($value, "\n")) {
            $csv = str_getcsv($value, $separator);
            $csv = Arrays::toStrings($csv);
            return self::parseLine($csv, $fields);
        }

        return self::decodeFile($value, $separator, $fields);
    }

    /**
     * Decodes a CSV File
     * @param string   $value
     * @param string   $separator Optional.
     * @param string[] $fields    Optional.
     * @return string[][]
     */
    public static function decodeFile(string $value, string $separator = ",", array $fields = []): array {
        $lines  = Strings::split($value, "\n");
        $result = [];
        foreach ($lines as $line) {
            if (trim($line) !== "") {
                $csv = str_getcsv($line, $separator);
                $csv = Arrays::toStrings($csv);
                $result[] = self::parseLine($csv, $fields);
            }
        }
        return $result;
    }

    /**
     * Parses a CSV Line
     * @param string[] $value
     * @param string[] $fields
     * @return string[]
     */
    private static function parseLine(array $value, array $fields): mixed {
        if (count($fields) === 0) {
            return $value;
        }

        $result = [];
        foreach ($fields as $index => $field) {
            $result[$field] = $value[$index] ?? "";
        }
        return $result;
    }



    /**
     * Reads a CSV file
     * @param string  $path
     * @param string  $separator  Optional.
     * @param boolean $skipHeader Optional.
     * @return mixed[]
     */
    public static function readFile(string $path, string $separator = ",", bool $skipHeader = false): array {
        $content = File::read($path);
        if ($content === "") {
            return [];
        }

        $lines  = Strings::split($content, "\n", true);
        $result = [];
        foreach ($lines as $index => $line) {
            if ($line !== "" && ($index > 0 || !$skipHeader)) {
                $result[] = self::decode($line, $separator);
            }
        }
        return $result;
    }

    /**
     * Writes a CSV File
     * @param string   $path
     * @param string[] $contents
     * @param string   $separator Optional.
     * @return boolean
     */
    public static function writeFile(string $path, array $contents, string $separator = ","): bool {
        if (!File::exists($path)) {
            return false;
        }

        $lines = [];
        foreach ($contents as $row) {
            $lines[] = self::encode($row, $separator);
        }
        return File::write($path, Strings::join($lines, "\n"));
    }
}
