<?php
namespace Framework\Utils;

use Framework\Core\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\Errors;
use Framework\Utils\JSON;

/**
 * The Time Table Data class
 */
class TimeTableData {
    // phpcs:ignore
    public function __construct(
        /** @var int[] */
        public array $days,
        public string $from,
        public string $to,
    ) {
    }
}

/**
 * Several Time Table functions
 */
class TimeTable {

    /** @var TimeTableData[] */
    private array $timeTables  = [];
    private bool  $startMonday = false;


    /**
     * Creates a new Time Table instance
     * @param TimeTableData[] $timeTables  Optional.
     * @param boolean         $startMonday Optional.
     */
    private function __construct(array $timeTables = [], bool $startMonday = false) {
        $this->timeTables  = $timeTables;
        $this->startMonday = $startMonday;
    }

    /**
     * Creates a new Time Table instance
     * @param mixed   $data
     * @param boolean $startMonday Optional.
     * @return TimeTable
     */
    public static function create(mixed $data, bool $startMonday = false): TimeTable {
        if ($data instanceof TimeTable) {
            return new TimeTable($data->timeTables, $data->startMonday);
        }

        if (!Arrays::isArray($data)) {
            return new TimeTable();
        }

        $timeTables = [];
        foreach ($data as $elem) {
            $days = !empty($elem["days"]) ? Arrays::sort(Arrays::toInts($elem["days"])) : [];
            $from = !empty($elem["from"]) ? $elem["from"] : "";
            $to   = !empty($elem["to"]) ? $elem["to"] : "";
            $timeTables[] = new TimeTableData($days, $from, $to);
        }
        return new TimeTable($timeTables, $startMonday);
    }



    /**
     * Encodes the Time Tables as a JSON
     * @return string
     */
    public function encode(): string {
        $list = [];
        foreach ($this->timeTables as $timeTable) {
            if (!empty($timeTable->days)) {
                $list[] = $timeTable;
            }
        }
        return JSON::encode($list);
    }

    /**
     * Returns true if the Time Tables are valid and adds the Errors
     * @param Errors  $errors
     * @param boolean $withHolidays Optional.
     * @param string  $fieldKey     Optional.
     * @return boolean
     */
    public function isValid(
        Errors $errors,
        bool $withHolidays = false,
        string $fieldKey = "timeTables",
    ): bool {
        $hasError = false;

        foreach ($this->timeTables as $index => $timeTable) {
            if (empty($timeTable->days)) {
                continue;
            }

            foreach ($timeTable->days as $day) {
                if (!DateTime::isValidDay($day, $withHolidays, $this->startMonday)) {
                    $errors->add("$fieldKey-$index-days", "GENERAL_ERROR_PERIOD_DAYS_INVALID");
                    $hasError = true;
                    break;
                }
            }
            if (!DateTime::isValidHour($timeTable->from)) {
                $errors->add("$fieldKey-$index-from", "GENERAL_ERROR_PERIOD_FROM_TIME");
                $hasError = true;
            }
            if (!DateTime::isValidHour($timeTable->to)) {
                $errors->add("$fieldKey-$index-to", "GENERAL_ERROR_PERIOD_TO_TIME");
                $hasError = true;
            }
            if (!empty($timeTable->from) && !empty($timeTable->to) && !DateTime::isValidHourPeriod($timeTable->from, $timeTable->to, true)) {
                $errors->add("$fieldKey-$index-from", "GENERAL_ERROR_PERIOD_FROM_TO");
                $hasError = true;
            }
        }

        return !$hasError;
    }



