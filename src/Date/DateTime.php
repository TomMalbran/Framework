<?php
namespace Framework\Date;

use Framework\Intl\NLS;
use Framework\Date\TimeZone;
use Framework\Date\DateType;
use Framework\Date\DateFormat;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * Several Date Time functions
 */
class DateTime {

    /**
     * Date::createOrNew($time)
     * Returns the given string as a time
     * @param mixed $time
     * @param bool  $useTimeZone Optional.
     * @return int
     */
    public static function toTime(mixed $time, bool $useTimeZone = true): int {
        $timeStamp = 0;
        if (is_string($time) && $time !== "") {
            $timeStamp = Numbers::toInt(strtotime($time));
        } else {
            $timeStamp = Numbers::toInt($time);
        }

        if ($timeStamp === 0 || $timeStamp > 4294967295) {
            return 0;
        }
        return TimeZone::toServerTime($timeStamp, $useTimeZone);
    }

    /**
     * Date::create($time)->toServerTime()
     * Returns the given time using the given Time Zone
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return int
     */
    public static function toTimeZone(mixed $time, ?float $timeZone = null): int {
        $timeStamp = 0;
        if (is_string($time)) {
            $timeStamp = Numbers::toInt(strtotime($time));
        } else {
            $timeStamp = Numbers::toInt($time);
        }

        if ($timeStamp === 0) {
            return 0;
        }
        if ($timeZone !== null) {
            $timeDiff = TimeZone::calcTimeDiff($timeZone);
            return $timeStamp - (int)($timeDiff * 3600);
        }
        return $timeStamp;
    }

    /**
     * Date::create($time)->getTime()
     * Returns a Time Stamp
     * @param int $timeStamp
     * @return int
     */
    public static function getTime(int $timeStamp = 0): int {
        return $timeStamp !== 0 ? $timeStamp : time();
    }

    /**
     * Date::create($day, $month, $year, $hour, $minute, $second)
     * Creates a Time Stamp
     * @param int $day
     * @param int $month
     * @param int $year
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return int
     */
    public static function createTime(
        int $day,
        int $month,
        int $year,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
    ): int {
        $result = mktime($hour, $minute, $second, $month, $day, $year);
        return $result !== false ? $result : 0;
    }

    /**
     * Date::create($day, $month, $year)->toNumber()
     * Returns the Day, Month and Year as a number
     * @param int $day
     * @param int $month
     * @param int $year
     * @return int
     */
    public static function toNumber(int $day, int $month, int $year): int {
        return $year * 10000 + $month * 100 + $day;
    }



    /**
     * Date::create($dateString, $hourString)
     * Returns the given string as a time
     * @param string $dateString
     * @param string $hourString
     * @param bool   $useTimeZone Optional.
     * @return int
     */
    public static function toTimeHour(string $dateString, string $hourString, bool $useTimeZone = true): int {
        return self::toTime("$dateString $hourString", $useTimeZone);
    }

    /**
     * Date::create($dateString)->toDayMoment($dateType)
     * Returns the given string as a time
     * @param string   $string
     * @param DateType $dateType    Optional.
     * @param bool     $useTimeZone Optional.
     * @return int
     */
    public static function toDay(string $string, DateType $dateType = DateType::Start, bool $useTimeZone = true): int {
        return match ($dateType) {
            DateType::Start  => self::toDayStart($string, $useTimeZone),
            DateType::Middle => self::toDayMiddle($string, $useTimeZone),
            DateType::End    => self::toDayEnd($string, $useTimeZone),
            DateType::None   => 0,
        };
    }

    /**
     * Date::create($dateString)->toDayStart()
     * Returns the given string as a time of the start of the day
     * @param string $string
     * @param bool   $useTimeZone Optional.
     * @return int
     */
    public static function toDayStart(string $string, bool $useTimeZone = true): int {
        $timeStamp = self::toTime($string, $useTimeZone);
        if ($timeStamp !== 0) {
            return self::getDayStart($timeStamp);
        }
        return 0;
    }

