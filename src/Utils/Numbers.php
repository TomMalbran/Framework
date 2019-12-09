<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * Several Numbers Utils
 */
class Numbers {

    /**
     * Returns true if the given value is a number and greater and/or equal to cero
     * @param mixed   $number
     * @param integer $min    Optional.
     * @param integer $max    Optional.
     * @return boolean
     */
    public static function isValid($number, int $min = 1, int $max = null): bool {
        return is_numeric($number) && $number >= $min && ($max != null ? $number <= $max : true);
    }

    /**
     * Rounds the given number to the given decimals
     * @param float   $number
     * @param integer $decimals
     * @return integer
     */
    public static function round(float $number, int $decimals): int {
        if (is_numeric($number)) {
            $padding = pow(10, $decimals);
            return ceil($number * $padding) / $padding;
        }
        return 0;
    }

    /**
     * Returns the given number as an integer using the given decimals
     * @param float   $number
     * @param integer $decimals
     * @return integer
     */
    public static function toInt(float $number, int $decimals): int {
        if (is_numeric($number)) {
            $padding = pow(10, $decimals);
            return round($number * $padding);
        }
        return 0;
    }

    /**
     * Returns the given number as a float using the given decimals
     * @param integer $number
     * @param integer $decimals
     * @return float
     */
    public static function toFloat(int $number, int $decimals): float {
        $padding = pow(10, $decimals);
        return $number / $padding;
    }



    /**
     * Maps a value that is in the from range to the to range
     * @param integer $value
     * @param integer $fromLow
     * @param integer $fromHigh
     * @param integer $toLow
     * @param integer $toHigh
     * @return integer
     */
    public static function map(int $value, int $fromLow, int $fromHigh, int $toLow, int $toHigh): int {
        $fromRange = $fromHigh - $fromLow;
        $toRange   = $toHigh - $toLow;
        if ($fromRange == 0) {
            return $toLow;
        }
        $scaleFactor = $toRange / $fromRange;

        // Re-zero the value within the from range
        $tmpValue = $value - $fromLow;
        // Rescale the value to the to range
        $tmpValue *= $scaleFactor;
        // Re-zero back to the to range
        return $tmpValue + $toLow;
    }

    /**
     * Returns a percent from the given values
     * @param integer $number
     * @param integer $total
     * @param integer $decimals Optional.
     * @return integer
     */
    public static function percent(int $number, int $total, int $decimals = 0): int {
        return $total == 0 ? 0 : self::round($number * 100 / $total, $decimals);
    }

    /**
     * Returns a division from the given values
     * @param integer $numerator
     * @param integer $divisor
     * @param integer $decimals  Optional.
     * @return integer
     */
    public static function divide(int $numerator, int $divisor, int $decimals = 0): int {
        return $divisor == 0 ? 0 : self::round($numerator / $divisor, $decimals);
    }



    /**
     * Returns true if the given price is valid
     * @param mixed   $price
     * @param integer $min   Optional.
     * @param integer $max   Optional.
     * @return boolean
     */
    public static function isValidPrice($price, int $min = 1, int $max = null): bool {
        return self::isValid($price * 100, $min, $max);
    }
    
    /**
     * Rounds the given price to 2 decimals
     * @param float $price
     * @return integer
     */
    public static function roundCents(float $price): int {
        return self::round($price, 2);
    }
    
    /**
     * Returns the given price in Cents
     * @param float $price
     * @return integer
     */
    public static function toCents(float $price): int {
        return self::toInt($price, 2);
    }
    
    /**
     * Returns the given price in Dollars
     * @param integer $price
     * @return float
     */
    public static function fromCents(int $price): float {
        return self::toFloat($price, 2);
    }

    /**
     * Returns a price using the right format
     * @param float   $price
     * @param integer $decimals Optional.
     * @return string
     */
    public static function formatPrice(float $price, int $decimals = 2): string {
        $price = floatval($price);
        if (!empty($price)) {
            return number_format($price, $decimals, ",", "");
        }
        return "";
    }

    /**
     * Returns a price using the right format
     * @param integer $cents
     * @param integer $decimals Optional.
     * @return string
     */
    public static function formatCents(int $cents, int $decimals = 2): string {
        $price = self::fromCents($cents);
        return self::formatPrice($price, $decimals);
    }

    /**
     * Returns a price string
     * @param float $price
     * @return string
     */
    public static function toPriceString(float $price): string {
        $millions = round($price / 1000000);
        if ($millions > 10) {
            return "${$millions}m";
        }
        $kilos = round($price / 1000);
        if ($kilos > 10) {
            return "${$kilos}k";
        }
        $price = round($price);
        return "${$price}";
    }
}
