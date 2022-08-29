<?php
namespace Framework\Utils;

/**
 * Several Numbers Utils
 */
class Numbers {

    /**
     * Returns the length og the given Number
     * @param integer $number
     * @return integer
     */
    public static function length(int $number): int {
        return strlen((string)$number);
    }

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
     * Returns < 0 if number is less than other; > 0 if number is greater than other, and 0 if they are equal
     * @param mixed   $number
     * @param mixed   $other
     * @param boolean $orderAsc Optional.
     * @return integer
     */
    public static function compare($number, $other, bool $orderAsc = true): int {
        return ($number - $other) * ($orderAsc ? 1 : -1);
    }

    /**
     * Rounds the given number to the given decimals
     * @param float   $number
     * @param integer $decimals
     * @return float|integer
     */
    public static function round(float $number, int $decimals) {
        if (is_numeric($number)) {
            $padding = pow(10, $decimals);
            return round($number * $padding) / $padding;
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
            return (int)round($number * $padding);
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
     * Returns a number using the right format
     * @param float   $number
     * @param integer $decimals
     * @return string
     */
    public static function formatFloat(float $number, int $decimals): string {
        $float = floatval($number);
        if (!empty($float)) {
            $decimals = $float < 1000 && !is_int($number) ? $decimals : 0;
            return number_format($float, $decimals, ",", ".");
        }
        return "";
    }



    /**
     * Clamps the given number between the min and max
     * @param integer $number
     * @param integer $min
     * @param integer $max
     * @return integer
     */
    public function clamp(int $number, int $min, int $max) {
        return max($min, min($max, $number));
    }

    /**
     * Maps the given number that is in the from range to the to range
     * @param integer $number
     * @param integer $fromLow
     * @param integer $fromHigh
     * @param integer $toLow
     * @param integer $toHigh
     * @return integer
     */
    public static function map(int $number, int $fromLow, int $fromHigh, int $toLow, int $toHigh): int {
        $fromRange = $fromHigh - $fromLow;
        $toRange   = $toHigh - $toLow;
        if ($fromRange == 0) {
            return $toLow;
        }
        $scaleFactor = $toRange / $fromRange;

        // Re-zero the value within the from range
        $tmpValue = $number - $fromLow;
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
     * @return float
     */
    public static function roundCents(float $price): float {
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
        return self::formatFloat($price, $decimals);
    }

    /**
     * Returns a price using the right format
     * @param integer $cents
     * @param integer $decimals Optional.
     * @return string
     */
    public static function formatCents(int $cents, int $decimals = 2): string {
        $price = self::fromCents($cents);
        return self::formatFloat($price, $decimals);
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

    /**
     * Returns the memory in MB or GB with the units
     * @param integer $bytes
     * @param boolean $inGigas Optional.
     * @return string
     */
    public static function toBytesString(int $bytes, bool $inGigas = false): string {
        $megaBytes = $inGigas ? $bytes * 1024 : $bytes;
        $teraBytes = floor($megaBytes / (1024 * 1024));
        if ($teraBytes >= 1) {
            return "$teraBytes TB";
        }
        $gigaBytes = floor($megaBytes / 1024);
        if ($inGigas || $gigaBytes >= 1) {
            return "$gigaBytes GB";
        }
        return "$megaBytes MB";
    }

    /**
     * Adds zeros to the start of the number
     * @param mixed   $value
     * @param integer $amount
     * @return string
     */
    public static function zerosPad($value, int $amount): string {
        if (!empty($value)) {
            return str_pad((string)$value, $amount, "0", STR_PAD_LEFT);
        }
        return $value;
    }
}
