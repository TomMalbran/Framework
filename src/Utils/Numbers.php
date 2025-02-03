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
     * @param mixed        $number
     * @param integer|null $min    Optional.
     * @param integer|null $max    Optional.
     * @return boolean
     */
    public static function isValid(mixed $number, ?int $min = 1, ?int $max = null): bool {
        if (!is_numeric($number)) {
            return false;
        }
        if ($min !== null && $number < $min) {
            return false;
        }
        if ($max !== null && $number > $max) {
            return false;
        }
        return true;
    }

    /**
     * Returns < 0 if number is less than other; > 0 if number is greater than other, and 0 if they are equal
     * @param integer|float $number
     * @param integer|float $other
     * @param boolean       $orderAsc Optional.
     * @return integer|float
     */
    public static function compare(int|float $number, int|float $other, bool $orderAsc = true): int|float {
        return ($number - $other) * ($orderAsc ? 1 : -1);
    }

    /**
     * Rounds the given number to the given decimals
     * @param integer|float $number
     * @param integer       $decimals
     * @return integer|float
     */
    public static function round(int|float $number, int $decimals): int|float {
        if (is_numeric($number)) {
            $padding = pow(10, $decimals);
            return round($number * $padding) / $padding;
        }
        return 0;
    }

    /**
     * Returns a number with the given length
     * @param integer $length Optional.
     * @return integer
     */
    public static function random(int $length = 8): int {
        return (int)rand(pow(10, $length - 1), pow(10, $length) - 1);
    }

    /**
     * Returns the given number as an integer using the given decimals
     * @param integer|float $number
     * @param integer       $decimals
     * @return integer
     */
    public static function toInt(int|float $number, int $decimals): int {
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
     * @param integer|float $number
     * @param integer       $decimals
     * @param integer       $maxForDecimals Optional.
     * @param string        $default        Optional.
     * @return string
     */
    public static function formatInt(int|float $number, int $decimals = 0, int $maxForDecimals = 1000, string $default = ""): string {
        $float = $decimals > 0 ? self::toFloat($number, $decimals) : $number;
        return self::formatFloat($float, $decimals, $maxForDecimals, $default);
    }

    /**
     * Returns a number using the right format
     * @param integer|float $number
     * @param integer       $decimals
     * @param integer       $maxForDecimals Optional.
     * @param string        $default        Optional.
     * @return string
     */
    public static function formatFloat(int|float $number, int $decimals, int $maxForDecimals = 1000, string $default = ""): string {
        $float = floatval($number);
        if (!empty($float)) {
            $decimals = (empty($maxForDecimals) || $float < $maxForDecimals) && !is_int($number) ? $decimals : 0;
            return number_format($float, $decimals, ",", ".");
        }
        return $default;
    }



    /**
     * Clamps the given number between the min and max
     * @param integer|float $number
     * @param integer|float $min
     * @param integer|float $max
     * @return integer|float
     */
    public static function clamp(int|float $number, int|float $min, int|float $max): int|float {
        return max($min, min($max, $number));
    }

    /**
     * Maps the given number that is in the from range to the to range
     * @param integer|float $number
     * @param integer|float $fromLow
     * @param integer|float $fromHigh
     * @param integer|float $toLow
     * @param integer|float $toHigh
     * @return integer|float
     */
    public static function map(int|float $number, int|float $fromLow, int|float $fromHigh, int|float $toLow, int|float $toHigh): int|float {
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
     * @param integer|float $number
     * @param integer|float $total
     * @param integer       $decimals Optional.
     * @return integer|float
     */
    public static function percent(int|float $number, int|float $total, int $decimals = 0): int|float {
        return $total == 0 ? 0 : self::round($number * 100 / $total, $decimals);
    }

    /**
     * Returns a division from the given values
     * @param integer|float $numerator
     * @param integer|float $divisor
     * @param integer       $decimals  Optional.
     * @return integer|float
     */
    public static function divide(int|float $numerator, int|float $divisor, int $decimals = 0): int|float {
        return $divisor == 0 ? 0 : self::round($numerator / $divisor, $decimals);
    }

    /**
     * Applies the Discount to the given Number
     * @param integer|float $number
     * @param integer|float $percent
     * @return integer|float
     */
    public static function applyDiscount(int|float $number, int|float $percent): int|float {
        if (empty($percent)) {
            return $number;
        }
        $discount = (100 - min(100, $percent)) / 100;
        return $number * $discount;
    }

    /**
     * Applies the Increment to the given Number
     * @param integer|float $number
     * @param integer|float $percent
     * @return integer|float
     */
    public static function applyIncrement(int|float $number, int|float $percent): int|float {
        if (empty($percent)) {
            return $number;
        }
        $percent   = self::clamp($percent, 0, 100);
        $increment = $percent / (100 - $percent);
        return $number + $number * $increment;
    }

    /**
     * Returns the Greatest Common Divisor
     * @param integer|float $a
     * @param integer|float $b
     * @return integer|float
     */
    public static function getCommonDivisor(int|float $a, int|float $b): int|float {
        while ($b != 0) {
            $m = $a % $b;
            $a = $b;
            $b = $m;
        }
        return $a;
    }



    /**
     * Returns true if the given price is valid
     * @param integer|float $number
     * @param integer|null  $min      Optional.
     * @param integer|null  $max      Optional.
     * @param integer|null  $decimals Optional.
     * @return boolean
     */
    public static function isValidFloat(int|float $number, ?int $min = 1, ?int $max = null, ?int $decimals = null): bool {
        $mult = 1;
        if ($decimals != null) {
            $decimalCount = strlen($number) - strrpos($number, ".") - 1;
            if (strrpos($number, ".") > 0 && $decimalCount > $decimals) {
                return false;
            }
            $mult = pow(10, $decimals);
        }
        $multMin = $min !== null ? $min * $mult : $min;
        $multMax = $max !== null ? $max * $mult : $max;
        return self::isValid($number * $mult, $multMin, $multMax);
    }

    /**
     * Returns true if the given price is valid
     * @param integer|float $price
     * @param integer|null  $min   Optional.
     * @param integer|null  $max   Optional.
     * @return boolean
     */
    public static function isValidPrice(int|float $price, ?int $min = 1, ?int $max = null): bool {
        return self::isValidFloat($price, $min, $max, 2);
    }

    /**
     * Rounds the given price to 2 decimals
     * @param integer|float $price
     * @return integer|float
     */
    public static function roundCents(int|float $price): int|float {
        return self::round($price, 2);
    }

    /**
     * Returns the given price in Cents
     * @param integer|float $price
     * @return integer
     */
    public static function toCents(int|float $price): int {
        return self::toInt($price, 2);
    }

    /**
     * Returns the given price in Dollars
     * @param integer|float $price
     * @return float
     */
    public static function fromCents(int|float $price): float {
        return self::toFloat($price, 2);
    }

    /**
     * Returns a price using the right format
     * @param integer|float $price
     * @param integer       $decimals       Optional.
     * @param integer       $maxForDecimals Optional.
     * @param string        $default        Optional.
     * @return string
     */
    public static function formatPrice(int|float $price, int $decimals = 2, int $maxForDecimals = 1000, string $default = "0"): string {
        return self::formatFloat($price, $decimals, $maxForDecimals, $default);
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
     * @param integer|float $price
     * @return string
     */
    public static function toPriceString(int|float $price): string {
        $millions = round($price / 1000000);
        if ($millions > 10) {
            return "\${$millions}m";
        }
        $kilos = round($price / 1000);
        if ($kilos > 10) {
            return "\${$kilos}k";
        }
        $price = round($price);
        return "\${$price}";
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
     * @param integer|float $value
     * @param integer       $amount
     * @return string
     */
    public static function zerosPad(int|float $value, int $amount): string {
        if (!empty($value)) {
            return str_pad((string)$value, $amount, "0", STR_PAD_LEFT);
        }
        return $value;
    }



    /**
     * Calculates the distance between two coordinates
     * @param float $fromLatitude
     * @param float $fromLongitude
     * @param float $toLatitude
     * @param float $toLongitude
     * @return float
     */
    public static function coordinatesDistance(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float {
        $fromLatitude = deg2rad($fromLatitude);
        $toLatitude   = deg2rad($toLatitude);
        $angle        = deg2rad($fromLongitude - $toLongitude);

        $distanceA    = sin($fromLatitude) * sin($toLatitude);
        $distanceB    = cos($fromLatitude) * cos($toLatitude);
        $distance     = $distanceA + $distanceB * cos($angle);

        $result       = acos($distance);
        $result       = rad2deg($result);
        $result       = $result * 60 * 1.1515 * 1.609344;
        return $result;
    }

    /**
     * Calculates the Expression
     * @param string $expression
     * @return integer|float
     */
    public static function calcExpression(string $expression): int|float {
        // Sanitize the input
        $expression = preg_replace("/[^0-9.,+\-*\/()%]/", "", $expression);

        // Convert percentages to decimal
        $expression = preg_replace("/([+-])([0-9]{1})(%)/", "*(1\$1.0\$2)", $expression);
        $expression = preg_replace("/([+-])([0-9]+)(%)/", "*(1\$1.\$2)", $expression);
        $expression = preg_replace("/([0-9]{1})(%)/", ".0\$1", $expression);
        $expression = preg_replace("/([0-9]+)(%)/", ".\$1", $expression);

        // Fix some errors
        $expression = preg_replace("/,/", ".", $expression);
        $expression = preg_replace("/\.+/", ".", $expression);
        $expression = preg_replace("/\(\)/", "", $expression);
        $expression = preg_replace("/\+\-/", "-", $expression);
        $expression = preg_replace("/--/", "+", $expression);
        $expression = preg_replace("/([+\-*\/])[+\-*\/]+/", "$1", $expression);

        // Calculate
        if (empty($expression)) {
            return 0;
        }
        try {
            return @eval("return $expression;");
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