    /**
     * Date::create($dateString)->toDayMiddle()
     * Returns the given string as a time of the middle of the day
     * @param string $string
     * @param bool   $useTimeZone Optional.
     * @return int
     */
    public static function toDayMiddle(string $string, bool $useTimeZone = true): int {
        $timeStamp = self::toDayStart($string, $useTimeZone);
        if ($timeStamp !== 0) {
            $timeDiff = TimeZone::getCurrentTimeDiff();
            return $timeStamp + 12 * 3600 - (int)($timeDiff * 3600);
        }
        return 0;
    }

    /**
     * Date::create($dateString)->toDayEnd()
     * Returns the given string as a time of the end of the day
     * @param string $string
     * @param bool   $useTimeZone Optional.
     * @return int
     */
    public static function toDayEnd(string $string, bool $useTimeZone = true): int {
        $timeStamp = self::toDayStart($string, $useTimeZone);
        if ($timeStamp !== 0) {
            return $timeStamp + 24 * 3600 - 1;
        }
        return 0;
    }

    /**
     * Date::create(month: $month, year: $year)
     * Creates a Time, with the given month and year
     * @param int|null $month       Optional.
     * @param int|null $year        Optional.
     * @param bool     $useTimeZone Optional.
     * @return int
     */
    public static function fromMonthYear(?int $month = null, ?int $year = null, bool $useTimeZone = false): int {
        if ($month === null || $month < 1 || $month > 12) {
            $month = self::getMonth();
        }
        if ($year === null || $year === 0) {
            $year = self::getYear();
        }
        $timeStamp = self::createTime(1, $month, $year);
        return self::toTime($timeStamp, $useTimeZone);
    }



    /**
     * Date::create($dateString)->toDayMoment($dateType)->isFuture()
     * Returns true if the given Date is in the future
     * @param string   $dateString
     * @param DateType $dateType    Optional.
     * @param bool     $useTimeZone Optional.
     * @return bool
     */
    public static function isFutureDate(
        string $dateString,
        DateType $dateType = DateType::Middle,
        bool $useTimeZone = true,
    ): bool {
        $timeStamp = self::toDay($dateString, $dateType, $useTimeZone);
        return self::isFutureTime($timeStamp);
    }

    /**
     * Date::create($dateString, $hourString)->isFuture()
     * Returns true if the given Date and Hour is in the future
     * @param string $dateString
     * @param string $hourString
     * @param bool   $useTimeZone Optional.
     * @return bool
     */
    public static function isFutureDateHour(string $dateString, string $hourString, bool $useTimeZone = true): bool {
        $timeStamp = self::toTimeHour($dateString, $hourString, $useTimeZone);
        return self::isFutureTime($timeStamp);
    }

    /**
     * Date::create($time)->isFuture()
     * Returns true if the given Time is in the future
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return bool
     */
    public static function isFutureTime(mixed $time, ?float $timeZone = null): bool {
        $timeStamp = self::toTimeZone($time, $timeZone);
        return $timeStamp > time();
    }

    /**
     * Date::create($time)->isToday()
     * Returns true if the given Time is today
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return bool
     */
    public static function isToday(mixed $time, ?float $timeZone = null): bool {
        $timeStamp = self::toTimeZone($time, $timeZone);
        return date("d-m-Y", $timeStamp) === date("d-m-Y");
    }

    /**
     * Date::create($timeStamp)->isBetween($fromTime, $toTime)
     * Returns true if the given Time is between the from and to Times
     * @param int $timeStamp
     * @param int $fromTime
     * @param int $toTime
     * @return bool
     */
    public static function isBetween(int $timeStamp, int $fromTime, int $toTime): bool {
        return $timeStamp >= $fromTime && $timeStamp <= $toTime;
    }

    /**
     * Date::now()->isBetween($fromTime, $toTime)
     * Returns true if the current Time is between the from and to Times
     * @param int $fromTime
     * @param int $toTime
     * @return bool
     */
    public static function isCurrentBetween(int $fromTime, int $toTime): bool {
        return self::isBetween(time(), $fromTime, $toTime);
    }



