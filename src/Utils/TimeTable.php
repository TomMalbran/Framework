<?php
namespace Framework\Utils;

use Framework\NLS\NLS;
use Framework\Utils\DateTime;
use Framework\Utils\Errors;
use Framework\Utils\JSON;

/**
 * Several Time Table functions
 */
class TimeTable {

    /**
     * Validates the Time Tables and returns the values as a JSON
     * @param array{}[]     $timeTables
     * @param Errors        $errors
     * @param boolean       $withHolidays Optional.
     * @param boolean       $startMonday  Optional.
     * @param string        $fieldKey     Optional.
     * @param callable|null $callback     Optional.
     * @return string
     */
    public static function validate(
        array $timeTables,
        Errors $errors,
        bool $withHolidays = false,
        bool $startMonday = false,
        string $fieldKey = "timeTables",
        ?callable $callback = null
    ): string {
        $result = [];

        foreach ($timeTables as $index => $timeTable) {
            $hasError = false;
            if (empty($timeTable["days"])) {
                continue;
            }

            foreach ($timeTable["days"] as $day) {
                if (!DateTime::isValidDay($day, $withHolidays, $startMonday)) {
                    $errors->add("$fieldKey-$index-days", "GENERAL_ERROR_PERIOD_DAYS_INVALID");
                    $hasError = true;
                    break;
                }
            }
            if (empty($timeTable["from"]) || !DateTime::isValidHour($timeTable["from"])) {
                $errors->add("$fieldKey-$index-from", "GENERAL_ERROR_PERIOD_FROM_TIME");
                $hasError = true;
            }
            if (empty($timeTable["to"]) || !DateTime::isValidHour($timeTable["to"])) {
                $errors->add("$fieldKey-$index-to", "GENERAL_ERROR_PERIOD_TO_TIME");
                $hasError = true;
            }
            if (!empty($timeTable["from"]) && !empty($timeTable["to"]) && !DateTime::isValidHourPeriod($timeTable["from"], $timeTable["to"], true)) {
                $errors->add("$fieldKey-$index-from", "GENERAL_ERROR_PERIOD_FROM_TO");
                $hasError = true;
            }

            if (!$hasError) {
                sort($timeTable["days"]);
                $result[] = [
                    "days" => $timeTable["days"],
                    "from" => $timeTable["from"],
                    "to"   => $timeTable["to"],
                ];
            }

            if ($callback !== null) {
                $callback($timeTable, $index);
            }
        }

        return JSON::encode($result);
    }



