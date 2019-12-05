<?php
namespace Framework\Utils;

/**
 * Several String Utils
 */
class Strings {
    
    /**
     * Returns the length og the given String
     * @param string $string
     * @return integer
     */
    public static function length($string) {
        return strlen((string)$string);
    }

    /**
     * Returns true if the given String contains the given Needle
     * @param string $string
     * @param string $needle
     * @return boolean
     */
    public static function contains($string, $needle) {
        return strstr($string, $needle) !== FALSE;
    }

    /**
     * Returns true if the given String starts with the given Needle
     * @param string $string
     * @param string $needle
     * @return boolean
     */
    public static function startsWith($string, $needle) {
        $length = strlen($needle);
        return (substr($string, 0, $length) === $needle);
    }

    /**
     * Returns true if the given String ends with the given Needle
     * @param string $string
     * @param string $needle
     * @return boolean
     */
    public static function endsWith($string, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($string, -$length) === $needle);
    }



    /**
     * Replaces in the String the search with the replace
     * @param string          $string
     * @param string|string[] $search
     * @param string|string[] $replace
     * @return string
     */
    public static function replace($string, $search, $replace) {
        return str_replace($search, $replace, $string);
    }

    /**
     * Removes the Needle from the start of the String
     * @param string $string
     * @param string $needle
     * @return string
     */
    public static function stripStart($string, $needle) {
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
    public static function stripEnd($string, $needle) {
        if (self::endsWith($string, $needle)) {
            $length = strlen($needle);
            return substr($string, 0, strlen($string) - $length);
        }
        return $string;
    }

    
    
    /**
     * Returns a Substring from the Start to the Length
     * @param string  $string
     * @param integer $start
     * @param integer $length
     * @return string
     */
    public static function substring($string, $start, $length = null) {
        return substr($string, $start, $length);
    }

    /**
     * Returns a Substring from the Needle to the end
     * @param string $string
     * @param string $needle
     * @return string
     */
    public static function substringAfter($string, $needle) {
        if (self::contains($string, $needle)) {
            return substr($string, strrpos($string, $needle) + strlen($needle));
        }
        return $string;
    }

    /**
     * Returns a Substring from the start to the Needle
     * @param string $string
     * @param string $needle
     * @return string
     */
    public static function substringBefore($string, $needle) {
        if (self::contains($string, $needle)) {
            return substr($string, 0, strpos($string, $needle));
        }
        return $string;
    }


    /**
     * Splits the given String at the given Needle
     * @param string $string
     * @param string $needle
     * @return string[]
     */
    public static function split($string, $needle) {
        return explode($needle, $string);
    }

    /**
     * Jois the given Strings using the given glue
     * @param string[]|string $string
     * @param string          $glue
     * @return string
     */
    public static function join($string, $glue) {
        if (is_array($string)) {
            return implode($glue, $string);
        }
        return $string;
    }



    /**
     * Returns true if the values are Equal
     * @param string  $string
     * @param string  $other
     * @param boolean $asLower Optional.
     * @return boolean
     */
    public static function isEqual($string, $other, $asLower = true) {
        if ($asLower) {
            return strtolower($string) === strtolower($other);
        }
        return $string === $other;
    }

    /**
     * Transforms a String to Uppercase
     * @param string $string
     * @return string
     */
    public static function toLowerCase($string) {
        return strtolower($string);
    }

    /**
     * Transforms a String to Lowercase
     * @param string $string
     * @return string
     */
    public static function toUpperCase($string) {
        return strtoupper($string);
    }

    /**
     * Transforms an Uppercase string with underscores to Camelcase
     * @param string  $string
     * @param boolean $capitalizeFirst Optional.
     * @return string
     */
    public static function upperCaseToCamelCase($string, $capitalizeFirst = false) {
        $result = ucwords(strtolower($string), "_");
        $result = str_replace("_", "", $result);
        $result = lcfirst($result);
        return $result;
    }

    /**
     * Transforms an CamelCase string to UpperCase with underscores
     * @param string $string
     * @return string
     */
    public static function camelCaseToUpperCase($string) {
        $parts  = preg_split('/(?=[A-Z])/', $string);
        $result = implode("_", $parts);
        $result = strtoupper($result);
        return $result;
    }



    /**
     * Returns the HTML version of the given text
     * @param string $text
     * @return string
     */
    public static function toHtml($text) {
        return str_replace("\n", "<br>", $text);
    }

    /**
     * Returns a short version of the given text
     * @param string  $text
     * @param integer $len  Optional.
     * @return string
     */
    public static function makeShort($text, $len = 30) {
        $first = explode("\n", $text)[0];
        return strlen($first) > $len ? mb_substr($first, 0, $len, "utf-8") . "..." : $first;
    }

    /**
     * Returns true if the short version is different from the text
     * @param string  $text
     * @param integer $len  Optional.
     * @return string
     */
    public static function isShort($text, $len = 30) {
        return self::makeShort($text, $len) !== $text;
    }
}
