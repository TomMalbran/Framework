<?php
namespace Framework\Date;

use Framework\Intl\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * Several Date Util functions
 */
class DateUtils {

    /**
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

        $hours   = (int)$parts[0];
        $minutes = (int)$parts[1];
        $result  = $hours * 60 + $minutes;

        if ($timeZone !== null) {
            $timeDiff = TimeZone::calcTimeDiff($timeZone);
            $result  += Numbers::roundInt($timeDiff * 60);
        }
        return $result;
    }

    /**
     * Converts the Minutes to a Time
     * @param int $minutes
     * @return string
     */
    public static function minutesToTime(int $minutes): string {
        $hours = floor($minutes / 60);
        $mins  = $minutes - $hours * 60;
        return Numbers::zerosPad($hours, 2) . ":" . Numbers::zerosPad($mins, 2);
    }



    /**
     * Returns true if the given date is Valid
     * @param string $string
     * @return bool
     */
    public static function isValidDate(string $string): bool {
        return strtotime($string) !== false;
    }

    /**
     * Returns true if the given day is Valid
     * @param string|int $value
     * @param bool       $withHolidays Optional.
     * @param bool       $startMonday  Optional.
     * @return bool
     */
    public static function isValidDay(string|int $value, bool $withHolidays = false, bool $startMonday = false): bool {
        $minValue = $startMonday ? 1 : 0;
        $maxValue = ($withHolidays ? 7 : 6) + $minValue;
        return (int)$value >= $minValue && (int)$value <= $maxValue;
    }

    /**
     * Returns true if the given hour is Valid
     * @param string     $string
     * @param int[]|null $minutes Optional.
     * @param int        $minHour Optional.
     * @param int        $maxHour Optional.
     * @return bool
     */
    public static function isValidHour(
        string $string,
        ?array $minutes = null,
        int $minHour = 0,
        int $maxHour = 23,
    ): bool {
        $parts = Strings::split($string, ":");
        return (
            isset($parts[0]) && Numbers::isValid($parts[0], $minHour, $maxHour) &&
            isset($parts[1]) && Numbers::isValid($parts[1], 0, 59) &&
            ($minutes === null || Arrays::contains($minutes, $parts[1]))
        );
    }

    /**
     * Returns true if the given dates are a valid period
     * @param string $fromDate
     * @param string $toDate
     * @return bool
     */
    public static function isValidPeriod(string $fromDate, string $toDate): bool {
        $fromTime = Date::create($fromDate)->toDayStart();
        $toTime   = Date::create($toDate)->toDayEnd();
        return (
            $fromTime->isNotEmpty() &&
            $toTime->isNotEmpty() &&
            $fromTime->isBefore($toTime)
        );
    }

    /**
     * Returns true if the given hours are a valid period
     * @param string $fromHour
     * @param string $toHour
     * @param bool   $allow24
     * @return bool
     */
    public static function isValidHourPeriod(string $fromHour, string $toHour, bool $allow24 = false): bool {
        $fromMinutes = self::timeToMinutes($fromHour);
        $toMinutes   = self::timeToMinutes($toHour);

        if ($allow24 && $fromMinutes > 0 && $toMinutes === 1440) {
            return true;
        }
        if ($fromMinutes !== 0 && $toMinutes !== 0 && $fromMinutes < $toMinutes) {
            return true;
        }
        return false;
    }

    /**
     * Returns true if the given dates with hours are a valid period
     * @param string $fromDate
     * @param string $fromHour
     * @param string $toDate
     * @param string $toHour
     * @return bool
     */
    public static function isValidFullPeriod(
        string $fromDate,
        string $fromHour,
        string $toDate,
        string $toHour,
    ): bool {
        $fromTime = Date::create($fromDate, $fromHour);
        $toTime   = Date::create($toDate, $toHour);
        return (
            $fromTime->isNotEmpty() &&
            $toTime->isNotEmpty() &&
            $fromTime->isBefore($toTime)
        );
    }

    /**
     * Returns true if the given week day is valid
     * @param int  $weekDay
     * @param bool $startMonday Optional.
     * @return bool
     */
    public static function isValidWeekDay(int $weekDay, bool $startMonday = false): bool {
        if ($startMonday) {
            return Numbers::isValid($weekDay, 1, 7);
        }
        return Numbers::isValid($weekDay, 0, 6);
    }



    /**
     * Returns the Day name at the given Day
     * @param int    $day
     * @param bool   $startMonday Optional.
     * @param int    $length      Optional.
     * @param bool   $inUpperCase Optional.
     * @param string $language    Optional.
     * @return string
     */
    public static function getDayName(
        int $day,
        bool $startMonday = false,
        int $length = 0,
        bool $inUpperCase = false,
        string $language = "",
    ): string {
        $key    = $startMonday ? "DATE_TIME_DAYS_MONDAY" : "DATE_TIME_DAYS";
        $result = NLS::getIndex($key, $day, $language);

        if ($length > 0) {
            $result = Strings::substring($result, 0, $length, true);
        }
        if ($inUpperCase) {
            $result = Strings::toUpperCase($result);
        }
        return $result;
    }

