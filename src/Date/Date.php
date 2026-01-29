<?php
namespace Framework\Date;

use Framework\Date\DateTime;
use Framework\Date\DateFormat;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * Date class
 */
class Date {

    private int $timeStamp = 0;


    /**
     * Creates a new Date instance
     * @param mixed $value Optional.
     */
    public function __construct(mixed $value = null) {
        if ($value instanceof Date) {
            $this->timeStamp = $value->getTimeStamp();
        } elseif (Numbers::isValid($value)) {
            $this->timeStamp = Numbers::toInt($value);
        } elseif (is_string($value)) {
            $timeStamp = strtotime($value);
            if ($timeStamp !== false) {
                $this->timeStamp = $timeStamp;
            }
        }

        // If no valid timestamp and value is not null, set to current time
        if ($value !== null && $this->timeStamp <= 0) {
            $this->timeStamp = time();
        }
    }

    /**
     * Creates a new Date instance
     * @param mixed $value Optional.
     * @return Date
     */
    public static function create(mixed $value = null): Date {
        return new Date($value);
    }

    /**
     * Create a Date instance for the current time and adding the given amounts
     * @param int $months  Optional.
     * @param int $days    Optional.
     * @param int $hours   Optional.
     * @param int $minutes Optional.
     * @return Date
     */
    public static function now(
        int $months = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
    ): Date {
        // Start with current time or adjusted month
        if ($months !== 0) {
            $time = mktime(date("H"), month: date("m") + $months);
        } else {
            $time = time();
        }

        // Add days, hours and minutes
        $time += $days * 86400;
        $time += $hours * 3600;
        $time += $minutes * 60;
        return new Date($time);
    }

    /**
     * Creates a Date instance parsing the given string
     * @param string $text
     * @param string $language Optional.
     * @return Date
     */
    public static function parse(string $text, string $language = ""): Date {
        $dateTime = DateTime::parseDate($text, $language);
        return new Date($dateTime);
    }

    /**
     * Creates a Date instance from the given date string
     * @param string $dateString
     * @param string $hourString Optional.
     * @return Date
     */
    public static function fromString(string $dateString, string $hourString = ""): Date {
        return new Date("$dateString $hourString");
    }

    /**
     * Creates a Date instance from the given date and time parts
     * @param int $day
     * @param int $month
     * @param int $year
     * @param int $hour   Optional.
     * @param int $minute Optional.
     * @param int $second Optional.
     * @return Date
     */
    public static function createTime(
        int $day,
        int $month,
        int $year,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
    ): Date {
        $time = mktime($hour, $minute, $second, $month, $day, $year);
        return new Date($time);
    }



    /**
     * Returns a new Date changing the current one with the given values
     * @param int|null $day    Optional.
     * @param int|null $month  Optional.
     * @param int|null $year   Optional.
     * @param int|null $hour   Optional.
     * @param int|null $minute Optional.
     * @param int|null $second Optional.
     * @return Date
     */
    private function changeDate(
        ?int $day = null,
        ?int $month = null,
        ?int $year = null,
        ?int $hour = null,
        ?int $minute = null,
        ?int $second = null,
    ): Date {
        $time = mktime(
            $hour   !== null ? $hour   : $this->getHour(),
            $minute !== null ? $minute : $this->getMinute(),
            $second !== null ? $second : $this->getSecond(),
            $month  !== null ? $month  : $this->getMonth(),
            $day    !== null ? $day    : $this->getDay(),
            $year   !== null ? $year   : $this->getYear(),
        );
        return new Date($time);
    }

    /**
     * Returns a new Date instance moving the day by the given amount
     * @param int $days
     * @return Date
     */
    public function moveDay(int $days): Date {
        $currentDay = $this->getDay();
        return $this->changeDate(day: $currentDay + $days);
    }

    /**
     * Returns a new Date instance moving the day by the given amount of weeks
     * @param int $weeks
     * @return Date
     */
    public function moveWeek(int $weeks): Date {
        return $this->moveDay($weeks * 7);
    }

    /**
     * Returns a new Date instance moving the month by the given amount
     * @param int $months
     * @return Date
     */
    public function moveMonth(int $months): Date {
        $currentMonth = $this->getMonth();
        return $this->changeDate(month: $currentMonth + $months);
    }

    /**
     * Returns a new Date instance moving the year by the given amount
     * @param int $years
     * @return Date
     */
    public function moveYear(int $years): Date {
        $currentYear = $this->getYear();
        return $this->changeDate(year: $currentYear + $years);
    }

    /**
     * Returns a new Date instance moving the hour by the given amount
     * @param int $hours
     * @return Date
     */
    public function moveHour(int $hours): Date {
        $currentHour = $this->getHour();
        return $this->changeDate(hour: $currentHour + $hours);
    }

    /**
     * Returns a new Date instance moving the minute by the given amount
     * @param int $minutes
     * @return Date
     */
    public function moveMinute(int $minutes): Date {
        $currentMinute = $this->getMinute();
        return $this->changeDate(minute: $currentMinute + $minutes);
    }



