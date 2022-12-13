<?php
namespace Framework\Utils;

use Framework\Utils\Strings;

/**
 * Several Date Time functions
 */
class DateTime {

    /** @var string[] */
    public static array $days = [ "Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado" ];

    /** @var string[] */
    public static array $months = [ "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre" ];

    /** @var array{} The Date Formats */
    public static array $formats = [
        "dashes"     => "d-m-Y",
        "time"       => "H:i",
        "dashesTime" => "d-m-Y H:i",
    ];

    public static int $serverDiff = -180;
    public static int $timeDiff   = 0;



    /**
     * Sets the Time Zone in minutes
     * @param integer $timezone
     * @return void
     */
    public static function setTimezone(int $timezone): void {
        self::$timeDiff = (self::$serverDiff - $timezone) * 60;
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
     * @param mixed   $time
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toTime(mixed $time, bool $useTimezone = true): int {
        if (is_string($time)) {
            $time = strtotime($time);
        }
        if (empty($time)) {
            return 0;
        }
        return self::toServerTime($time, $useTimezone);
    }

    /**
     * Returns the given time using the given Time Zone
     * @param mixed        $time
     * @param integer|null $timezone Optional.
     * @return integer
     */
    public static function toTimezone(mixed $time, ?int $timezone = null): int {
        if (is_string($time)) {
            $time = strtotime($time);
        }
        if (empty($time)) {
            return 0;
        }
        if ($timezone !== null) {
            $timeDiff = (self::$serverDiff - $timezone) * 60;
            return $time - $timeDiff;
        }
        return $time;
    }



    /**
     * Returns the given string as a time
     * @param string  $dateString
     * @param string  $hourString
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toTimeHour(string $dateString, string $hourString, bool $useTimezone = true): int {
        return self::toTime("$dateString $hourString", $useTimezone);
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
        return self::toTime($string, $useTimezone);
    }

    /**
     * Returns the given string as a time of the middle of the day
     * @param string  $string
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public static function toDayMiddle(string $string, bool $useTimezone = true): int {
        $result = self::toTime($string, $useTimezone);
        if (!empty($result)) {
            return $result + 12 * 3600;
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
        $result = self::toTime($string, $useTimezone);
        if (!empty($result)) {
            return $result + 24 * 3600 - 1;
        }
        return 0;
    }

    /**
     * Creates a Time, with the given month and year
     * @param integer|null $month       Optional.
     * @param integer|null $year        Optional.
     * @param boolean      $useTimezone Optional.
     * @return integer
     */
    public static function fromMonthYear(?int $month = null, ?int $year = null, bool $useTimezone = false): int {
        $time = mktime(0, 0, 0, !empty($month) ? $month : date("n"), 1, !empty($year) ? $year : date("Y"));
        return self::toTime($time, $useTimezone);
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
     * @param string         $string
     * @param integer[]|null $minutes Optional.
     * @return boolean
     */
    public static function isValidHour(string $string, ?array $minutes = null): bool {
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
     * @param mixed        $time
     * @param integer|null $timezone Optional.
     * @return boolean
     */
    public static function isFutureTime(mixed $time, ?int $timezone = null): bool {
        $seconds = self::toTimezone($time, $timezone);
        return $seconds > time();
    }

     /**
     * Returns true if the given Time is today
     * @param mixed        $time
     * @param integer|null $timezone Optional.
     * @return boolean
     */
    public static function isToday(mixed $time, ?int $timezone = null): bool {
        $seconds = self::toTimezone($time, $timezone);
        return date("d-m-Y", $seconds) == date("d-m-Y");
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
     * @param mixed        $time
     * @param string       $format
     * @param integer|null $timezone Optional.
     * @return string
     */
    public static function format(mixed $time, string $format, ?int $timezone = null): string {
        $seconds = self::toTimezone($time, $timezone);
        if (empty($seconds)) {
            return "";
        }
        return date($format, $seconds);
    }

    /**
     * Returns the Seconds as a string
     * @param mixed        $time
     * @param string       $format
     * @param integer|null $timezone Optional.
     * @return string
     */
    public static function toString(mixed $time, string $format, ?int $timezone = null): string {
        if (!empty(self::$formats[$format])) {
            return self::format($time, self::$formats[$format], $timezone);
        }
        return "";
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
        $days      = floor($seconds / $secsInDay);
        return "{$days}d";
    }



    /**
     * Returns the time of the start of the day
     * @param integer $time        Optional.
     * @param integer $dayDiff     Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getDayStart(int $time = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $time   = empty($time) ? time() : $time;
        $result = mktime(0, 0, 0, date("n", $time), date("j", $time) + $dayDiff, date("Y", $time));
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the time of the end of the day
     * @param integer $time        Optional.
     * @param integer $dayDiff     Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getDayEnd(int $time = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $time   = empty($time) ? time() : $time;
        $result = mktime(23, 59, 59, date("n", $time), date("j", $time) + $dayDiff, date("Y", $time));
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Add the given Days to the given time
     * @param integer $time        Optional.
     * @param integer $dayDiff     Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function addDays(int $time = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $time   = empty($time) ? time() : $time;
        $result = mktime(0, 0, 0, date("n", $time), date("j", $time) + $dayDiff, date("Y", $time));
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the time of the start of the week
     * @param integer $time        Optional.
     * @param integer $dayDiff     Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getWeekStart(int $time = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $time     = empty($time) ? time() : $time;
        $startDay = date("j", $time) - date("w", $time);
        $result   = mktime(0, 0, 0, date("n", $time), $startDay + $dayDiff, date("Y", $time));
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the time of the start of the month
     * @param integer $time        Optional.
     * @param integer $monthDiff   Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getMonthStart(int $time = 0, int $monthDiff = 0, bool $useTimeZone = false): int {
        $time   = empty($time) ? time() : $time;
        $result = mktime(0, 0, 0, date("n", $time) + $monthDiff, 1, date("Y", $time));
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Add the given months to the given time
     * @param integer $time        Optional.
     * @param integer $monthDiff   Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function addMonths(int $time = 0, int $monthDiff = 0, bool $useTimeZone = false): int {
        $time   = empty($time) ? time() : $time;
        $result = mktime(0, 0, 0, date("n", $time) + $monthDiff, date("j", $time), date("Y", $time));
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the start of the last x months
     * @param integer $months
     * @param integer $time        Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getLastXMonths(int $months, int $time = 0, bool $useTimeZone = false): int {
        $time   = empty($time) ? time() : $time;
        $result = mktime(0, 0, 0, date("n", $time) - $months, date("j", $time), date("Y", $time));
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the start of the last x days
     * @param integer $days
     * @param integer $time        Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getLastXDays(int $days, int $time = 0, bool $useTimeZone = false): int {
        $time   = empty($time) ? time() : $time;
        $result = $time - $days * 24 * 3600;
        return self::toServerTime($result, $useTimeZone);
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
     * Returns the amount of years between given date and today AKA the age
     * @param mixed        $time
     * @param integer|null $timezone Optional.
     * @return integer
     */
    public static function getAge(mixed $time, ?int $timezone = null): int {
        $seconds  = self::toTimezone($time, $timezone);
        if (empty($seconds)) {
            return 0;
        }
        $thisYear = (int)date("Y");
        $thatYear = (int)date("Y", $seconds);
        $result   = $thisYear - $thatYear;
        if ($seconds > time()) {
            $result += 1;
        }
        return $result;
    }



    /**
     * Returns the Day name at the given Time
     * @param integer      $time
     * @param integer|null $timezone Optional.
     * @return string
     */
    public static function getDay(int $time, ?int $timezone = null): string {
        $seconds = self::toTimezone($time, $timezone);
        return self::getDayName(date("w", $seconds));
    }

    /**
     * Returns the Day name at the given day
     * @param integer $day
     * @return string
     */
    public static function getDayName(int $day): string {
        if (!empty(self::$days[$day])) {
            return self::$days[$day];
        }
        return "";
    }

    /**
     * Returns the Month and Year at the given month
     * @param integer $time
     * @param integer $length      Optional.
     * @param boolean $inUpperCase Optional.
     * @return string
     */
    public static function getMonthYear(int $time, int $length = 0, bool $inUpperCase = false): string {
        return self::getMonth(date("n", $time), $length, $inUpperCase) . " " . date("Y", $time);
    }

    /**
     * Returns the Month name at the given month
     * @param integer $month
     * @param integer $length      Optional.
     * @param boolean $inUpperCase Optional.
     * @return string
     */
    public static function getMonth(int $month, int $length = 0, bool $inUpperCase = false): string {
        $result = "";
        if ($month >= 1 && $month <= 12) {
            $result = self::$months[$month - 1];
        }
        if ($length > 0) {
            $result = Strings::substring($result, 0, $length);
        }
        if ($inUpperCase) {
            $result = Strings::toUpperCase($result);
        }
        return $result;
    }

    /**
     * Returns a short version of the Month
     * @param integer $month
     * @return string
     */
    public static function getShortMonth(int $month): string {
        return self::getMonth($month, 3, true);
    }
}
