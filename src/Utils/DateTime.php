<?php
namespace Framework\Utils;

use Framework\Utils\Strings;
use Framework\Utils\Utils;

/**
 * Several Date Time functions
 */
class DateTime {

    public static $months     = [ "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre" ];

    public static $serverDiff = -180;
    public static $timeDiff   = 0;


    /**
     * Sets the Time Zone in minutes
     * @param integer $timezone
     * @return void
     */
    public static function setTimezone(int $timezone): void {
        self::$timeDiff = (self::$serverDiff - $timezone) * 60;
    }

    /**
     * Returns the given time using the given Time Zone
     * @param integer $value
     * @param integer $timezone
     * @return integer
     */
    public static function toTimezone(int $value, int $timezone) {
        if (!empty($value) && !empty($timezone)) {
            $timeDiff = (self::$serverDiff - $timezone) * 60;
            return $value - $timeDiff;
        }
        return $value;
    }

    /**
     * Returns the given time in the User Time Zone
     * @param integer $value
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toUserTime(int $value, bool $useTimezone = true): int {
        if (!empty($value) && $useTimezone) {
            return $value - self::$timeDiff;
        }
        return $value;
    }

    /**
     * Returns the given time in the Server Time Zone
     * @param integer $value
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toServerTime(int $value, bool $useTimezone = true): int {
        if (!empty($value) && $useTimezone) {
            return $value + self::$timeDiff;
        }
        return $value;
    }



    /**
     * Returns the Server Date
     * @return string
     */
    public static function getServerDate(): string {
        return date("d-m-Y @ H:i", time());
    }

    /**
     * Returns the User Date
     * @return string
     */
    public static function getUserDate(): string {
        return date("d-m-Y @ H:i", self::toUserTime(time()));
    }



    /**
     * Returns the given string as a time
     * @param string  $string
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toTime(string $string, bool $useTimezone = true): int {
        $result = strtotime($string);
        if ($result !== false) {
            return self::toServerTime($result, $useTimezone);
        }
        return 0;
    }

    /**
     * Returns the given string as a time
     * @param string  $dateString
     * @param string  $hourString
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toTimeHour(string $dateString, string $hourString, bool $useTimezone = true): int {
        $result = strtotime("$dateString $hourString");
        if ($result !== false) {
            return self::toServerTime($result, $useTimezone);
        }
        return 0;
    }

    /**
     * Returns the given string as a time
     * @param string  $string
     * @param string  $type        Optional.
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toDay(string $string, string $type = "start", bool $useTimezone = true): int {
        switch ($type) {
        case "start":
            return self::toDayStart($string, $useTimezone);
        case "end":
            return self::toDayEnd($string, $useTimezone);
        default:
            return self::toDayMiddle($string, $useTimezone);
        }
    }

    /**
     * Returns the given string as a time of the start of the day
     * @param string  $string
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toDayStart(string $string, bool $useTimezone = true): int {
        $result = strtotime($string);
        if ($result !== false) {
            return self::toServerTime($result, $useTimezone);
        }
        return 0;
    }

    /**
     * Returns the given string as a time of the middle of the day
     * @param string  $string
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toDayMiddle(string $string, bool $useTimezone = true): int {
        $result = strtotime($string);
        if ($result !== false) {
            $result += 12 * 3600;
            return self::toServerTime($result, $useTimezone);
        }
        return 0;
    }

    /**
     * Returns the given string as a time of the end of the day
     * @param string  $string
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toDayEnd(string $string, bool $useTimezone = true): int {
        $result = strtotime($string);
        if ($result !== false) {
            $result += 24 * 3600 - 1;
            return self::toServerTime($result, $useTimezone);
        }
        return 0;
    }



    /**
     * Returns true if the given date is Valid
     * @param string $string
     * @return boolean
     */
    public static function isValidDate(string $string): bool {
        return strtotime($string) !== false;
    }

    /**
     * Returns true if the given hour is Valid
     * @param string $string
     * @param array  $minutes Optional.
     * @return boolean
     */
    public static function isValidHour(string $string, array $minutes = null): bool {
        $parts = Strings::split($string, ":");
        return (
            isset($parts[0]) && Numbers::isValid($parts[0], 0, 23) &&
            isset($parts[1]) && Numbers::isValid($parts[1], 0, 59) &&
            (empty($minutes) || Arrays::contains($minutes, $parts[1]))
        );
    }

    /**
     * Returns true if the given dates are a valid period
     * @param string  $fromDate
     * @param string  $toDate
     * @param boolean $useTimezone Optional.
     * @return boolean
     */
    public static function isValidPeriod(string $fromDate, string $toDate, bool $useTimezone = true): bool {
        $fromTime = self::toDayStart($fromDate, $useTimezone);
        $toTime   = self::toDayEnd($toDate, $useTimezone);

        return $fromTime !== null && $toTime !== null && $fromTime < $toTime;
    }

    /**
     * Returns true if the given hours are a valid period
     * @param string $fromHour
     * @param string $toHour
     * @return boolean
     */
    public static function isValidHourPeriod(string $fromHour, string $toHour): bool {
        $date     = date("d-m-Y");
        $fromTime = self::toTimeHour($date, $fromHour);
        $toTime   = self::toTimeHour($date, $toHour);

        return $fromTime !== 0 && $toTime !== 0 && $fromTime < $toTime;
    }

