<?php
namespace Framework\Utils;

use Framework\NLS\NLS;
use Framework\Utils\Strings;

/**
 * Several Date Time functions
 */
class DateTime {

    /** @var array{} The Date Formats */
    public static array $formats = [
        "time"          => "H:i",
        "dashes"        => "d-m-Y",
        "dashesReverse" => "Y-m-d",
        "dashesTime"    => "d-m-Y H:i",
    ];

    /** @var float[] */
    public static array $stackZones = [];
    public static float $serverZone = -3;
    public static float $timeDiff   = 0;



    /**
     * Sets the Time Zone in minutes
     * @param float $timeZone
     * @return float
     */
    public static function setTimeZone(float $timeZone): float {
        if ($timeZone > 60 || $timeZone < 60) {
            $timeZone /= 60;
        }
        self::$stackZones = [ $timeZone ];
        self::$timeDiff   = self::$serverZone - $timeZone;
        return self::$timeDiff;
    }

    /**
     * Pushes a Time Zone
     * @param float $timeZone
     * @return float
     */
    public static function pushTimeZone(float $timeZone): float {
        if ($timeZone > 60 || $timeZone < 60) {
            $timeZone /= 60;
        }
        self::$stackZones[] = $timeZone;
        self::$timeDiff     = self::$serverZone - $timeZone;
        return self::$timeDiff;
    }

    /**
     * Pops a Time Zone
     * @return float
     */
    public static function popTimeZone(): float {
        if (count(self::$stackZones) > 1) {
            $timeZone = array_pop(self::$stackZones);
        } else {
            $timeZone = self::$stackZones[0];
        }
        self::$timeDiff = self::$serverZone - $timeZone;
        return self::$timeDiff;
    }

    /**
     * Returns the given time in the User Time Zone
     * @param integer $value
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toUserTime(int $value, bool $useTimeZone = true): int {
        if (!empty($value) && $useTimeZone) {
            return $value - (self::$timeDiff * 3600);
        }
        return $value;
    }

    /**
     * Returns the given time in the Server Time Zone
     * @param integer $value
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toServerTime(int $value, bool $useTimeZone = true): int {
        if (!empty($value) && $useTimeZone) {
            return $value + (self::$timeDiff * 3600);
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
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toTime(mixed $time, bool $useTimeZone = true): int {
        if (Strings::isString($time)) {
            $time = strtotime($time);
        }
        if (empty($time)) {
            return 0;
        }
        return self::toServerTime($time, $useTimeZone);
    }

    /**
     * Returns the given time using the given Time Zone
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return integer
     */
    public static function toTimeZone(mixed $time, ?float $timeZone = null): int {
        if (Strings::isString($time)) {
            $time = strtotime($time);
        }
        if (empty($time)) {
            return 0;
        }
        if ($timeZone !== null) {
            $timeDiff = self::$serverZone - $timeZone;
            return $time - ($timeDiff * 3600);
        }
        return $time;
    }

    /**
     * Returns a time
     * @param integer $time
     * @return integer
     */
    public static function getTime(int $time = 0): int {
        return !empty($time) ? $time : time();
    }