    /**
     * Returns the Month name for the given Month
     * @param int    $month
     * @param int    $length      Optional.
     * @param bool   $inUpperCase Optional.
     * @param string $language    Optional.
     * @return string
     */
    public static function getMonthName(
        int $month,
        int $length = 0,
        bool $inUpperCase = false,
        string $language = "",
    ): string {
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
     * Parses a text into a Time Stamp
     * @param string $text
     * @param string $language Optional.
     * @return int
     */
    public static function parseDate(string $text, string $language = ""): int {
        $glue = "";
        if (Strings::contains($text, "/")) {
            $glue = "/";
        } elseif (Strings::contains($text, "-")) {
            $glue = "-";
        }

        if ($glue !== "") {
            return self::parseDateGlue($text, $glue);
        }
        return self::parseDateLang($text, $language);
    }

    /**
     * Parses a text with the given glue into a Time Stamp
     * @param string $text
     * @param string $glue
     * @return int
     */
    private static function parseDateGlue(string $text, string $glue): int {
        $parts  = Strings::split($text, $glue);
        $amount = count($parts);
        $part0  = isset($parts[0]) ? (int)$parts[0] : 0;
        $part1  = isset($parts[1]) ? (int)$parts[1] : 0;
        $part2  = isset($parts[2]) ? (int)$parts[2] : 0;

        // We need at least 2 parts
        if ($part0 === 0 || $part1 === 0) {
            return 0;
        }

        // Start with the current year and the given month and day
        $day   = $part0;
        $month = $part1;
        $year  = (int)date("Y");

        // Invert the order
        if ($amount === 3 && $part0 > 1000) {
            $day   = $part2;
            $month = $part1;
            $year  = $part0;
        } else {
            // Handle invalid months
            if ($month > 12) {
                $monthStr = (string)$month;
                switch (Strings::length($monthStr)) {
                case 3:
                    $month = (int)Strings::substring($monthStr, 0, 1);
                    $part2 = Strings::substring($monthStr, 1, 3);
                    break;
                case 4:
                    $month = (int)Strings::substring($monthStr, 0, 2);
                    $part2 = Strings::substring($monthStr, 2, 4);
                    break;
                case 5:
                    $month = (int)Strings::substring($monthStr, 0, 1);
                    $year  = (int)Strings::substring($monthStr, 1, 5);
                    $part2 = 0;
                    break;
                case 6:
                    $month = (int)Strings::substring($monthStr, 0, 2);
                    $year  = (int)Strings::substring($monthStr, 2, 6);
                    $part2 = 0;
                    break;
                default:
                }
            }

            // Handle the Year
            if ($part2 !== 0 && $part2 !== "") {
                $yearStr = trim((string)$part2);
                if (Strings::length($yearStr) === 2) {
                    if ((int)$yearStr >= 50) {
                        $year = (int)"19$yearStr";
                    } else {
                        $year = (int)"20$yearStr";
                    }
                } else {
                    $year = (int)$yearStr;
                }
            }
        }

        // Something is still wrong
        if ($month > 12 || $year > 2100) {
            return 0;
        }

        // Return the Time Stamp
        $result = mktime(0, 0, 0, $month, $day, $year);
        if ($result === false) {
            return 0;
        }
        return $result;
    }

    /**
     * Parses a text with a language into a Time Stamp
     * @param string $text
     * @param string $language
     * @return int
     */
    private static function parseDateLang(string $text, string $language): int {
        $year  = (int)date("Y");
        $month = 0;
        $day   = 0;

        $numbers = Strings::getAllMatches($text, "!\d+!");
        $numbers = Arrays::toInts($numbers);
        if (count($numbers) === 0) {
            return 0;
        }

        $monthNames = NLS::getList("DATE_TIME_MONTHS", $language);
        foreach ($monthNames as $index => $monthName) {
            if (Strings::containsCaseInsensitive($text, $monthName, Strings::substring($monthName, 0, 3))) {
                $month = (int)$index + 1;
                break;
            }
        }
        if ($month === 0) {
            return 0;
        }

        foreach ($numbers as $number) {
            if ($number <= 0) {
                continue;
            }
            if ($day === 0 && $number <= 31) {
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

        if ($day === 0 || $year > 2100) {
            return 0;
        }

        $result = mktime(0, 0, 0, $month, $day, $year);
        if ($result === false) {
            return 0;
        }
        return $result;
    }
}