    /**
     * Returns true if the given dates with hours are a valid period
     * @param string  $fromDate
     * @param string  $fromHour
     * @param string  $toDate
     * @param string  $toHour
     * @param boolean $useTimezone Optional.
     * @return boolean
     */
    public static function isValidFullPeriod(
        string $fromDate,
        string $fromHour,
        string $toDate,
        string $toHour,
        bool $useTimezone = true
    ): bool {
        $fromTime = self::toTimeHour($fromDate, $fromHour, $useTimezone);
        $toTime   = self::toTimeHour($toDate, $toHour, $useTimezone);

        return $fromTime !== 0 && $toTime !== 0 && $fromTime < $toTime;
    }

    /**
     * Returns true if the given week day is valid
     * @param integer $weekDay
     * @return boolean
     */
    public static function isValidWeekDay(int $weekDay): bool {
        return Numbers::isValid($weekDay, 0, 6);
    }



    /**
     * Returns true if the given Date is in the future
     * @param string  $date
     * @param string  $type        Optional.
     * @param boolean $useTimezone Optional.
     * @return boolean
     */
    public static function isFutureDate(string $date, string $type = "middle", bool $useTimezone = true): bool {
        $time = self::toDay($date, $type, $useTimezone);
        return self::isFutureTime($time);
    }

    /**
     * Returns true if the given Time is in the future
     * @param integer $time
     * @return boolean
     */
    public static function isFutureTime(int $time): bool {
        return $time > time();
    }

    /**
     * Returns true if the given Time is between the from and to Times
     * @param integer $time
     * @param integer $fromTime
     * @param integer $toTime
     * @return boolean
     */
    public static function isBetween(int $time, int $fromTime, int $toTime): bool {
        return $time >= $fromTime && $time <= $toTime;
    }

    /**
     * Returns true if the current Time is between the from and to Times
     * @param integer $fromTime
     * @param integer $toTime
     * @return boolean
     */
    public static function isCurrentBetween(int $fromTime, int $toTime): bool {
        return self::isBetween(time(), $fromTime, $toTime);
    }



    /**
     * Formats the time using the given Time Zone
     * @param integer $seconds
     * @param string  $format
     * @param integer $timezone Optional.
     * @return string
     */
    public static function format(int $seconds, string $format, int $timezone = null) {
        if (!empty($timezone)) {
            $seconds = self::toTimezone($seconds, $timezone);
        }
        return date($format, $seconds);
    }

    /**
     * Returns the Seconds as a string
     * @param integer $seconds
     * @return string
     */
    public static function toTimeString(int $seconds): string {
        $secsInMinute = 60;
        $secsInHour   = 60 * $secsInMinute;
        $secsInDay    = 24 * $secsInHour;
        $secsInWeek   = 7  * $secsInDay;

        // Extract the Weeks
        $weeks       = floor($seconds / $secsInWeek);

        // Extract the Days
        $daySeconds  = $seconds % $secsInWeek;
        $days        = floor($daySeconds / $secsInDay);

        // Extract the Hours
        $hourSeconds = $daySeconds % $secsInDay;
        $hours       = floor($hourSeconds / $secsInHour);

        // Extract the Minutes
        $minSeconds  = $daySeconds % $secsInHour;
        $mins        = floor($minSeconds / $secsInMinute);

        // Generate the Result
        if ($mins == 0) {
            return "0";
        }
        if ($hours == 0) {
            return "{$mins}m";
        }
        if ($days == 0) {
            return "{$hours}h";
        }
        if ($weeks == 0) {
            return "{$days}d-{$hours}h";
        }
        return "{$weeks}w-{$days}d-{$hours}h";
    }

    /**
     * Returns the Seconds as a days string
     * @param integer $seconds
     * @return string
     */
    public static function toDayString(int $seconds): string {
        $secsInDay = 24 * 3600;
        $days       = floor($seconds / $secsInDay);
        return "{$days}d";
    }



    /**
     * Returns the difference between 2 dates in Months
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getMonthsDiff(int $time1, int $time2): int {
        return 12 * (date("Y", $time1) - date("Y", $time2)) + date("n", $time1) - date("n", $time2);
    }

    /**
     * Returns the difference between 2 dates in Weeks
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getWeeksDiff(int $time1, int $time2): int {
        return floor(($time1 - $time2) / (7 * 24 * 3600));
    }

    /**
     * Returns the difference between 2 dates in Days
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getDaysDiff(int $time1, int $time2): int {
        return floor(($time1 - $time2) / (24 * 3600));
    }

    /**
     * Returns the difference between 2 dates in Minutes
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getMinsDiff(int $time1, int $time2): int {
        return floor(($time1 - $time2) / 60);
    }



    /**
     * Returns the Month and Year at the given month
     * @param integer $time
     * @return string
     */
    public static function getMonthYear(int $time): string {
        return self::getMonth(date("n", $time)) . " " . date("Y", $time);
    }

    /**
     * Returns the Month at the given month
     * @param integer $month
     * @return string
     */
    public static function getMonth(int $month): string {
        if ($month >= 1 && $month <= 12) {
            return self::$months[$month - 1];
        }
        return "";
    }

    /**
     * Returns a short version of the Month
     * @param integer $month
     * @return string
     */
    public static function getShortMonth(int $month): string {
        $result = self::getMonth($month);
        $result = Strings::substring($result, 0, 3);
        return Strings::toUpperCase($result);
    }
}
