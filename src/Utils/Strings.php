<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

/**
 * Several String Utils
 */
class Strings {

    /**
     * Returns true if the given value is a string
     * @param mixed $string
     * @return boolean
     */
    public static function isString(mixed $string): bool {
        return is_string($string);
    }

    /**
     * Returns the length og the given String
     * @param string $string
     * @return integer
     */
    public static function length(string $string): int {
        return strlen($string);
    }

    /**
     * Returns true if the given String contains all of the given Needles
     * @param string $string
     * @param string ...$needles
     * @return boolean
     */
    public static function contains(string $string, string ...$needles): bool {
        foreach ($needles as $needle) {
            if (strstr($string, $needle) !== FALSE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the given String contains all of the given Needles in Case Insensitive
     * @param string $string
     * @param string ...$needles
     * @return boolean
     */
    public static function containsCaseInsensitive(string $string, string ...$needles): bool {
        $string = strtolower($string);
        foreach ($needles as $needle) {
            if (strstr($string, strtolower($needle)) !== FALSE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the given String starts with the given Needle
     * @param string $string
     * @param string $needle
     * @return boolean
     */
    public static function startsWith(string $string, string $needle): bool {
        $length = strlen($needle);
        return substr($string, 0, $length) === $needle;
    }

    /**
     * Returns true if the given String ends with the given Needle
     * @param string $string
     * @param string $needle
     * @return boolean
     */
    public static function endsWith(string $string, string $needle): bool {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return substr($string, -$length) === $needle;
    }

    /**
     * Returns true if the given String matches the given Pattern
     * @param string $string
     * @param string $pattern
     * @return boolean
     */
    public static function match(string $string, string $pattern): bool {
        return preg_match($pattern, $string);
    }

    /**
     * Returns all the matches for the Pattern in the given String as an array
     * @param string $string
     * @param string $pattern
     * @return mixed[]
     */
    public static function getAllMatches(string $string, string $pattern): array {
        preg_match_all($pattern, $string, $matches);
        if (empty($matches)) {
            return [];
        }
        if (count($matches) === 1 && !empty($matches[0])) {
            return $matches[0];
        }
        return $matches;
    }



    /**
     * Returns a char from an index
     * @param integer $index
     * @return string
     */
    public static function getLetter(int $index): string {
        return chr($index + 65);
    }

    /**
     * Returns a number from a letter
     * @param string $text
     * @return integer
     */
    public static function getNumber(string $text): int {
        $letter = strtoupper(trim($text));
        if (preg_match('/^[A-Z]$/', $letter)) {
            return ord($letter) - 64;
        }
        return 0;
    }

    /**
     * Repeats the given String the given times
     * @param string  $string
     * @param integer $times
     * @return string
     */
    public static function repeat(string $string, int $times): string {
        return str_repeat($string, $times);
    }

    /**
     * Returns a random String with the given length
     * @param integer $length Optional.
     * @return string
     */
    public static function random(int $length = 50): string {
        return substr(md5(rand()), 0, $length);
    }

    /**
     * Returns a random char from the given String
     * @param string $string
     * @return string
     */
    public static function randomChar(string $string): string {
        $parts = str_split($string);
        $index = array_rand($parts);
        return $string[$index];
    }

    /**
     * Generates a random String with the given options
     * @param integer $length        Optional.
     * @param string  $availableSets Optional.
     * @return string
     */
    public static function randomCode(int $length = 8, string $availableSets = "lud"): string {
        $sets   = [];
        $all    = "";
        $result = "";

        if (self::contains($availableSets, "a")) {
            $sets[] = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        } else {
            if (self::contains($availableSets, "l")) {
                $sets[] = "abcdefghijklmnopqrstuvwxyz";
            }
            if (self::contains($availableSets, "u")) {
                $sets[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            }
        }
        if (self::contains($availableSets, "d")) {
            $sets[] = "0123456789";
        }
        if (self::contains($availableSets, "s")) {
            $sets[] = "!@#$%&*?";
        }

        foreach ($sets as $set) {
            $result .= self::randomChar($set);
            $all    .= $set;
        }

        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++) {
            $result .= $all[array_rand($all)];
        }

        $result = str_shuffle($result);
        return $result;
    }



    /**
     * Replaces in the String the search with the replace
     * @param string               $string
     * @param string[]|string      $search
     * @param string[]|string|null $replace Optional.
     * @return string
     */
    public static function replace(string $string, array|string $search, array|string $replace = null): string {
        if ($replace === null && Arrays::isArray($search)) {
            return str_replace(array_keys($search), array_values($search), $string);
        }
        return str_replace($search, $replace, $string);
    }

    /**
     * Replaces in the start of the String the search with the replace
     * @param string $string
     * @param string $search
     * @param string $replace
     * @return string
     */
    public static function replaceStart(string $string, string $search, string $replace): string {
        if (self::startsWith($string, $search)) {
            return substr_replace($string, $replace, 0, strlen($replace));
        }
        return $string;
    }

    /**
     * Replaces in the String the pattern with the replace
     * @param string          $string
     * @param string[]|string $pattern
     * @param string[]|string $replace
     * @return string
     */
    public static function replacePattern(string $string, array|string $pattern, array|string $replace): string {
        $result = preg_replace($pattern, $replace, $string);
        return !empty($result) ? $result : "";
    }

    /**
     * Removes the Needle from the start of the String
     * @param string $string
     * @param string $needle
     * @return string
     */
    public static function stripStart(string $string, string $needle): string {
        if (self::startsWith($string, $needle)) {
            $length = strlen($needle);
            return substr($string, $length, strlen($string) - $length);
        }
        return $string;
    }

    /**
     * Removes the Needle from the end of the String
     * @param string $string
     * @param string $needle
     * @return string
     */
    public static function stripEnd(string $string, string $needle): string {
        if (self::endsWith($string, $needle)) {
            $length = strlen($needle);
            return substr($string, 0, strlen($string) - $length);
        }
        return $string;
    }

    /**
     * Removes the Needle from the start and end of the String
     * @param string $string
     * @param string $startNeedle
     * @param string $endNeedle
     * @return string
     */
    public static function stripStartEnd(string $string, string $startNeedle, string $endNeedle): string {
        $result = self::stripStart($string, $startNeedle);
        $result = self::stripEnd($result, $endNeedle);
        return $result;
    }

    /**
     * Pads left the String with the given string to the given length
     * @param string  $string
     * @param integer $length
     * @param string  $needle
     * @return string
     */
    public static function padLeft(string $string, int $length, string $needle = " "): string {
        return str_pad($string, $length, $needle, STR_PAD_LEFT);
    }

    /**
     * Pads right the String with the given string to the given length
     * @param string  $string
     * @param integer $length
     * @param string  $needle
     * @return string
     */
    public static function padRight(string $string, int $length, string $needle = " "): string {
        return str_pad($string, $length, $needle, STR_PAD_RIGHT);
    }

    /**
     * Adds the Needle to the start of the String if not there
     * @param string $string
     * @param string $needle
     * @return string
     */
    public static function addPrefix(string $string, string $needle): string {
        if (!self::startsWith($string, $needle)) {
            return $needle . $string;
        }
        return $string;
    }

    /**
     * Adds the Needle to the start of the String if not there
     * @param string $string
     * @param string $needle
     * @return string
     */
    public static function addSuffix(string $string, string $needle): string {
        if (!self::endsWith($string, $needle)) {
            return $string . $needle;
        }
        return $string;
    }



    /**
     * Returns a Substring from the Start to the Length
     * @param string       $string
     * @param integer      $start
     * @param integer|null $length Optional.
     * @return string
     */
    public static function substring(string $string, int $start, ?int $length = null): string {
        return substr($string, $start, $length);
    }

    /**
     * Returns a Substring from the Needle to the end
     * @param string  $string
     * @param string  $needle
     * @param boolean $useFirst Optional.
     * @return string
     */
    public static function substringAfter(string $string, string $needle, bool $useFirst = false): string {
        if (self::contains($string, $needle)) {
            $position = $useFirst ? strpos($string, $needle) : strrpos($string, $needle);
            return substr($string, $position + strlen($needle));
        }
        return $string;
    }

    /**
     * Returns a Substring from the start to the Needle
     * @param string  $string
     * @param string  $needle
     * @param boolean $useFirst Optional.
     * @return string
     */
    public static function substringBefore(string $string, string $needle, bool $useFirst = true): string {
        if (self::contains($string, $needle)) {
            $position = $useFirst ? strpos($string, $needle) : strrpos($string, $needle);
            return substr($string, 0, $position);
        }
        return $string;
    }

    /**
     * Returns a Substring between the From and To
     * @param string $string
     * @param string $from
     * @param string $to
     * @return string
     */
    public static function substringBetween(string $string, string $from, string $to): string {
        $result = self::substringAfter($string, $from);
        $result = self::substringBefore($result, $to);
        return $result;
    }



    /**
     * Splits the given String at the given Needle
     * @param string[]|string $string
     * @param string          $needle
     * @param boolean         $trim      Optional.
     * @param boolean         $skipEmpty Optional.
     * @return string[]
     */
    public static function split(array|string $string, string $needle, bool $trim = false, bool $skipEmpty = false): array {
        if (Arrays::isArray($string)) {
            return $string;
        }

        $content = !empty($string) ? explode($needle, $string) : [];
        if (!$trim) {
            return $content;
        }
        $parts  = self::split($content, ",");
        $result = [];
        foreach ($parts as $part) {
            if (!$skipEmpty || ($skipEmpty && !empty($part))) {
                $result[] = trim($part);
            }
        }
        return $result;
    }

    /**
     * Split the String into Words
     * @param string $string
     * @return string[]
     */
    public static function splitToWords(string $string): array {
        return preg_split('/(\.\.\.\s?|[-.?!,;:(){}\[\]\'"]\s?)|\s/', $string, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Joins the given Strings using the given glue
     * @param string[]|string $value
     * @param string          $glue      Optional.
     * @param boolean         $skipEmpty Optional.
     * @return string
     */
    public static function join(array|string $value, string $glue = "", bool $skipEmpty = false): string {
        if (!Arrays::isArray($value)) {
            return $value;
        }
        if ($skipEmpty) {
            $value = Arrays::removeEmpty($value);
        }
        return implode($glue, $value);
    }

    /**
     * Joins the given String keys using the given glue
     * @param string[]|string $value
     * @param string          $glue  Optional.
     * @return string
     */
    public static function joinKeys(array|string $value, string $glue = ""): string {
        if (!Arrays::isArray($value)) {
            return $value;
        }
        return implode($glue, array_keys($value));
    }

    /**
     * Joins the given String values using the given glue
     * @param string[]|string $value
     * @param string          $key
     * @param string          $glue  Optional.
     * @return string
     */
    public static function joinValues(array|string $value, string $key, string $glue = ""): string {
        if (!Arrays::isArray($value)) {
            return $value;
        }
        $value = Arrays::createArray($value, $key);
        return implode($glue, $value);
    }

    /**
     * Merges 2 strings with the given glue when possible
     * @param string $first
     * @param string $second
     * @param string $glue   Optional.
     * @return string
     */
    public static function merge(string $first, string $second, string $glue = " "): string {
        $result = "";
        if (!empty($first) && !empty($second)) {
            $result = "{$first}{$glue}{$second}";
        } elseif (!empty($first)) {
            $result = $first;
        } elseif (!empty($second)) {
            $result = $second;
        }
        return $result;
    }



    /**
     * Returns true if the values are Equal
     * @param mixed   $string
     * @param mixed   $other
     * @param boolean $caseInsensitive Optional.
     * @param boolean $trimValues      Optional.
     * @return boolean
     */
    public static function isEqual(mixed $string, mixed $other, bool $caseInsensitive = true, bool $trimValues = true): bool {
        if (!self::isString($string) || !self::isString($other)) {
            return $string == $other;
        }

        if ($caseInsensitive) {
            $string = strtolower($string);
            $other  = strtolower($other);
        }
        if ($trimValues) {
            $string = trim($string);
            $other  = trim($other);
        }
        return $string === $other;
    }

    /**
     * Returns < 0 if string is less than other; > 0 if string is greater than other, and 0 if they are equal
     * @param string  $string
     * @param string  $other
     * @param boolean $orderAsc        Optional.
     * @param boolean $caseInsensitive Optional.
     * @return integer
     */
    public static function compare(string $string, string $other, bool $orderAsc = true, bool $caseInsensitive = false): int {
        if ($caseInsensitive) {
            $result = strcasecmp($string, $other);
        } else {
            $result = strcmp($string, $other);
        }
        return $orderAsc ? $result : $result * -1;
    }

    /**
     * Transforms a String to LowerCase
     * @param string $string
     * @return string
     */
    public static function toLowerCase(string $string): string {
        return strtolower($string);
    }

    /**
     * Transforms a String to UpperCase
     * @param string $string
     * @return string
     */
    public static function toUpperCase(string $string): string {
        return strtoupper($string);
    }

    /**
     * Transforms the first Character to LowerCase
     * @param string $string
     * @return string
     */
    public static function lowerCaseFirst(string $string): string {
        return lcfirst($string);
    }

    /**
     * Transforms the first Character to UpperCase
     * @param string $string
     * @return string
     */
    public static function upperCaseFirst(string $string): string {
        return ucfirst($string);
    }

    /**
     * Transforms an UpperCase string with underscores to CamelCase
     * @param string  $string
     * @param boolean $capitalizeFirst Optional.
     * @return string
     */
    public static function upperCaseToCamelCase(string $string, bool $capitalizeFirst = false): string {
        $result = ucwords(strtolower($string), "_");
        $result = str_replace("_", "", $result);
        if (!$capitalizeFirst) {
            $result = lcfirst($result);
        }
        return $result;
    }

    /**
     * Transforms an CamelCase string to UpperCase with underscores
     * @param string $string
     * @return string
     */
    public static function camelCaseToUpperCase(string $string): string {
        $parts  = preg_split('/(?=[A-Z])/', $string);
        $result = implode("_", $parts);
        $result = strtoupper($result);
        return $result;
    }



    /**
     * Returns the HTML version of the given string
     * @param string $string
     * @return string
     */
    public static function toHtml(string $string): string {
        return str_replace("\n", "<br>", $string);
    }

    /**
     * Returns the given string without HTML
     * @param string $string
     * @return string
     */
    public static function removeHtml(string $string): string {
        return strip_tags($string);
    }

    /**
     * Returns a short version of the given string
     * @param string  $string
     * @param integer $length Optional.
     * @param boolean $asUtf8 Optional.
     * @return string
     */
    public static function makeShort(string $string, int $length = 30, bool $asUtf8 = true): string {
        $first = explode("\n", $string)[0];
        if ($asUtf8) {
            if (mb_strlen($first, "utf-8") > $length) {
                return mb_substr($first, 0, $length - 3, "utf-8") . "...";
            }
            return $first;
        }

        $count  = $length;
        $result = $first;
        while (strlen($result) > $length) {
            $result = mb_substr($result, 0, $count, "utf-8");
            $count -= 1;
        }
        return $result;
    }

    /**
     * Returns true if the short version is different from the string
     * @param string  $string
     * @param integer $length Optional.
     * @param boolean $asUtf8 Optional.
     * @return string
     */
    public static function isShort(string $string, int $length = 30, bool $asUtf8 = true): string {
        return self::makeShort($string, $length, $asUtf8) !== $string;
    }



    /**
     * Returns true if the given string is alpha-numeric
     * @param string       $string
     * @param boolean      $withDashes Optional.
     * @param integer|null $length     Optional.
     * @return boolean
     */
    public static function isAlphaNum(string $string, bool $withDashes = false, ?int $length = null): bool {
        if ($length !== null && strlen($string) != $length) {
            return false;
        }
        if ($withDashes) {
            $string = str_replace([ "-", "_" ], "", $string);
        }
        return ctype_alnum($string);
    }

    /**
     * Returns true if the given string is a valid slug
     * @param string $string
     * @return boolean
     */
    public static function isValidSlug(string $string): bool {
        return self::match($string, '/^[a-z0-9\-]+$/');
    }

    /**
     * Returns a Slug from the given string
     * @param string $string
     * @return string
     */
    public static function toSlug(string $string): string {
        $result = self::sanitize($string, true, true);
        $result = str_replace("---", "-", $result);
        $result = str_replace("--", "-", $result);
        return $result;
    }

    /**
     * Encodes the url
     * @param string $url
     * @return string
     */
    public static function encodeUrl(string $url): string {
        return str_replace(" ", "%20", $url);
    }

    /**
     * Sanitizes a String
     * @param string  $string
     * @param boolean $lowercase Optional.
     * @param boolean $anal      Optional.
     * @return string
     */
    public static function sanitize(string $string, bool $lowercase = true, bool $anal = false): string {
        $strip = [
            "~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
            "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
            "â€”", "â€“", ",", "<", ".", ">", "/", "?",
        ];
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);

        if ($anal) {
            $tilde = [ "á", "é", "í", "ó", "ú", "ü", "ñ", "Á", "É", "Í", "Ó", "Ú", "Ü", "Ñ" ];
            $with  = [ "a", "e", "i", "o", "u", "u", "n", "A", "E", "I", "O", "U", "U", "N" ];
            $clean = str_replace($tilde, $with, $clean);
            $clean = preg_replace("/[^a-zA-Z0-9\-]/", "", $clean);
        }
        if ($lowercase) {
            return function_exists("mb_strtolower") ? mb_strtolower($clean, "UTF-8") : strtolower($clean);
        }
        return $clean;
    }

    /**
     * Returns true if the given Text has an Emoji
     * @param string $string
     * @return boolean
     */
    public static function hasEmoji(string $string): bool {
        $emojis = '/[\x{0080}-\x{02AF}'
            . '\x{0300}-\x{03FF}'
            . '\x{0600}-\x{06FF}'
            . '\x{0C00}-\x{0C7F}'
            . '\x{1DC0}-\x{1DFF}'
            . '\x{1E00}-\x{1EFF}'
            . '\x{2000}-\x{209F}'
            . '\x{20D0}-\x{214F}'
            . '\x{2190}-\x{23FF}'
            . '\x{2460}-\x{25FF}'
            . '\x{2600}-\x{27EF}'
            . '\x{2900}-\x{29FF}'
            . '\x{2B00}-\x{2BFF}'
            . '\x{2C60}-\x{2C7F}'
            . '\x{2E00}-\x{2E7F}'
            . '\x{3000}-\x{303F}'
            . '\x{A490}-\x{A4CF}'
            . '\x{E000}-\x{F8FF}'
            . '\x{FE00}-\x{FE0F}'
            . '\x{FE30}-\x{FE4F}'
            . '\x{1F000}-\x{1F02F}'
            . '\x{1F0A0}-\x{1F0FF}'
            . '\x{1F100}-\x{1F64F}'
            . '\x{1F680}-\x{1F6FF}'
            . '\x{1F910}-\x{1F96B}'
            . '\x{1F980}-\x{1F9E0}]/u';

        preg_match($emojis, $string, $matches);
        return !empty($matches);
    }
}