    /**
     * Creates a time
     * @param integer $day
     * @param integer $month
     * @param integer $year
     * @param integer $hour
     * @param integer $minute
     * @param integer $second
     * @return integer
     */
    public static function createTime(int $day, int $month, int $year, int $hour = 0, int $minute = 0, int $second = 0): int {
        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    /**
     * Returns the Day, Month and Year as a number
     * @param integer $day
     * @param integer $month
     * @param integer $year
     * @return integer
     */
    public static function toNumber(int $day, int $month, int $year): int {
        return $year * 10000 + $month * 100 + $day;
    }



    /**
     * Returns the given string as a time
     * @param string  $dateString
     * @param string  $hourString
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toTimeHour(string $dateString, string $hourString, bool $useTimeZone = true): int {
        return self::toTime("$dateString $hourString", $useTimeZone);
    }

    /**
     * Returns the given string as a time
     * @param string  $string
     * @param string  $type        Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toDay(string $string, string $type = "start", bool $useTimeZone = true): int {
        return match ($type) {
            "start" => self::toDayStart($string, $useTimeZone),
            "end"   => self::toDayEnd($string, $useTimeZone),
            default => self::toDayMiddle($string, $useTimeZone),
        };
    }

    /**
     * Returns the given string as a time of the start of the day
     * @param string  $string
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toDayStart(string $string, bool $useTimeZone = true): int {
        return self::toTime($string, $useTimeZone);
    }

    /**
     * Returns the given string as a time of the middle of the day
     * @param string  $string
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toDayMiddle(string $string, bool $useTimeZone = true): int {
        $result = self::toTime($string, $useTimeZone);
        if (!empty($result)) {
            return $result + 12 * 3600;
        }
        return 0;
    }

    /**
     * Returns the given string as a time of the end of the day
     * @param string  $string
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toDayEnd(string $string, bool $useTimeZone = true): int {
        $result = self::toTime($string, $useTimeZone);
        if (!empty($result)) {
            return $result + 24 * 3600 - 1;
        }
        return 0;
    }

    /**
     * Creates a Time, with the given month and year
     * @param integer|null $month       Optional.
     * @param integer|null $year        Optional.
     * @param boolean      $useTimeZone Optional.
     * @return integer
     */
    public static function fromMonthYear(?int $month = null, ?int $year = null, bool $useTimeZone = false): int {
        $month = !empty($month) ? $month : self::getMonth();
        $year  = !empty($year)  ? $year  : self::getYear();
        $time  = self::createTime(1, $month, $year);
        return self::toTime($time, $useTimeZone);
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
     * Returns true if the given day is Valid
     * @param string|integer $value
     * @param boolean        $withHolidays Optional.
     * @return boolean
     */
    public static function isValidDay(string|int $value, bool $withHolidays = false): bool {
        $totalDays = $withHolidays ? 7 : 6;
        return (int)$value >= 0 && (int)$value <= $totalDays;
    }

    /**
     * Returns true if the given hour is Valid
     * @param string         $string
     * @param integer[]|null $minutes Optional.
     * @param integer        $minHour Optional.
     * @param integer        $maxHour Optional.
     * @return boolean
     */
    public static function isValidHour(string $string, ?array $minutes = null, int $minHour = 0, int $maxHour = 23): bool {
        $parts = Strings::split($string, ":");
        return (
            isset($parts[0]) && Numbers::isValid($parts[0], $minHour, $maxHour) &&
            isset($parts[1]) && Numbers::isValid($parts[1], 0, 59) &&
            (empty($minutes) || Arrays::contains($minutes, $parts[1]))
        );
    }

    /**
     * Returns true if the given dates are a valid period
     * @param string  $fromDate
     * @param string  $toDate
     * @param boolean $useTimeZone Optional.
     * @return boolean
     */
    public static function isValidPeriod(string $fromDate, string $toDate, bool $useTimeZone = true): bool {
        $fromTime = self::toDayStart($fromDate, $useTimeZone);
        $toTime   = self::toDayEnd($toDate, $useTimeZone);

        return $fromTime !== null && $toTime !== null && $fromTime < $toTime;
    }

    /**
     * Returns true if the given hours are a valid period
     * @param string  $fromHour
     * @param string  $toHour
     * @param boolean $allow24
     * @return boolean
     */
    public static function isValidHourPeriod(string $fromHour, string $toHour, bool $allow24 = false): bool {
        $date     = date("d-m-Y");
        $time     = self::toDayStart($date);
        $fromTime = self::toTimeHour($date, $fromHour);
        $toTime   = self::toTimeHour($date, $toHour);

        if ($allow24 && $fromTime > 0 && $toTime === $time) {
            return true;
        }
        if ($fromTime !== 0 && $toTime !== 0 && $fromTime < $toTime) {
            return true;
        }
        return false;
    }

    /**
     * Returns true if the given dates with hours are a valid period
     * @param string  $fromDate
     * @param string  $fromHour
     * @param string  $toDate
     * @param string  $toHour
     * @param boolean $useTimeZone Optional.
     * @return boolean
     */
    public static function isValidFullPeriod(
        string $fromDate,
        string $fromHour,
        string $toDate,
        string $toHour,
        bool $useTimeZone = true
    ): bool {
        $fromTime = self::toTimeHour($fromDate, $fromHour, $useTimeZone);
        $toTime   = self::toTimeHour($toDate, $toHour, $useTimeZone);

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
     * @param boolean $useTimeZone Optional.
     * @return boolean
     */
    public static function isFutureDate(string $date, string $type = "middle", bool $useTimeZone = true): bool {
        $time = self::toDay($date, $type, $useTimeZone);
        return self::isFutureTime($time);
    }

    /**
     * Returns true if the given Time is in the future
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return boolean
     */
    public static function isFutureTime(mixed $time, ?float $timeZone = null): bool {
        $seconds = self::toTimeZone($time, $timeZone);
        return $seconds > time();
    }

     /**
     * Returns true if the given Time is today
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return boolean
     */
    public static function isToday(mixed $time, ?float $timeZone = null): bool {
        $seconds = self::toTimeZone($time, $timeZone);
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
     * Formats the Time using the given Time Zone
     * @param mixed      $time
     * @param string     $format
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function format(mixed $time, string $format, ?float $timeZone = null): string {
        $seconds = self::toTimeZone($time, $timeZone);
        if (empty($seconds)) {
            return "";
        }
        return date($format, $seconds);
    }

    /**
     * Returns the Time as a string
     * @param mixed      $time
     * @param string     $format
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function toString(mixed $time, string $format, ?float $timeZone = null): string {
        if (!empty(self::$formats[$format])) {
            return self::format($time, self::$formats[$format], $timeZone);
        }
        return "";
    }

    /**
     * Returns the Time as a ISO date string
     * @param mixed      $time     Optional.
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function toISOString(mixed $time, ?float $timeZone = null): string {
        return self::format($time, "c", $timeZone);
    }

    /**
     * Returns the Time as a UTC date string
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function toUTCString(mixed $time, ?float $timeZone = null): string {
        return Strings::replace(self::format($time, "c", $timeZone), "-03:00", "Z");
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
     * Returns the Seconds as an hours string
     * @param integer $seconds
     * @return string
     */
    public static function toHourString(int $seconds): string {
        $secsInMinute = 60;
        $secsInHour   = 60 * $secsInMinute;

        $hours        = floor($seconds / $secsInHour);

        $minSeconds   = $seconds % $secsInHour;
        $mins         = floor($minSeconds / $secsInMinute);
        $minsStr      = self::parseTime($mins);

        $secs         = $seconds % $secsInMinute;
        $secsStr      = self::parseTime($secs);

        return "{$hours}:{$minsStr}:{$secsStr} hs";
    }

    /**
     * Returns the Minutes as a string
     * @param integer $minutes
     * @return string
     */
    public static function toMinString(int $minutes): string {
        if ($minutes < 120) {
            return "{$minutes}m";
        }
        $hours = floor($minutes / 60);
        if ($hours < 72) {
            return "{$hours}h";
        }
        $days = floor($minutes / (60 * 24));
        return "{$days}d";
    }

    /**
     * Returns the Seconds as a string
     * @param integer $seconds
     * @return string
     */
    public static function toSecString(int $seconds): string {
        if ($seconds < 120) {
            return "{$seconds}s";
        }
        $minutes = floor($seconds / 60);
        return self::toMinString($minutes);
    }



    /**
     * Returns the time minus x months
     * @param integer $months
     * @param integer $time        Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getLastXMonths(int $months, int $time = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $day    = self::getDay($time);
        $month  = self::getMonth($time) - $months;
        $year   = self::getYear($time);
        $result = self::createTime($day, $month, $year);
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the time minus x days
     * @param float   $days
     * @param integer $time        Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getLastXDays(float $days, int $time = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $result = $time - $days * 24 * 3600;
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the time minus x hours
     * @param float   $hours
     * @param integer $time        Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getLastXHours(float $hours, int $time = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $result = $time - $hours * 3600;
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the time minus x minutes
     * @param float   $minutes
     * @param integer $time        Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getLastXMinutes(float $minutes, int $time = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $result = $time - $minutes * 60;
        return self::toServerTime($result, $useTimeZone);
    }



    /**
     * Returns the Year for the given Time
     * @param integer $time Optional.
     * @return integer
     */
    public static function getYear(int $time = 0): int {
        $time = self::getTime($time);
        return (int)date("Y", $time);
    }

    /**
     * Returns the Month for the given Time
     * @param integer $time    Optional.
     * @param boolean $zeroPad Optional.
     * @return integer|string
     */
    public static function getMonth(int $time = 0, bool $zeroPad = false): int|string {
        $time  = self::getTime($time);
        $month = (int)date("n", $time);
        return $zeroPad ? self::parseTime($month) : $month;
    }

    /**
     * Returns the amount of days in the Month for the given Time
     * @param integer $time Optional.
     * @return integer
     */
    public static function getMonthDays(int $time = 0): int {
        $time = self::getTime($time);
        return (int)date("t", $time);
    }

    /**
     * Returns true if the given time is the current month
     * @param integer    $time     Optional.
     * @param float|null $timeZone Optional.
     * @return integer
     */
    public static function isCurrentMonth(int $time = 0, ?float $timeZone = null): int {
        $time    = self::getTime($time);
        $seconds = self::toTimeZone($time, $timeZone);
        return (self::getYear() == self::getYear($seconds) && self::getMonth() == self::getMonth($seconds));
    }

    /**
     * Returns the Time of the start of the Month
     * @param integer $time        Optional.
     * @param integer $monthDiff   Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getMonthStart(int $time = 0, int $monthDiff = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $month  = self::getMonth($time) + $monthDiff;
        $year   = self::getYear($time);
        $result = self::createTime(1, $month, $year);
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the Time of the end of the Month
     * @param integer $time        Optional.
     * @param integer $monthDiff   Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getMonthEnd(int $time = 0, int $monthDiff = 0, bool $useTimeZone = false): int {
        $result = self::getMonthStart($time, $monthDiff + 1) - 1;
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the Time of the Month at the given Day position
     * @param integer $time        Optional.
     * @param integer $dayPosition Optional.
     * @param integer $weekDay     Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getMonthDayPos(int $time = 0, int $dayPosition = 0, int $weekDay = 0, bool $useTimeZone = false): int {
        $time        = self::getMonthStart($time);
        $thisWeekDay = self::getDayOfWeek($time);
        $increase    = $thisWeekDay > $weekDay ? 7 : 0;
        $days        = $dayPosition * 7 + $weekDay + $increase;
        $result      = self::getWeekStart($time, $days);
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Add the given Months to the given Time
     * @param integer $time        Optional.
     * @param integer $monthDiff   Optional.
     * @param integer $day         Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function addMonths(int $time = 0, int $monthDiff = 0, int $day = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $result = self::createTime(
            !empty($day) ? $day : self::getDay($time),
            self::getMonth($time) + $monthDiff,
            self::getYear($time),
            date("h", $time),
            date("i", $time),
            date("s", $time),
        );
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the difference between 2 times in Months
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getMonthsDiff(int $time1, int $time2): int {
        return 12 * ((int)self::getYear($time1) - (int)self::getYear($time2)) + self::getMonth($time1) - self::getMonth($time2);
    }

    /**
     * Returns the Month and Year at the given Month
     * @param integer $time
     * @param integer $length      Optional.
     * @param boolean $inUpperCase Optional.
     * @param string  $language    Optional.
     * @return string
     */
    public static function getMonthYear(int $time, int $length = 0, bool $inUpperCase = false, string $language = ""): string {
        return self::getMonthName(self::getMonth($time), $length, $inUpperCase, $language) . " " . self::getYear($time);
    }

    /**
     * Returns the Month name at the given Month
     * @param integer $month
     * @param integer $length      Optional.
     * @param boolean $inUpperCase Optional.
     * @param string  $language    Optional.
     * @return string
     */
    public static function getMonthName(int $month, int $length = 0, bool $inUpperCase = false, string $language = ""): string {
        $result = NLS::getIndex("DATE_TIME_MONTHS", $month - 1, $language);
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
     * @param string  $language Optional.
     * @return string
     */
    public static function getShortMonth(int $month, string $language = ""): string {
        return self::getMonthName($month, 3, true, $language);
    }



    /**
     * Returns the time of the start of the Week
     * @param integer $time        Optional.
     * @param integer $dayDiff     Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getWeekStart(int $time = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $time     = self::getTime($time);
        $startDay = self::getDay($time) - self::getDayOfWeek($time);
        $month    = self::getMonth($time);
        $year     = self::getYear($time);
        $result   = self::createTime($startDay + $dayDiff, $month, $year);
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the difference between 2 times in Weeks
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getWeeksDiff(int $time1, int $time2): int {
        return floor(abs($time1 - $time2) / (7 * 24 * 3600));
    }



    /**
     * Returns the Day for the given Time
     * @param integer $time    Optional.
     * @param boolean $zeroPad Optional.
     * @return integer|string
     */
    public static function getDay(int $time = 0, bool $zeroPad = false): int|string {
        $time = self::getTime($time);
        $day  = (int)date("j", $time);
        return $zeroPad ? self::parseTime($day) : $day;
    }

    /**
     * Returns the Day of Week
     * @param integer    $time        Optional.
     * @param boolean    $startMonday Optional.
     * @param float|null $timeZone    Optional.
     * @return integer
     */
    public static function getDayOfWeek(int $time = 0, bool $startMonday = false, ?float $timeZone = null): int {
        $time    = self::getTime($time);
        $seconds = self::toTimeZone($time, $timeZone);
        if ($startMonday) {
            return (int)date("N", $seconds);
        }
        return (int)date("w", $seconds);
    }

    /**
     * Returns the time of the start of the day
     * @param integer $time        Optional.
     * @param integer $dayDiff     Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function getDayStart(int $time = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $day    = self::getDay($time) + $dayDiff;
        $month  = self::getMonth($time);
        $year   = self::getYear($time);
        $result = self::createTime($day, $month, $year);
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
        $time   = self::getTime($time);
        $day    = self::getDay($time) + $dayDiff;
        $month  = self::getMonth($time);
        $year   = self::getYear($time);
        $result = self::createTime($day, $month, $year, 23, 59, 59);
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Add the given Days to the given Time
     * @param integer $time        Optional.
     * @param integer $dayDiff     Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function addDays(int $time = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $result = $time + $dayDiff * 24 * 3600;
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the difference between 2 dates in Days
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getDaysDiff(int $time1, int $time2): int {
        return floor(abs($time1 - $time2) / (24 * 3600));
    }

    /**
     * Returns the Day name at the given Time
     * @param integer    $time     Optional.
     * @param float|null $timeZone Optional.
     * @param string     $language Optional.
     * @return string
     */
    public static function getDayText(int $time = 0, ?float $timeZone = null, string $language = ""): string {
        $dayOfWeek = self::getDayOfWeek($time, false, $timeZone);
        return self::getDayName($dayOfWeek, 0, false, $language);
    }

    /**
     * Returns the Day name at the given Day
     * @param integer $day
     * @param integer $length      Optional.
     * @param boolean $inUpperCase Optional.
     * @param string  $language    Optional.
     * @return string
     */
    public static function getDayName(int $day, int $length = 0, bool $inUpperCase = false, string $language = ""): string {
        $result = NLS::getIndex("DATE_TIME_DAYS", $day, $language);
        if ($length > 0) {
            $result = Strings::substring($result, 0, $length);
        }
        if ($inUpperCase) {
            $result = Strings::toUpperCase($result);
        }
        return $result;
    }

    /**
     * Returns the Day and Month and Year at the given Time
     * @param integer $time
     * @param integer $length      Optional.
     * @param boolean $inUpperCase Optional.
     * @param string  $language    Optional.
     * @return string
     */
    public static function getDayMonth(int $time, int $length = 0, bool $inUpperCase = false, string $language = ""): string {
        $day   = self::getDay($time, true);
        $month = self::getMonth($time);
        return "$day " . self::getMonthName($month, $length, $inUpperCase, $language);
    }



    /**
     * Add the given Hours to the given Time
     * @param integer $time        Optional.
     * @param integer $hourDiff    Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function addHours(int $time = 0, int $hourDiff = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $result = $time + $hourDiff * 3600;
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Add the given Minutes to the given Time
     * @param integer $time        Optional.
     * @param integer $minuteDiff  Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function addMinutes(int $time = 0, int $minuteDiff = 0, bool $useTimeZone = false): int {
        $time   = self::getTime($time);
        $result = $time + $minuteDiff * 60;
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Add the given Time to the given Time
     * @param integer $time        Optional.
     * @param string  $timeDiff    Optional.
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function addTime(int $time = 0, string $timeDiff = "", bool $useTimeZone = false): int {
        $time    = self::getTime($time);
        $minutes = self::timeToMinutes($timeDiff);
        $result  = $time + $minutes * 60;
        return self::toServerTime($result, $useTimeZone);
    }

    /**
     * Converts Hours and Minutes to Minutes
     * @param integer|null $hours    Optional.
     * @param integer|null $minutes  Optional.
     * @param float|null   $timeZone Optional.
     * @return integer
     */
    public static function toMinutes(?int $hours = null, ?int $minutes = null, ?float $timeZone = null): int {
        if ($hours === null || $minutes === null) {
            $result = (int)date("H") * 60 + (int)date("i");
        } else {
            $result = $hours * 60 + $minutes;
        }
        if (!empty($timeZone)) {
            $timeDiff = self::$serverZone - $timeZone;
            $result  -= ($timeDiff * 60);
        }
        return $result;
    }

    /**
     * Converts the Time to Minutes
     * @param string     $time
     * @param float|null $timeZone Optional.
     * @return integer
     */
    public static function timeToMinutes(string $time, ?float $timeZone = null): int {
        $parts = Strings::split($time, ":");
        if (empty($parts) || count($parts) != 2) {
            return 0;
        }
        return self::toMinutes((int)$parts[0], (int)$parts[1], $timeZone);
    }

    /**
     * Converts the Minutes to a Time
     * @param integer $minutes
     * @return string
     */
    public static function minutesToTime(int $minutes): string {
        $hours = floor($minutes / 60);
        $mins  = $minutes - $hours * 60;
        return self::parseTime($hours) . ":" . self::parseTime($mins);
    }

    /**
     * Returns the difference between 2 times in Minutes
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getMinsDiff(int $time1, int $time2): int {
        return floor(abs($time1 - $time2) / 60);
    }



    /**
     * Returns the amount of years between given date and today AKA the age
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return integer
     */
    public static function getAge(mixed $time, ?float $timeZone = null): int {
        $seconds  = self::toTimeZone($time, $timeZone);
        if (empty($seconds)) {
            return 0;
        }

        $thisYear = self::getYear();
        $thatYear = self::getYear($seconds);
        $result   = $thisYear - $thatYear;
        if ($seconds > time()) {
            $result += 1;
        }
        return $result;
    }

    /**
     * Returns a number as a String with a 0 in front, if required
     * @param integer|float $time
     * @return string
     */
    public static function parseTime(int|float $time): string {
        return $time < 10 ? "0{$time}" : (string)$time;
    }

    /**
     * Parses the Time Zone
     * @param float $timeZone
     * @return string
     */
    public static function parseTimeZone(float $timeZone): string {
        $sign    = $timeZone < 0 ? "-" : "+";
        $time    = abs($timeZone * 60);
        $hours   = floor($time / 60);
        $minutes = $time - $hours * 60;

        return "GMT $sign$hours:" . self::parseTime($minutes);
    }



    /**
     * Parses a text into a Timestamp
     * @param string $text
     * @param string $language Optional.
     * @return integer
     */
    public static function parseDate(string $text, string $language = ""): int {
        $glue = Strings::contains($text, "/") ? "/" : (Strings::contains($text, "-") ? "-" : "");
        if (!empty($glue)) {
            return self::parseDateGlue($text, $glue);
        }
        return self::parseDateLang($text, $language);
    }

    /**
     * Parses a text with the given glue into a Timestamp
     * @param string $text
     * @param string $glue
     * @return integer
     */
    private static function parseDateGlue(string $text, string $glue): int {
        $parts  = Strings::split($text, $glue);
        $amount = count($parts);
        $part0  = (int)$parts[0];
        $part1  = $amount > 1 ? (int)$parts[1] : 0;
        $part2  = $amount > 2 ? (int)$parts[2] : 0;

        if (empty($part0) || empty($part1)) {
            return 0;
        }

        $year  = self::getYear();
        $month = $part1;
        $day   = $part0;

        if ($amount == 3 && $part0 > 1000) {
            $year  = $part0;
            $month = $part1;
            $day   = $part2;
        } elseif (!empty($part2)) {
            $yearStr = trim($parts[2]);
            if (Strings::length($yearStr) == 2) {
                if ((int)$yearStr >= 50) {
                    $year = (int)"19$yearStr";
                } else {
                    $year = (int)"20$yearStr";
                }
            } else {
                $year = (int)$yearStr;
            }
        }

        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * Parses a text with a language into a Timestamp
     * @param string $text
     * @param string $language
     * @return integer
     */
    public static function parseDateLang(string $text, string $language): int {
        $year  = self::getYear();
        $month = 0;
        $day   = 0;

        $numbers = Strings::getAllMatches($text, "!\d+!");
        if (empty($numbers)) {
            return 0;
        }

        $monthNames = NLS::get("DATE_TIME_MONTHS", $language);
        foreach ($monthNames as $index => $monthName) {
            if (Strings::containsCaseInsensitive($text, $monthName)) {
                $month = $index + 1;
                break;
            }
        }
        if (empty($month)) {
            return 0;
        }

        foreach ($numbers as $number) {
            if ($number <= 0) {
                continue;
            }
            if (empty($day) && $number <= 31) {
                $day = $number;
            } elseif ($number >= 1000) {
                $year = $number;
            } elseif ($number >= 100) {
                $year = (int)"1$number";
            } elseif ($number >= 50) {
                $year = (int)"19$number";
            } else {
                $year = (int)"20$number";
            }
        }
        if (empty($day)) {
            return 0;
        }

        return mktime(0, 0, 0, $month, $day, $year);
    }
}