    /**
     * Returns a Date instance set to the start of the day
     * @return Date
     */
    public function toDayStart(): Date {
        return $this->changeDate(hour: 0, minute: 0, second: 0);
    }

    /**
     * Returns a Date instance set to the middle of the day
     * @return Date
     */
    public function toDayMiddle(): Date {
        return $this->changeDate(hour: 12, minute: 0, second: 0);
    }

    /**
     * Returns a Date instance set to the end of the day
     * @return Date
     */
    public function toDayEnd(): Date {
        return $this->changeDate(hour: 23, minute: 59, second: 59);
    }

    /**
     * Returns a Date instance set to the start of the week
     * @param bool $startMonday Optional.
     * @return Date
     */
    public function toWeekStart(bool $startMonday = false): Date {
        $dayOfWeek = $this->getDayOfWeek($startMonday);
        return $this->moveDay(-$dayOfWeek);
    }

    /**
     * Returns a Date instance set to the end of the week
     * @param bool $startMonday Optional.
     * @return Date
     */
    public function toWeekEnd(bool $startMonday = false): Date {
        $dayOfWeek = $this->getDayOfWeek($startMonday);
        return $this->moveDay(6 - $dayOfWeek);
    }

    /**
     * Returns a Date instance set to the start of the month
     * @return Date
     */
    public function toMonthStart(): Date {
        return $this->changeDate(day: 1);
    }

    /**
     * Returns a Date instance set to the end of the month
     * @return Date
     */
    public function toMonthEnd(): Date {
        $monthDays = $this->getMonthDays();
        return $this->changeDate(day: $monthDays);
    }

    /**
     * Returns a Date instance set to the start of the year
     * @return Date
     */
    public function toYearStart(): Date {
        return $this->changeDate(day: 1, month: 1);
    }

    /**
     * Returns a Date instance set to the end of the year
     * @return Date
     */
    public function toYearEnd(): Date {
        return $this->changeDate(day: 31, month: 12);
    }



    /**
     * Returns true if the Date is empty (time stamp is 0 or less)
     * @return bool
     */
    public function isEmpty(): bool {
        return $this->timeStamp <= 0;
    }

    /**
     * Returns the Time Stamp
     * @return int
     */
    public function getTimeStamp(): int {
        return $this->timeStamp;
    }

    /**
     * Returns the Time Stamp in Server Time
     * @param bool $useTimeZone Optional.
     * @return int
     */
    public function toServerTime(bool $useTimeZone = true): int {
        return DateTime::toServerTime($this->timeStamp, $useTimeZone);
    }

    /**
     * Returns the Day, Month and Year as a number
     * @return int
     */
    public function toNumber(): int {
        return (
            $this->getYear() * 10000 +
            $this->getMonth() * 100 +
            $this->getDay()
        );
    }

    /**
     * Returns the Hours and Minutes as total Minutes
     * @return int
     */
    public function toMinutes(): int {
        return $this->getHour() * 60 + $this->getMinute();
    }



    /**
     * Returns the Year
     * @return int
     */
    public function getYear(): int {
        return (int)date("Y", $this->timeStamp);
    }

    /**
     * Returns the amount of days in the Year
     * @return int
     */
    public function getYearDays(): int {
        // NOTE: L returns 1 for leap years and 0 for non-leap years
        return 365 + (int)date("L", $this->timeStamp);
    }



    /**
     * Returns the Month number
     * @return int
     */
    public function getMonth(): int {
        return (int)date("n", $this->timeStamp);
    }

    /**
     * Returns the Month with leading zero (01 to 12)
     * @return string
     */
    public function getMonthZero(): string {
        return date("m", $this->timeStamp);
    }

    /**
     * Returns the amount of days in the Month
     * @return int
     */
    public function getMonthDays(): int {
        return (int)date("t", $this->timeStamp);
    }

    /**
     * Returns the name of the current Month
     * @param int    $length      Optional.
     * @param bool   $inUpperCase Optional.
     * @param string $language    Optional.
     * @return string
     */
    public function getMonthName(
        int $length = 0,
        bool $inUpperCase = false,
        string $language = "",
    ): string {
        return DateTime::getMonthName(
            month:       $this->getMonth(),
            length:      $length,
            inUpperCase: $inUpperCase,
            language:    $language,
        );
    }



    /**
     * Returns the Day of the month
     * @return int
     */
    public function getDay(): int {
        return (int)date("j", $this->timeStamp);
    }

    /**
     * Returns the Day with leading 0 (01 to 31)
     * @return string
     */
    public function getDayZero(): string {
        return date("d", $this->timeStamp);
    }

    /**
     * Returns the Day of the week
     * @param bool $startMonday Optional.
     * @return int
     */
    public function getDayOfWeek(bool $startMonday = false): int {
        if ($startMonday) {
            return (int)date("N", $this->timeStamp) - 1;
        }
        return (int)date("w", $this->timeStamp);
    }