    /**
     * Returns true if the Time Tables are in the Current time
     * @param array{}[] $timeTables
     * @param integer   $timeStamp   Optional.
     * @param boolean   $startMonday Optional.
     * @param integer   $minuteGap   Optional.
     * @return boolean
     */
    public static function isCurrent(array $timeTables, int $timeStamp = 0, bool $startMonday = false, int $minuteGap = 0): bool {
        if (empty($timeTables)) {
            return false;
        }

        $weekDay    = DateTime::getDayOfWeek($timeStamp, $startMonday);
        $nowMinutes = DateTime::timeStampToMinutes($timeStamp);

        foreach ($timeTables as $timeTable) {
            if (!Arrays::contains($timeTable["days"], $weekDay)) {
                continue;
            }

            $fromMinutes = DateTime::timeToMinutes($timeTable["from"]);
            $toMinutes   = DateTime::timeToMinutes($timeTable["to"]) - $minuteGap;

            if ($nowMinutes >= $fromMinutes && $nowMinutes <= $toMinutes) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the end time of the current day for the given Time Tables
     * @param array{}[] $timeTables
     * @param integer   $timeStamp   Optional.
     * @param boolean   $startMonday Optional.
     * @return integer
     */
    public static function getCurrentEndTime(array $timeTables, int $timeStamp = 0, bool $startMonday = false): int {
        if (empty($timeTables)) {
            return 0;
        }

        $result     = 0;
        $weekDay    = DateTime::getDayOfWeek($timeStamp, $startMonday);
        $nowMinutes = DateTime::timeStampToMinutes($timeStamp);

        foreach ($timeTables as $timeTable) {
            if (!Arrays::contains($timeTable["days"], $weekDay)) {
                continue;
            }

            $fromMinutes = DateTime::timeToMinutes($timeTable["from"]);
            $toMinutes   = DateTime::timeToMinutes($timeTable["to"]);
            if ($nowMinutes >= $fromMinutes && $nowMinutes <= $toMinutes) {
                $result = DateTime::getDayStart($timeStamp);
                $result = DateTime::addMinutes($result, $toMinutes);
                break;
            }
        }

        return $result;
    }

    /**
     * Returns the next start time for the given Time Tables
     * @param array{}[] $timeTables
     * @param integer   $timeStamp   Optional.
     * @param boolean   $startMonday Optional.
     * @return integer
     */
    public static function getNextStartTime(array $timeTables, int $timeStamp = 0, bool $startMonday = false): int {
        if (empty($timeTables)) {
            return 0;
        }

        $maxDay    = $startMonday ? 8 : 7;
        $timeStamp = DateTime::getTime($timeStamp);
        $weekStart = DateTime::getWeekStart($timeStamp, 0, $startMonday, false);
        $result    = 0;

        foreach ($timeTables as $timeTable) {
            $fromMinutes = DateTime::timeToMinutes($timeTable["from"]);

            foreach ($timeTable["days"] as $day) {
                if ($day >= $maxDay) {
                    continue;
                }

                $newTime = DateTime::addDays($weekStart, $day);
                $newTime = DateTime::addMinutes($newTime, $fromMinutes);

                if ($newTime >= $timeStamp && ($result === 0 || $newTime <= $result)) {
                    $result = $newTime;
                }
            }
        }

        return $result;
    }



    /**
     * Returns the Time Table as list
     * @param array{}[] $timeTables
     * @param boolean   $allDays     Optional.
     * @param boolean   $startMonday Optional.
     * @param string    $closedText  Optional.
     * @param string    $timeZone    Optional.
     * @param string    $isoCode     Optional.
     * @return array{}
     */
    public static function getList(
        array $timeTables,
        bool $allDays = false,
        bool $startMonday = false,
        string $closedText = "TIME_TABLE_NO_HOURS",
        string $timeZone = "",
        string $isoCode = "",
    ): array {
        if (empty($timeTables) && !$allDays) {
            return [];
        }

        $minDay = $startMonday ? 1 : 0;
        $maxDay = $startMonday ? 8 : 7;

        $schedules = [];
        $days      = [];
        if (!empty($timeTables)) {
            foreach ($timeTables as $timeTable) {
                if (empty($timeTable["days"])) {
                    continue;
                }

                foreach ($timeTable["days"] as $day) {
                    $dayNumber = (int)$day;
                    $weekTime  = DateTime::getWeekStart(0, $day, $startMonday, true);
                    $weekDate  = DateTime::toString($weekTime, "dashes");
                    $fromTime  = DateTime::toTimeHour($weekDate, $timeTable["from"]);
                    $fromHour  = DateTime::toString($fromTime, "time");
                    $toTime    = DateTime::toTimeHour($weekDate, $timeTable["to"]);
                    $toHour    = DateTime::toString($toTime, "time");
                    $id        = "$fromHour-$toHour";

                    if (empty($schedules[$id])) {
                        $schedules[$id] = [
                            "fromHour" => $fromHour,
                            "toHour"   => $toHour,
                            "numbers"  => [],
                            "times"    => [],
                            "days"     => [],
                        ];
                    }
                    $schedules[$id]["numbers"][] = $dayNumber;
                    $schedules[$id]["times"][]   = $dayNumber < $maxDay ? $fromTime : 0;
                    $days[$dayNumber] = 1;
                }
            }
        }
        $schedules = array_values($schedules);

        if ($allDays && count($days) < $maxDay) {
            $numbers = [];
            for ($i = $minDay; $i < $maxDay; $i++) {
                if (empty($days[$i])) {
                    $numbers[] = $i;
                }
            }
            $schedules[] = [
                "fromHour" => "",
                "toHour"   => "",
                "numbers"  => $numbers,
                "times"    => [],
                "days"     => [],
            ];
        }

        foreach ($schedules as $id => $elem) {
            sort($schedules[$id]["times"]);
            sort($schedules[$id]["numbers"]);
            if (!empty($elem["times"])) {
                foreach ($elem["times"] as $index => $dayTime) {
                    if (!empty($dayTime)) {
                        $schedules[$id]["days"][$index] = DateTime::getDayText(
                            $dayTime,
                            startMonday: $startMonday,
                            language: $isoCode,
                        );
                    } else {
                        $dayNumber = (int)$elem["numbers"][$index];
                        $schedules[$id]["days"][$index] = DateTime::getDayName(
                            $dayNumber,
                            startMonday: $startMonday,
                            language: $isoCode,
                        );
                    }
                }
            } else {
                foreach ($elem["numbers"] as $index => $dayNumber) {
                    $schedules[$id]["days"][$index] = DateTime::getDayName(
                        $dayNumber,
                        startMonday: $startMonday,
                        language: $isoCode,
                    );
                }
            }
        }

        $zone = "";
        if (!empty($timeZone)) {
            $zone = DateTime::parseTimeZone((float)$timeZone);
            $zone = " ($zone)";
        }

        $result = [];
        foreach ($schedules as $elem) {
            $daysText = "";
            $amount   = count($elem["days"]);
            if ($amount > 7) {
                $daysText = NLS::get("TIME_TABLE_ALL_HOLIDAYS", $isoCode);
            } elseif ($amount === 7) {
                $daysText = NLS::get("TIME_TABLE_ALL_DAYS", $isoCode);
            } elseif ($amount === 2) {
                $daysText = NLS::join($elem["days"], false, $isoCode);
            } elseif ($amount === 1) {
                $daysText = $elem["days"][0];
            } else {
                $parts = [];
                $count = 0;
                while ($count < $amount) {
                    $first = $count;
                    $last  = $count;
                    for ($i = $count + 1; $i < $amount; $i++) {
                        if ($elem["numbers"][$i] === $maxDay) {
                            break;
                        }
                        if ($elem["numbers"][$i] - 1 !== $elem["numbers"][$last]) {
                            break;
                        }
                        $last = $i;
                    }
                    if ($last - $first > 1) {
                        $parts[] = NLS::format("TIME_TABLE_SOME_DAYS", [
                            $elem["days"][$first],
                            $elem["days"][$last],
                        ], $isoCode);
                        $count = $last + 1;
                    } else {
                        $parts[] = $elem["days"][$first];
                        $count++;
                    }
                    $daysText = NLS::join($parts, false, $isoCode);
                }
            }

            $timeText = "";
            $toHour   = $elem["toHour"];
            if ($toHour === "00:00") {
                $toHour = "24:00";
            }

            if (empty($elem["fromHour"])) {
                $timeText = NLS::get($closedText, $isoCode);
            } else {
                $timeText = NLS::format("TIME_TABLE_HOURS", [ $elem["fromHour"], $toHour ], $isoCode);
            }

            $result[] = [
                "days"     => $elem["numbers"],
                "fromHour" => $elem["fromHour"],
                "toHour"   => $toHour,
                "daysText" => $daysText,
                "timeText" => $timeText,
                "zone"     => $zone,
            ];
        }

        return $result;
    }

    /**
     * Returns the Time Table as a string
     * @param array{}[] $timeTables
     * @param boolean   $startMonday Optional.
     * @param string    $timeZone    Optional.
     * @param string    $isoCode     Optional.
     * @return string
     */
    public static function getText(array $timeTables, bool $startMonday = false, string $timeZone = "", string $isoCode = ""): string {
        $list = self::getList($timeTables, false, $startMonday, $timeZone, $isoCode);
        if (empty($list)) {
            return "";
        }

        $result = [];
        foreach ($list as $elem) {
            $result[] = NLS::format("TIME_TABLE_SCHEDULE", [
                $elem["daysText"],
                $elem["timeText"],
                $elem["zone"],
            ], $isoCode);
        }

        return NLS::join($result, false, $isoCode);
    }
}