    /**
     * Returns true if the Time Tables are in the Current time
     * @param integer $timeStamp Optional.
     * @param integer $minuteGap Optional.
     * @return boolean
     */
    public function isCurrent(int $timeStamp = 0, int $minuteGap = 0): bool {
        if (empty($this->timeTables)) {
            return false;
        }

        $weekDay    = DateTime::getDayOfWeek($timeStamp, $this->startMonday);
        $nowMinutes = DateTime::timeStampToMinutes($timeStamp);

        foreach ($this->timeTables as $timeTable) {
            if (!Arrays::contains($timeTable->days, $weekDay)) {
                continue;
            }

            $fromMinutes = DateTime::timeToMinutes($timeTable->from);
            $toMinutes   = DateTime::timeToMinutes($timeTable->to) - $minuteGap;

            if ($nowMinutes >= $fromMinutes && $nowMinutes <= $toMinutes) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the end time of the current day for the given Time Tables
     * @param integer $timeStamp Optional.
     * @return integer
     */
    public function getCurrentEndTime(int $timeStamp = 0): int {
        if (empty($this->timeTables)) {
            return 0;
        }

        $result     = 0;
        $weekDay    = DateTime::getDayOfWeek($timeStamp, $this->startMonday);
        $nowMinutes = DateTime::timeStampToMinutes($timeStamp);

        foreach ($this->timeTables as $timeTable) {
            if (!Arrays::contains($timeTable->days, $weekDay)) {
                continue;
            }

            $fromMinutes = DateTime::timeToMinutes($timeTable->from);
            $toMinutes   = DateTime::timeToMinutes($timeTable->to);
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
     * @param integer $timeStamp Optional.
     * @return integer
     */
    public function getNextStartTime(int $timeStamp = 0): int {
        if (empty($this->timeTables)) {
            return 0;
        }

        $maxDay    = $this->startMonday ? 8 : 7;
        $timeStamp = DateTime::getTime($timeStamp);
        $weekStart = DateTime::getWeekStart($timeStamp, 0, $this->startMonday, false);
        $weeks     = [ 0, 7 ];
        $result    = 0;

        foreach ($weeks as $week) {
            foreach ($this->timeTables as $timeTable) {
                $fromMinutes = DateTime::timeToMinutes($timeTable->from);

                foreach ($timeTable->days as $day) {
                    if ($day >= $maxDay) {
                        continue;
                    }

                    $newTime = DateTime::addDays($weekStart, $day + $week);
                    $newTime = DateTime::addMinutes($newTime, $fromMinutes);

                    if ($newTime >= $timeStamp && ($result === 0 || $newTime <= $result)) {
                        $result = $newTime;
                    }
                }
            }
        }

        return $result;
    }



    /**
     * Returns the Time Table as list
     * @param boolean $allDays    Optional.
     * @param string  $closedText Optional.
     * @param string  $timeZone   Optional.
     * @param string  $isoCode    Optional.
     * @return array{}
     */
    public function getList(
        bool $allDays = false,
        string $closedText = "TIME_TABLE_NO_HOURS",
        string $timeZone = "",
        string $isoCode = "",
    ): array {
        if (empty($this->timeTables) && !$allDays) {
            return [];
        }

        $minDay = $this->startMonday ? 1 : 0;
        $maxDay = $this->startMonday ? 8 : 7;

        $schedules = [];
        $days      = [];
        foreach ($this->timeTables as $timeTable) {
            if (empty($timeTable->days)) {
                continue;
            }

            foreach ($timeTable->days as $day) {
                $dayNumber = (int)$day;
                $weekTime  = DateTime::getWeekStart(0, $day, $this->startMonday, true);
                $weekDate  = DateTime::toString($weekTime, "dashes");
                $fromTime  = DateTime::toTimeHour($weekDate, $timeTable->from);
                $fromHour  = DateTime::toString($fromTime, "time");
                $toTime    = DateTime::toTimeHour($weekDate, $timeTable->to);
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
        $schedules = array_values($schedules);

        if ($allDays && count($days) < $maxDay) {
            $numbers = [];
            for ($i = $minDay; $i < $maxDay; $i++) {
                if (empty($days[$i])) {
                    $numbers[] = $i;
                }
            }
            if (!empty($numbers)) {
                $schedules[] = [
                    "fromHour" => "",
                    "toHour"   => "",
                    "numbers"  => $numbers,
                    "times"    => [],
                    "days"     => [],
                ];
            }
        }

        foreach ($schedules as $id => $elem) {
            sort($schedules[$id]["times"]);
            sort($schedules[$id]["numbers"]);
            if (!empty($elem["times"])) {
                foreach ($elem["times"] as $index => $dayTime) {
                    if (!empty($dayTime)) {
                        $schedules[$id]["days"][$index] = DateTime::getDayText(
                            $dayTime,
                            startMonday: $this->startMonday,
                            language:    $isoCode,
                        );
                    } else {
                        $dayNumber = (int)$elem["numbers"][$index];
                        $schedules[$id]["days"][$index] = DateTime::getDayName(
                            $dayNumber,
                            startMonday: $this->startMonday,
                            language:    $isoCode,
                        );
                    }
                }
            } else {
                foreach ($elem["numbers"] as $index => $dayNumber) {
                    $schedules[$id]["days"][$index] = DateTime::getDayName(
                        $dayNumber,
                        startMonday: $this->startMonday,
                        language:    $isoCode,
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
     * @param string $timeZone Optional.
     * @param string $isoCode  Optional.
     * @return string
     */
    public function getText(string $timeZone = "", string $isoCode = ""): string {
        $list = $this->getList(false, $timeZone, $isoCode);
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