    /**
     * Returns the name of the current Day of the week
     * @param bool   $startMonday Optional.
     * @param int    $length      Optional.
     * @param bool   $inUpperCase Optional.
     * @param string $language    Optional.
     * @return string
     */
    public function getDayName(
        bool $startMonday = false,
        int $length = 0,
        bool $inUpperCase = false,
        string $language = "",
    ): string {
        return DateTime::getDayName(
            day:         $this->getDayOfWeek($startMonday),
            startMonday: $startMonday,
            length:      $length,
            inUpperCase: $inUpperCase,
            language:    $language,
        );
    }



    /**
     * Returns the Hour
     * @return int
     */
    public function getHour(): int {
        return (int)date("G", $this->timeStamp);
    }

    /**
     * Returns the Minute
     * @return int
     */
    public function getMinute(): int {
        return (int)date("i", $this->timeStamp);
    }

    /**
     * Returns the Second
     * @return int
     */
    public function getSecond(): int {
        return (int)date("s", $this->timeStamp);
    }



    /**
     * Returns true if the current Date is today
     * @return bool
     */
    public function isToday(): bool {
        $now = Date::now();
        return (
            $this->getYear()  === $now->getYear() &&
            $this->getMonth() === $now->getMonth() &&
            $this->getDay()   === $now->getDay()
        );
    }

    /**
     * Returns true if the current Date is in the past
     * @return bool
     */
    public function isPast(): bool {
        return $this->timeStamp < time();
    }

    /**
     * Returns true if the current Date is in the future
     * @return bool
     */
    public function isFuture(): bool {
        return $this->timeStamp > time();
    }

    /**
     * Returns true if the current Date is before another Date
     * @param Date $date
     * @return bool
     */
    public function isBefore(Date $date): bool {
        return $this->timeStamp < $date->getTimeStamp();
    }

    /**
     * Returns true if the current Date is after another Date
     * @param Date $date
     * @return bool
     */
    public function isAfter(Date $date): bool {
        return $this->timeStamp > $date->getTimeStamp();
    }

    /**
     * Returns true if the current Date is between two other Dates
     * @param Date $from
     * @param Date $to
     * @return bool
     */
    public function isBetween(Date $from, Date $to): bool {
        return $this->timeStamp >= $from->getTimeStamp() && $this->timeStamp <= $to->getTimeStamp();
    }

    /**
     * Returns true if the current Date is in the current Month
     * @return bool
     */
    public function isCurrentMonth(): bool {
        $now = Date::now();
        return (
            $this->getYear()  === $now->getYear() &&
            $this->getMonth() === $now->getMonth()
        );
    }

    /**
     * Returns the difference in days between the current Date and another Date
     * @param Date $date
     * @return int
     */
    public function getDaysDiff(Date $date): int {
        $diffSeconds = abs($this->timeStamp - $date->getTimeStamp());
        return (int)floor($diffSeconds / 86400);
    }

    /**
     * Returns the difference in hours between the current Date and another Date
     * @param Date $date
     * @return int
     */
    public function getHoursDiff(Date $date): int {
        $diffSeconds = abs($this->timeStamp - $date->getTimeStamp());
        return (int)floor($diffSeconds / 3600);
    }

    /**
     * Returns the difference in minutes between the current Date and another Date
     * @param Date $date
     * @return int
     */
    public function getMinutesDiff(Date $date): int {
        $diffSeconds = abs($this->timeStamp - $date->getTimeStamp());
        return (int)floor($diffSeconds / 60);
    }

    /**
     * Returns the Age in years from the current Date to another Date
     * @param Date|null $now Optional.
     * @return int
     */
    public function getAge(?Date $now = null): int {
        if ($now === null) {
            $now = Date::now();
        }

        $ageYear  = $this->getYear();
        $ageMonth = $this->getMonth();
        $ageDay   = $this->getDay();

        $nowYear  = $now->getYear();
        $nowMonth = $now->getMonth();
        $nowDay   = $now->getDay();

        $result   = $nowYear - $ageYear;
        if ($ageMonth > $nowMonth || ($ageMonth === $nowMonth && $ageDay > $nowDay)) {
            $result -= 1;
        }
        return $result;
    }



    /**
     * Returns the Date as a string using the given format
     * @param string $format
     * @return string
     */
    public function format(string $format): string {
        return date($format, $this->timeStamp);
    }

    /**
     * Returns the Date as a string using the given DateFormat
     * @param DateFormat $format
     * @return string
     */
    public function toString(DateFormat $format): string {
        return $this->format($format->value);
    }

    /**
     * Returns the Date as an ISO 8601 string
     * @return string
     */
    public function toISOString(): string {
        return $this->format("c");
    }

    /**
     * Returns the Date as a UTC date string
     * @return string
     */
    public function toUTCString(): string {
        return Strings::replace($this->format("c"), "-03:00", "Z");
    }
}