    /**
     * Date::create($time)->format($format)
     * Formats the Time using the given Time Zone
     * @param mixed      $time
     * @param string     $format
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function format(mixed $time, string $format, ?float $timeZone = null): string {
        $timeStamp = self::toTimeZone($time, $timeZone);
        if ($timeStamp === 0) {
            return "";
        }
        return date($format, $timeStamp);
    }

    /**
     * Date::create($time)->toString($format)
     * Returns the Time as a string
     * @param mixed      $time
     * @param DateFormat $format
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function toString(mixed $time, DateFormat $format, ?float $timeZone = null): string {
        return self::format($time, $format->value, $timeZone);
    }

    /**
     * Date::create($time)->toISOString()
     * Returns the Time as a ISO date string
     * @param mixed      $time     Optional.
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function toISOString(mixed $time, ?float $timeZone = null): string {
        return self::format($time, "c", $timeZone);
    }

    /**
     * Date::create($time)->toUTCString()
     * Returns the Time as a UTC date string
     * @param mixed      $time
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function toUTCString(mixed $time, ?float $timeZone = null): string {
        return Strings::replace(self::format($time, "c", $timeZone), "-03:00", "Z");
    }

    /**
     * Date::create($fromTime)->toHourPeriodString($toTime)
     * Returns the Hour Period as a string
     * @param int        $fromTime
     * @param int        $toTime
     * @param float|null $timeZone Optional.
     * @return string
     */
    public static function toHourPeriodString(int $fromTime, int $toTime, ?float $timeZone = null): string {
        $fromTimeStr = self::toString($fromTime, DateFormat::Time, $timeZone);
        $toTimeStr   = self::toString($toTime, DateFormat::Time, $timeZone);
        return "$fromTimeStr - $toTimeStr";
    }

    /**
     * Date::create($seconds)->toTimeString()
     * Returns the Seconds as a string
     * @param int $seconds
     * @return string
     */
    public static function toTimeString(int $seconds): string {
        $secsInMinute = 60;
        $secsInHour   = 60 * $secsInMinute;
        $secsInDay    = 24 * $secsInHour;
        $secsInWeek   = 7  * $secsInDay;

        // Extract the Weeks
        $weeks       = (int)floor($seconds / $secsInWeek);

        // Extract the Days
        $daySeconds  = $seconds % $secsInWeek;
        $days        = (int)floor($daySeconds / $secsInDay);

        // Extract the Hours
        $hourSeconds = $daySeconds % $secsInDay;
        $hours       = (int)floor($hourSeconds / $secsInHour);

        // Extract the Minutes
        $minSeconds  = $daySeconds % $secsInHour;
        $mins        = (int)floor($minSeconds / $secsInMinute);

        // Generate the Result
        if ($mins === 0) {
            return "0";
        }
        if ($hours === 0) {
            return "{$mins}m";
        }
        if ($days === 0) {
            return "{$hours}h";
        }
        if ($weeks === 0) {
            return "{$days}d-{$hours}h";
        }
        return "{$weeks}w-{$days}d-{$hours}h";
    }

    /**
     * DateUtils::getDayString($seconds)
     * Returns the Seconds as a days string
     * @param int $seconds
     * @return string
     */
    public static function toDayString(int $seconds): string {
        $secsInDay = 24 * 3600;
        $days      = floor($seconds / $secsInDay);
        return "{$days}d";
    }

    /**
     * Returns the Seconds as an hours string
     * @param int $seconds
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
     * DateUtils::getMinString($minutes, $decimals)
     * Returns the Minutes as a string
     * @param int|float $minutes
     * @param int       $decimals Optional.
     * @return string
     */
    public static function toMinString(int|float $minutes, int $decimals = 0): string {
        if ($minutes < 120) {
            return "{$minutes}m";
        }

        $hours = Numbers::divide($minutes, 60, $decimals);
        if ($hours < 72) {
            return "{$hours}h";
        }

        $days = Numbers::divide($minutes, 60 * 24, $decimals);
        return "{$days}d";
    }

    /**
     * DateUtils::getSecString($seconds, $decimals)
     * Returns the Seconds as a string
     * @param int $seconds
     * @param int $decimals Optional.
     * @return string
     */
    public static function toSecString(int $seconds, int $decimals = 0): string {
        if ($seconds < 120) {
            return "{$seconds}s";
        }

        $minutes = Numbers::divide($seconds, 60, $decimals);
        return self::toMinString($minutes, $decimals);
    }



