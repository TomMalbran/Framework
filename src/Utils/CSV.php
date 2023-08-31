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
        if (Arrays::isArray($value)) {
            $parts = Arrays::removeEmpty($value);
            return Strings::join($parts, $separator);
        }
        if (Strings::isString($value)) {
            $parts = Strings::split($value, $separator);
            $parts = Arrays::removeEmpty($parts);
            return Strings::join($parts, $separator);
        }
        return "";
    }

    /**
     * Converts an array or string to a CSV array
     * @param string[]|string $value
     * @param string          $separator Optional.
     * @param string[]        $fields    Optional.
     * @param boolean         $asArray   Optional.
     * @return mixed[]
     */
    public static function decode(array|string $value, string $separator = ",", array $fields = [], bool $asArray = false): array {
        if (Arrays::isArray($value)) {
            return self::parseLine($value, $fields, $asArray);
        }
        if (!Strings::isString($value)) {
            return [];
        }

        if (!Strings::contains($value, "\n")) {
            $csv = str_getcsv($value, $separator);
            return self::parseLine($csv, $fields, $asArray);
        }

        $lines  = Strings::split($value, "\n");
        $result = [];
        foreach ($lines as $line) {
            if (!empty($line)) {
                $csv = str_getcsv($line, $separator);
                if ($csv[0] !== null) {
                    $result[] = self::parseLine($csv, $fields, $asArray);;
                }
            }
        }
        return $result;
    }

    /**
     * Parses a CSV Line
     * @param string[] $value
     * @param string[] $fields
     * @param boolean  $asArray Optional.
     * @return mixed
     */
    private static function parseLine(array $value, array $fields, bool $asArray = false): mixed {
        if (empty($fields)) {
            return $value;
        }

        $result = [];
        foreach ($fields as $index => $field) {
            $result[$field] = isset($value[$index]) ? $value[$index] : "";
        }
        return $asArray ? $result : (object)$result;
    }



    /**
     * Reads a CSV file
     * @param string  $path
     * @param string  $separator  Optional.
     * @param boolean $skipHeader Optional.
     * @return mixed[]
     */
    public static function readFile(string $path, string $separator = ",", bool $skipHeader = false): array {
        if (!File::exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $lines   = Strings::split($content, "\n", true);
        $result  = [];
        foreach ($lines as $index => $line) {
            if (!empty($line) && ($index > 0 || !$skipHeader)) {
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
