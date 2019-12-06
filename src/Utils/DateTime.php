<?php
namespace Framework\Utils;

use Framework\Utils\Strings;
use Framework\Utils\Utils;

/**
 * Several Date Time functions
 */
class DateTime {

    public static $months     = [ "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre" ];

    public static $serverZone = -3;
    public static $timeDiff   = 0;


    /**
     * Sets the Time Zone
     * @param integer $timeZone
     * @return void
     */
    public static function setTimeZone($timeZone) {
        self::$timeDiff = self::$serverZone - $timeZone;
    }

    /**
     * Returns the given time in the User Time Zone
     * @param integer $value
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toUserTime($value, $useTimeZone = true) {
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
    public static function toServerTime($value, $useTimeZone = true) {
        if (!empty($value) && $useTimeZone) {
            return $value + (self::$timeDiff * 3600);
        }
        return $value;
    }



    /**
     * Returns the Server Date
     * @return integer
     */
    public static function getServerDate() {
        return date("d-m-Y @ H:i", time());
    }

    /**
     * Returns the User Date
     * @return integer
     */
    public static function getUserDate() {
        return date("d-m-Y @ H:i", self::toUserTime(time()));
    }



    /**
     * Returns the given string as a time
     * @param string  $string
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toTime($string, $useTimeZone = true) {
        $result = strtotime($string);
        if ($result !== false) {
            return self::toServerTime($result, $useTimeZone);
        }
        return 0;
    }

    /**
     * Returns the given string as a time of the start of the day
     * @param string  $string
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toDayStart($string, $useTimeZone = true) {
        $result = strtotime($string);
        if ($result !== false) {
            return self::toServerTime($result, $useTimeZone);
        }
        return 0;
    }
    
    /**
     * Returns the given string as a time of the end of the day
     * @param string  $string
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toDayEnd($string, $useTimeZone = true) {
        $result = strtotime($string);
        if ($result !== false) {
            $result = $result + 24 * 3600 - 1;
            return self::toServerTime($result, $useTimeZone);
        }
        return 0;
    }

    /**
     * Returns the given string as a time
     * @param string  $dateString
     * @param string  $hourString
     * @param boolean $useTimeZone Optional.
     * @return integer
     */
    public static function toTimeHour($dateString, $hourString, $useTimeZone = true) {
        $result = strtotime($dateString . " " . $hourString);
        if ($result !== false) {
            return self::toServerTime($result, $useTimeZone);
        }
        return 0;
    }



    /**
     * Returns true if the given date is Valid
     * @param string $string
     * @return boolean
     */
    public static function isValidDate($string) {
        return strtotime($string) !== false;
    }
    
    /**
     * Returns true if the given hour is Valid
     * @param string $string
     * @param array  $minutes Optional.
     * @return boolean
     */
    public static function isValidHour($string, array $minutes = null) {
        $parts = Strings::split($string, ":");
        return (
            !empty($parts[0]) && Utils::isNumeric($parts[0], 0, 23) &&
            !empty($parts[1]) && Utils::isNumeric($parts[1], 0, 59) &&
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
    public static function isValidPeriod($fromDate, $toDate, $useTimeZone = true) {
        $fromTime = self::toDayStart($fromDate, $useTimeZone);
        $toTime   = self::toDayEnd($toDate, $useTimeZone);
        
        return $fromTime !== null && $toTime !== null && $fromTime < $toTime;
    }

    /**
     * Returns true if the given hours are a valid period
     * @param string $fromHour
     * @param string $toHour
     * @return boolean
     */
    public static function isValidHourPeriod($fromHour, $toHour) {
        $date     = date("d-m-Y");
        $fromTime = self::toHour($date, $fromHour);
        $toTime   = self::toHour($date, $toHour);
        
        return $fromTime !== 0 && $toTime !== 0 && $fromTime < $toTime;
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
    public static function isValidFullPeriod($fromDate, $fromHour, $toDate, $toHour, $useTimeZone = true) {
        $fromTime = self::toHour($fromDate, $fromHour, $useTimeZone);
        $toTime   = self::toHour($toDate, $toHour, $useTimeZone);
        
        return $fromTime !== 0 && $toTime !== 0 && $fromTime < $toTime;
    }

    /**
     * Returns true if the given week day is valid
     * @param integer $weekDay
     * @return boolean
     */
    public static function isValidWeekDay($weekDay) {
        return Utils::isNumeric((int)$weekDay, 0, 6);
    }
    


    /**
     * Returns the Seconds as a string
     * @param integer $seconds
     * @return string
     */
    public static function toTimeString($seconds) {
        $secondsInAMinute = 60;
        $secondsInAnHour  = 60 * $secondsInAMinute;
        $secondsInADay    = 24 * $secondsInAnHour;
        $secondsInAWeek   = 7  * $secondsInADay;

        // Extract the Weeks
        $weeks       = floor($seconds / $secondsInAWeek);
        
        // Extract the Days
        $daySeconds  = $seconds % $secondsInAWeek;
        $days        = floor($daySeconds / $secondsInADay);

        // Extract the Hours
        $hourSeconds = $daySeconds % $secondsInADay;
        $hours       = floor($hourSeconds / $secondsInAnHour);
        
        // Extract the Minutes
        $minSeconds  = $daySeconds % $secondsInAnHour;
        $mins        = floor($minSeconds / $secondsInAMinute);
        
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
    public static function toDayString($seconds) {
        $secondsInADay = 24 * 3600;
        $days          = floor($seconds / $secondsInADay);
        return "{$days}d";
    }



    /**
     * Returns the difference between 2 dates in Months
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getMonthsDiff($time1, $time2) {
        return 12 * (date("Y", $time1) - date("Y", $time2)) + date("n", $time1) - date("n", $time2);
    }

    /**
     * Returns the difference between 2 dates in Weeks
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getWeeksDiff($time1, $time2) {
        return floor(($time1 - $time2) / (7 * 24 * 3600));
    }
    
    /**
     * Returns the difference between 2 dates in Days
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getDaysDiff($time1, $time2) {
        return floor(($time1 - $time2) / (24 * 3600));
    }

    /**
     * Returns the difference between 2 dates in Minutes
     * @param integer $time1
     * @param integer $time2
     * @return integer
     */
    public static function getMinsDiff($time1, $time2) {
        return floor(($time1 - $time2) / 60);
    }



    /**
     * Returns the Month and Year at the given month
     * @param integer $time
     * @return string
     */
    public static function getMonthYear($time) {
        return self::getMonth(date("n", $time)) . " " . date("Y", $time);
    }

    /**
     * Returns the Month at the given month
     * @param integer $month
     * @return string
     */
    public static function getMonth($month) {
        return self::$months[$month - 1];
    }
    
    /**
     * Returns a short version of the Month
     * @param integer $month
     * @return string
     */
    public static function getShortMonth($month) {
        $result = self::getMonth($month);
        $result = Strings::substring($result, 0, 3);
        return Strings::toUpperCase($result);
    }
}