    /**
     * Date::now()->subtract(months: $months)
     * Date::create($timeStamp)->subtract(months: $months)
     * Returns the Time Stamp minus x months
     * @param int  $months
     * @param int  $timeStamp   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getLastXMonths(int $months, int $timeStamp = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $day       = self::getDay($timeStamp);
        $month     = self::getMonth($timeStamp) - $months;
        $year      = self::getYear($timeStamp);
        $result    = self::createTime($day, $month, $year);
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::now()->subtract(days: $days)
     * Date::create($timeStamp)->subtract(days: $days)
     * Returns the Time Stamp minus x days
     * @param int  $days
     * @param int  $timeStamp   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getLastXDays(int $days, int $timeStamp = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $result    = $timeStamp - $days * 24 * 3600;
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::now()->subtract(hours: $hours)
     * Date::create($timeStamp)->subtract(hours: $hours)
     * Returns the Time Stamp minus x hours
     * @param int  $hours
     * @param int  $timeStamp   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getLastXHours(int $hours, int $timeStamp = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $result    = $timeStamp - $hours * 3600;
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::now()->subtract(minutes: $minutes)
     * Date::create($timeStamp)->subtract(minutes: $minutes)
     * Returns the Time Stamp minus x minutes
     * @param int  $minutes
     * @param int  $timeStamp   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getLastXMinutes(int $minutes, int $timeStamp = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $result    = $timeStamp - $minutes * 60;
        return TimeZone::toServerTime($result, $useTimeZone);
    }



    /**
     * Date::now()->add(months: $months)
     * Date::create($timeStamp)->add(months: $months)
     * Returns the Time Stamp plus x months
     * @param int  $months
     * @param int  $timeStamp   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getNextXMonths(int $months, int $timeStamp = 0, bool $useTimeZone = false): int {
        return self::getLastXMonths(-$months, $timeStamp, $useTimeZone);
    }

    /**
     * Date::now()->add(days: $days)
     * Date::create($timeStamp)->add(days: $days)
     * Returns the Time Stamp plus x days
     * @param int  $days
     * @param int  $timeStamp   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getNextXDays(int $days, int $timeStamp = 0, bool $useTimeZone = false): int {
        return self::getLastXDays(-$days, $timeStamp, $useTimeZone);
    }

    /**
     * Date::now()->add(hours: $hours)
     * Date::create($timeStamp)->add(hours: $hours)
     * Returns the Time Stamp plus x hours
     * @param int  $hours
     * @param int  $timeStamp   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getNextXHours(int $hours, int $timeStamp = 0, bool $useTimeZone = false): int {
        return self::getLastXHours(-$hours, $timeStamp, $useTimeZone);
    }

    /**
     * Date::now()->add(minutes: $minutes)
     * Date::create($timeStamp)->add(minutes: $minutes)
     * Returns the Time Stamp plus x minutes
     * @param int  $minutes
     * @param int  $timeStamp   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getNextXMinutes(int $minutes, int $timeStamp = 0, bool $useTimeZone = false): int {
        return self::getLastXMinutes(-$minutes, $timeStamp, $useTimeZone);
    }



    /**
     * Date::createOrNow($timeStamp)->getMonth()
     * Returns the Month for the given Time Stamp
     * @param int $timeStamp Optional.
     * @return int
     */
    public static function getMonth(int $timeStamp = 0): int {
        $timeStamp = self::getTime($timeStamp);
        return (int)date("n", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->getMonthZero()
     * Returns the Month for the given Time Stamp with leading zero (01 to 12)
     * @param int $timeStamp Optional.
     * @return string
     */
    public static function getMonthZero(int $timeStamp = 0): string {
        $timeStamp = self::getTime($timeStamp);
        return date("m", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->getMonthDays()
     * Returns the amount of days in the Month for the given Time Stamp
     * @param int $timeStamp Optional.
     * @return int
     */
    public static function getMonthDays(int $timeStamp = 0): int {
        $timeStamp = self::getTime($timeStamp);
        return (int)date("t", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->isCurrentMonth()
     * Returns true if the given time is the current month
     * @param int        $timeStamp Optional.
     * @param float|null $timeZone  Optional.
     * @return bool
     */
    public static function isCurrentMonth(int $timeStamp = 0, ?float $timeZone = null): bool {
        $timeStamp = self::getTime($timeStamp);
        $timeStamp = self::toTimeZone($timeStamp, $timeZone);
        return (
            self::getYear() === self::getYear($timeStamp) &&
            self::getMonth() === self::getMonth($timeStamp)
        );
    }

    /**
     * Date::createOrNow($timeStamp)->toMonthStart()
     * Returns the Time Stamp of the start of the Month
     * @param int  $timeStamp   Optional.
     * @param int  $monthDiff   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getMonthStart(int $timeStamp = 0, int $monthDiff = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $month     = self::getMonth($timeStamp) + $monthDiff;
        $year      = self::getYear($timeStamp);
        $result    = self::createTime(1, $month, $year);
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp)->toMonthEnd()
     * Returns the Time Stamp of the end of the Month
     * @param int  $timeStamp   Optional.
     * @param int  $monthDiff   Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getMonthEnd(int $timeStamp = 0, int $monthDiff = 0, bool $useTimeZone = false): int {
        $result = self::getMonthStart($timeStamp, $monthDiff + 1) - 1;
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Returns the Time Stamp of the Month at the given Day position
     * @param int  $timeStamp   Optional.
     * @param int  $dayPosition Optional.
     * @param int  $weekDay     Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getMonthDayPos(
        int $timeStamp = 0,
        int $dayPosition = 0,
        int $weekDay = 0,
        bool $useTimeZone = false,
    ): int {
        $timeStamp   = self::getMonthStart($timeStamp);
        $thisWeekDay = self::getDayOfWeek($timeStamp);
        $increase    = $thisWeekDay > $weekDay ? 7 : 0;
        $days        = $dayPosition * 7 + $weekDay + $increase;
        $result      = self::getWeekStart($timeStamp, $days);
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp)->add(months: $months)
     * Add the given Months to the given Time Stamp
     * @param int  $timeStamp   Optional.
     * @param int  $monthDiff   Optional.
     * @param int  $day         Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function addMonths(
        int $timeStamp = 0,
        int $monthDiff = 0,
        int $day = 0,
        bool $useTimeZone = false,
    ): int {
        $timeStamp = self::getTime($timeStamp);
        $result    = self::createTime(
            $day > 0 ? $day : self::getDay($timeStamp),
            self::getMonth($timeStamp) + $monthDiff,
            self::getYear($timeStamp),
            self::getHour($timeStamp),
            (int)date("i", $timeStamp),
            (int)date("s", $timeStamp),
        );
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp1)->getMonthsDiff($timeStamp2)
     * Returns the difference between 2 Time Stamps in Months
     * @param int $timeStamp1
     * @param int $timeStamp2
     * @return int
     */
    public static function getMonthsDiff(int $timeStamp1, int $timeStamp2): int {
        return 12 * (self::getYear($timeStamp1) - self::getYear($timeStamp2)) +
            self::getMonth($timeStamp1) - self::getMonth($timeStamp2);
    }

    /**
     * Returns the Month and Year for the given Time Stamp
     * @param int    $timeStamp
     * @param int    $length      Optional.
     * @param bool   $inUpperCase Optional.
     * @param string $language    Optional.
     * @return string
     */
    public static function getMonthYear(
        int $timeStamp,
        int $length = 0,
        bool $inUpperCase = false,
        string $language = "",
    ): string {
        return DateUtils::getMonthName(self::getMonth($timeStamp), $length, $inUpperCase, $language) . " " .
            self::getYear($timeStamp);
    }

    /**
     * Returns a short version of the Month
     * @param int    $month
     * @param string $language Optional.
     * @return string
     */
    public static function getShortMonth(int $month, string $language = ""): string {
        return DateUtils::getMonthName($month, 3, true, $language);
    }



    /**
     * Date::createOrNow($timeStamp)->getYear()
     * Returns the Year for the given Time Stamp
     * @param int $timeStamp Optional.
     * @return int
     */
    public static function getYear(int $timeStamp = 0): int {
        $timeStamp = self::getTime($timeStamp);
        return (int)date("Y", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->getYearDays()
     * Returns the amount of days in the Year for the given Time Stamp
     * @param int $timeStamp Optional.
     * @return int
     */
    public static function getYearDays(int $timeStamp = 0): int {
        $timeStamp = self::getTime($timeStamp);
        // NOTE: L returns 1 for leap years and 0 for non-leap years
        return 365 + (int)date("L", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->add(years: $yearDiff)->set(month: $month, day: $day)
     * Add the given Years to the given Time Stamp
     * @param int  $timeStamp   Optional.
     * @param int  $yearDiff    Optional.
     * @param int  $month       Optional.
     * @param int  $day         Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function addYears(
        int $timeStamp = 0,
        int $yearDiff = 0,
        int $month = 0,
        int $day = 0,
        bool $useTimeZone = false,
    ): int {
        $timeStamp = self::getTime($timeStamp);
        $result    = self::createTime(
            $day   > 0 ? $day   : self::getDay($timeStamp),
            $month > 0 ? $month : self::getMonth($timeStamp),
            self::getYear($timeStamp) + $yearDiff,
            self::getHour($timeStamp),
            (int)date("i", $timeStamp),
            (int)date("s", $timeStamp),
        );
        return TimeZone::toServerTime($result, $useTimeZone);
    }



    /**
     * Date::createOrNow($timeStamp)->toWeekStart()
     * Returns the Time Stamp of the start of the Week
     * @param int  $timeStamp   Optional.
     * @param int  $dayDiff     Optional.
     * @param bool $startMonday Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getWeekStart(
        int $timeStamp = 0,
        int $dayDiff = 0,
        bool $startMonday = false,
        bool $useTimeZone = false,
    ): int {
        $timeStamp = self::getTime($timeStamp);
        $startDay  = self::getDay($timeStamp) - self::getDayOfWeek($timeStamp, $startMonday);
        $month     = self::getMonth($timeStamp);
        $year      = self::getYear($timeStamp);
        $result    = self::createTime($startDay + $dayDiff, $month, $year);
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp)->getWeeksDiff()
     * Returns the difference between 2 Time Stamps in Weeks
     * @param int $timeStamp1
     * @param int $timeStamp2
     * @return int
     */
    public static function getWeeksDiff(int $timeStamp1, int $timeStamp2): int {
        return (int)floor(abs($timeStamp1 - $timeStamp2) / (7 * 24 * 3600));
    }



    /**
     * Date::createOrNow($timeStamp)->getDay()
     * Returns the Day for the given Time Stamp
     * @param int $timeStamp Optional.
     * @return int
     */
    public static function getDay(int $timeStamp = 0): int {
        $timeStamp = self::getTime($timeStamp);
        return (int)date("j", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->getDayZero()
     * Returns the Day for the given Time Stamp with leading 0 (01 to 31)
     * @param int $timeStamp Optional.
     * @return string
     */
    public static function getDayZero(int $timeStamp = 0): string {
        $timeStamp = self::getTime($timeStamp);
        return date("d", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->getDayOfWeek()
     * Returns the Day of Week of the given Time Stamp
     * @param int        $timeStamp   Optional.
     * @param bool       $startMonday Optional.
     * @param float|null $timeZone    Optional.
     * @return int
     */
    public static function getDayOfWeek(int $timeStamp = 0, bool $startMonday = false, ?float $timeZone = null): int {
        $timeStamp = self::getTime($timeStamp);
        $timeStamp = self::toTimeZone($timeStamp, $timeZone);
        if ($startMonday) {
            return (int)date("N", $timeStamp);
        }
        return (int)date("w", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->toDayStart()
     * Returns the Time Stamp of the start of the day
     * @param int  $timeStamp   Optional.
     * @param int  $dayDiff     Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getDayStart(int $timeStamp = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $day       = self::getDay($timeStamp) + $dayDiff;
        $month     = self::getMonth($timeStamp);
        $year      = self::getYear($timeStamp);
        $result    = self::createTime($day, $month, $year);
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp)->toDayEnd()
     * Returns the Time Stamp of the end of the day
     * @param int  $timeStamp   Optional.
     * @param int  $dayDiff     Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function getDayEnd(int $timeStamp = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $day       = self::getDay($timeStamp) + $dayDiff;
        $month     = self::getMonth($timeStamp);
        $year      = self::getYear($timeStamp);
        $result    = self::createTime($day, $month, $year, 23, 59, 59);
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp)->add(days: $dayDiff)
     * Add the given Days to the given Time Stamp
     * @param int  $timeStamp   Optional.
     * @param int  $dayDiff     Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function addDays(int $timeStamp = 0, int $dayDiff = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $result    = $timeStamp + $dayDiff * 24 * 3600;
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp1)->getDaysDiff($timeStamp2)
     * Returns the difference between 2 Time Stamps in Days
     * @param int $timeStamp1
     * @param int $timeStamp2
     * @return int
     */
    public static function getDaysDiff(int $timeStamp1, int $timeStamp2): int {
        return (int)floor(abs($timeStamp1 - $timeStamp2) / (24 * 3600));
    }

    /**
     * Date::createOrNow($timeStamp)->getDayName()
     * Returns the Day name at the given Time Stamp
     * @param int        $timeStamp   Optional.
     * @param bool       $startMonday Optional.
     * @param float|null $timeZone    Optional.
     * @param string     $language    Optional.
     * @return string
     */
    public static function getDayText(
        int $timeStamp = 0,
        bool $startMonday = false,
        ?float $timeZone = null,
        string $language = "",
    ): string {
        $dayOfWeek = self::getDayOfWeek($timeStamp, $startMonday, $timeZone);
        return DateUtils::getDayName($dayOfWeek, $startMonday, language: $language);
    }

    /**
     * Returns the Day and Hour for the given Time Stamp
     * @param int        $timeStamp
     * @param bool       $startMonday Optional.
     * @param bool       $useToday    Optional.
     * @param float|null $timeZone    Optional.
     * @param string     $language    Optional.
     * @return string
     */
    public static function getDayHour(
        int $timeStamp = 0,
        bool $startMonday = false,
        bool $useToday = false,
        ?float $timeZone = null,
        string $language = "",
    ): string {
        if ($useToday && self::isToday($timeStamp, $timeZone)) {
            $dayName = NLS::getString("DATE_TIME_TODAY", $language);
        } else {
            $dayName = self::getDayText($timeStamp, $startMonday, $timeZone, $language);
        }

        $hour = self::toString($timeStamp, DateFormat::Time, $timeZone);
        return NLS::format("DATE_TIME_DAY_HOUR", [ $dayName, $hour ], $language);
    }

    /**
     * Date::createOrNow($timeStamp)->getDayMonth()
     * Returns the Day and Month for the given Time Stamp
     * @param int    $timeStamp
     * @param int    $length      Optional.
     * @param bool   $inUpperCase Optional.
     * @param string $language    Optional.
     * @return string
     */
    public static function getDayMonth(
        int $timeStamp,
        int $length = 0,
        bool $inUpperCase = false,
        string $language = "",
    ): string {
        $day   = self::getDayZero($timeStamp);
        $month = self::getMonth($timeStamp);
        return "$day " . DateUtils::getMonthName($month, $length, $inUpperCase, $language);
    }



    /**
     * Date::createOrNow($timeStamp)->getHour()
     * Returns the Hour of the given Time Stamp
     * @param int $timeStamp Optional.
     * @return int
     */
    public static function getHour(int $timeStamp = 0): int {
        $timeStamp = self::getTime($timeStamp);
        return (int)date("G", $timeStamp);
    }

    /**
     * Date::createOrNow($timeStamp)->add(hours: $hourDiff)
     * Adds the given Hours to the given Time Stamp
     * @param int  $timeStamp   Optional.
     * @param int  $hourDiff    Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function addHours(int $timeStamp = 0, int $hourDiff = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $result    = $timeStamp + $hourDiff * 3600;
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp1)->getHoursDiff($timeStamp2)
     * Returns the difference between 2 Time Stamps in Hours
     * @param int $timeStamp1
     * @param int $timeStamp2
     * @return int
     */
    public static function getHoursDiff(int $timeStamp1, int $timeStamp2): int {
        return (int)floor(abs($timeStamp1 - $timeStamp2) / 3600);
    }



    /**
     * Date::createOrNow($timeStamp)->add(minutes: $minuteDiff)
     * Adds the given Minutes to the given Time Stamp
     * @param int  $timeStamp   Optional.
     * @param int  $minuteDiff  Optional.
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public static function addMinutes(int $timeStamp = 0, int $minuteDiff = 0, bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $result    = $timeStamp + $minuteDiff * 60;
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Date::createOrNow($timeStamp1)->set($timeDiff)
     * Adds the given Time to the given Time Stamp
     * @param int    $timeStamp   Optional.
     * @param string $timeDiff    Optional.
     * @param bool   $useTimeZone Optional.
     * @return int
     */
    public static function addTime(int $timeStamp = 0, string $timeDiff = "", bool $useTimeZone = false): int {
        $timeStamp = self::getTime($timeStamp);
        $minutes   = self::timeToMinutes($timeDiff);
        $result    = $timeStamp + $minutes * 60;
        return TimeZone::toServerTime($result, $useTimeZone);
    }

    /**
     * Converts an Hour and Minute to Minutes
     * @param int|null   $hours    Optional.
     * @param int|null   $minutes  Optional.
     * @param float|null $timeZone Optional.
     * @return int
     */
    public static function toMinutes(?int $hours = null, ?int $minutes = null, ?float $timeZone = null): int {
        if ($hours === null || $minutes === null) {
            $result = self::getHour() * 60 + (int)date("i");
        } else {
            $result = $hours * 60 + $minutes;
        }
        if ($timeZone !== null) {
            $timeDiff = TimeZone::calcTimeDiff($timeZone);
            $result  += Numbers::roundInt($timeDiff * 60);
        }
        return $result;
    }

    /**
     * Date::createOrNow($timeStamp)->toMinutes()
     * Converts a Time Stamp to Minutes
     * @param int        $timeStamp
     * @param float|null $timeZone  Optional.
     * @return int
     */
    public static function timeStampToMinutes(int $timeStamp, ?float $timeZone = null): int {
        $timeStamp = self::getTime($timeStamp);
        $hours     = self::getHour($timeStamp);
        $minutes   = (int)date("i", $timeStamp);
        return self::toMinutes($hours, $minutes, $timeZone);
    }

    /**
     * DateUtils::timeToMinutes($time)
     * Converts a Time to Minutes
     * @param string     $time
     * @param float|null $timeZone Optional.
     * @return int
     */
    public static function timeToMinutes(string $time, ?float $timeZone = null): int {
        $parts = Strings::split($time, ":");
        if (count($parts) !== 2) {
            return 0;
        }
        return self::toMinutes((int)$parts[0], (int)$parts[1], $timeZone);
    }

    /**
     * DateUtils::minutesToTime($minutes)
     * Converts the Minutes to a Time
     * @param int $minutes
     * @return string
     */
    public static function minutesToTime(int $minutes): string {
        $hours = floor($minutes / 60);
        $mins  = $minutes - $hours * 60;
        return self::parseTime($hours) . ":" . self::parseTime($mins);
    }

    /**
     * Date::createOrNow($timeStamp1)->getMinsDiff($timeStamp2)
     * Returns the difference between 2 Time Stamps in Minutes
     * @param int $timeStamp1
     * @param int $timeStamp2
     * @return int
     */
    public static function getMinsDiff(int $timeStamp1, int $timeStamp2): int {
        return (int)floor(abs($timeStamp1 - $timeStamp2) / 60);
    }



    /**
     * Date::createOrNow($timeStamp)->getAge($nowTimeStamp)
     * Returns the amount of years between given date and today AKA the age
     * @param mixed      $ageTime
     * @param mixed|null $nowTime  Optional.
     * @param float|null $timeZone Optional.
     * @return int
     */
    public static function getAge(mixed $ageTime, mixed $nowTime = null, ?float $timeZone = null): int {
        $ageTimeStamp = self::toTimeZone($ageTime, $timeZone);
        $nowTimeStamp = $nowTime !== null ? self::toTimeZone($nowTime, $timeZone) : time();
        if ($ageTimeStamp === 0) {
            return 0;
        }

        $ageYear  = self::getYear($ageTimeStamp);
        $ageMonth = self::getMonth($ageTimeStamp);
        $ageDay   = self::getDay($ageTimeStamp);

        $nowYear  = self::getYear($nowTimeStamp);
        $nowMonth = self::getMonth($nowTimeStamp);
        $nowDay   = self::getDay($nowTimeStamp);

        $result   = $nowYear - $ageYear;
        if ($ageMonth > $nowMonth || ($ageMonth === $nowMonth && $ageDay > $nowDay)) {
            $result -= 1;
        }
        return $result;
    }

    /**
     * Returns a number as a String with a 0 in front, if required
     * @param int|float $time
     * @return string
     */
    public static function parseTime(int|float $time): string {
        return $time < 10 ? "0{$time}" : (string)$time;
    }
}
