<?php
namespace Framework\Utils;

use Framework\Core\NLS;
use Framework\Date\DateTime;
use Framework\Date\DateFormat;
use Framework\Utils\Arrays;
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

        if (!is_array($data)) {
            return new TimeTable();
        }

        $timeTables = [];
        foreach ($data as $elem) {
            if (!is_array($elem)) {
                continue;
            }

            $days = isset($elem["days"]) ? Arrays::sort(Arrays::toInts($elem["days"])) : [];
            $from = Strings::toString($elem["from"] ?? "");
            $to   = Strings::toString($elem["to"]   ?? "");

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
            if (count($timeTable->days) > 0) {
                $list[] = $timeTable;
            }
        }
        return JSON::encode($list);
    }

    /**
     * Returns true if the Time Tables are valid and adds the Errors
     * @param Errors  $errors
     * @param boolean $withHolidays Optional.
     * @param boolean $isRequired   Optional.
     * @param string  $fieldKey     Optional.
     * @return boolean
     */
    public function isValid(
        Errors $errors,
        bool $withHolidays = false,
        bool $isRequired = false,
        string $fieldKey = "timeTables",
    ): bool {
        $hasError = false;

        foreach ($this->timeTables as $index => $timeTable) {
            if (!$isRequired && Arrays::isEmpty($timeTable->days)) {
                continue;
            }

            if ($isRequired && Arrays::isEmpty($timeTable->days)) {
                $errors->add("$fieldKey-$index-days", "GENERAL_ERROR_PERIOD_DAYS_EMPTY");
                $hasError = true;
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
            if ($timeTable->from !== "" && $timeTable->to !== "" && !DateTime::isValidHourPeriod($timeTable->from, $timeTable->to, true)) {
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
        if (count($this->timeTables) === 0) {
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
        if (count($this->timeTables) === 0) {
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
        if (count($this->timeTables) === 0) {
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
     * @return array<string,mixed>[]
     */
    public function getList(
        bool $allDays = false,
        string $closedText = "TIME_TABLE_NO_HOURS",
        string $timeZone = "",
        string $isoCode = "",
    ): array {
        if (count($this->timeTables) === 0 && !$allDays) {
            return [];
        }

        $minDay = $this->startMonday ? 1 : 0;
        $maxDay = $this->startMonday ? 8 : 7;

        $schedules = [];
        $days      = [];
        foreach ($this->timeTables as $timeTable) {
            if (count($timeTable->days) === 0) {
                continue;
            }

            foreach ($timeTable->days as $day) {
                $weekTime = DateTime::getWeekStart(0, $day, $this->startMonday, true);
                $weekDate = DateTime::toString($weekTime, DateFormat::Dashes);
                $fromTime = DateTime::toTimeHour($weekDate, $timeTable->from);
                $fromHour = DateTime::toString($fromTime, DateFormat::Time);
                $toTime   = DateTime::toTimeHour($weekDate, $timeTable->to);
                $toHour   = DateTime::toString($toTime, DateFormat::Time);
                $id       = "$fromHour-$toHour";

                if (!isset($schedules[$id])) {
                    $schedules[$id] = [
                        "fromHour" => $fromHour,
                        "toHour"   => $toHour,
                        "numbers"  => [],
                        "times"    => [],
                        "days"     => [],
                    ];
                }
                $schedules[$id]["numbers"][] = $day;
                $schedules[$id]["times"][]   = $day < $maxDay ? $fromTime : 0;
                $days[$day] = 1;
            }
        }
        $schedules = array_values($schedules);

        if ($allDays && count($days) < $maxDay) {
            $numbers = [];
            for ($i = $minDay; $i < $maxDay; $i++) {
                if (!isset($days[$i])) {
                    $numbers[] = $i;
                }
            }
            if (count($numbers) > 0) {
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
            if (count($elem["times"]) > 0) {
                foreach ($elem["times"] as $index => $dayTime) {
                    if ($dayTime !== 0) {
                        $schedules[$id]["days"][$index] = DateTime::getDayText(
                            $dayTime,
                            startMonday: $this->startMonday,
                            language:    $isoCode,
                        );
                    } else {
                        $schedules[$id]["days"][$index] = DateTime::getDayName(
                            $elem["numbers"][$index],
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
        if ($timeZone !== "") {
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

            if ($elem["fromHour"] === "") {
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
        if (count($list) === 0) {
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
