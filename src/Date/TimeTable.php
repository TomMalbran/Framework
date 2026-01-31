<?php
namespace Framework\Date;

use Framework\Intl\NLS;
use Framework\Date\DateTime;
use Framework\Date\DateUtils;
use Framework\Date\DateFormat;
use Framework\Date\TimeZone;
use Framework\Date\TimeTableItem;
use Framework\Utils\Dictionary;
use Framework\Utils\Arrays;
use Framework\Utils\Errors;
use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * Several Time Table functions
 */
class TimeTable {

    /** @var TimeTableItem[] */
    private array $timeTables  = [];
    private bool  $startMonday = false;


    /**
     * Creates a new Time Table instance
     * @param TimeTableItem[] $timeTables  Optional.
     * @param bool            $startMonday Optional.
     */
    private function __construct(array $timeTables = [], bool $startMonday = false) {
        $this->timeTables  = $timeTables;
        $this->startMonday = $startMonday;
    }

    /**
     * Creates a new Time Table instance
     * @param mixed $data
     * @param bool  $startMonday Optional.
     * @return TimeTable
     */
    public static function create(mixed $data, bool $startMonday = false): TimeTable {
        if ($data instanceof TimeTable) {
            return new TimeTable($data->timeTables, $data->startMonday);
        }

        if ($data instanceof Dictionary) {
            $data = $data->toArray();
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

            $timeTables[] = new TimeTableItem($days, $from, $to);
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
     * @param Errors $errors
     * @param bool   $withHolidays Optional.
     * @param bool   $isRequired   Optional.
     * @param string $fieldKey     Optional.
     * @return bool
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
                if (!DateUtils::isValidDay($day, $withHolidays, $this->startMonday)) {
                    $errors->add("$fieldKey-$index-days", "GENERAL_ERROR_PERIOD_DAYS_INVALID");
                    $hasError = true;
                    break;
                }
            }
            if (!DateUtils::isValidHour($timeTable->from)) {
                $errors->add("$fieldKey-$index-from", "GENERAL_ERROR_PERIOD_FROM_TIME");
                $hasError = true;
            }
            if (!DateUtils::isValidHour($timeTable->to)) {
                $errors->add("$fieldKey-$index-to", "GENERAL_ERROR_PERIOD_TO_TIME");
                $hasError = true;
            }
            if ($timeTable->from !== "" && $timeTable->to !== "" &&
                !DateUtils::isValidHourPeriod($timeTable->from, $timeTable->to, true)
            ) {
                $errors->add("$fieldKey-$index-from", "GENERAL_ERROR_PERIOD_FROM_TO");
                $hasError = true;
            }
        }

        return !$hasError;
    }



    /**
     * Returns true if the Time Tables are in the Current time
     * @param int $timeStamp Optional.
     * @param int $minuteGap Optional.
     * @return bool
     */
    public function isCurrent(int $timeStamp = 0, int $minuteGap = 0): bool {
        if (count($this->timeTables) === 0) {
            return false;
        }

        $date       = new Date($timeStamp);
        $weekDay    = $date->getDayOfWeek($this->startMonday);
        $nowMinutes = $date->toMinutes();

        foreach ($this->timeTables as $timeTable) {
            if (!Arrays::contains($timeTable->days, $weekDay)) {
                continue;
            }

            $fromMinutes = DateUtils::timeToMinutes($timeTable->from);
            $toMinutes   = DateUtils::timeToMinutes($timeTable->to) - $minuteGap;

            if ($nowMinutes >= $fromMinutes && $nowMinutes <= $toMinutes) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the end time of the current day for the given Time Tables
     * @param int $timeStamp Optional.
     * @return int
     */
    public function getCurrentEndTime(int $timeStamp = 0): int {
        if (count($this->timeTables) === 0) {
            return 0;
        }

        $date       = new Date($timeStamp);
        $dayStart   = $date->toDayStart();
        $resultDate = new Date();

        $weekDay    = $date->getDayOfWeek($this->startMonday);
        $nowMinutes = $date->toMinutes();

        foreach ($this->timeTables as $timeTable) {
            if (!Arrays::contains($timeTable->days, $weekDay)) {
                continue;
            }

            $fromMinutes = DateUtils::timeToMinutes($timeTable->from);
            $toMinutes   = DateUtils::timeToMinutes($timeTable->to);
            if ($nowMinutes >= $fromMinutes && $nowMinutes <= $toMinutes) {
                $resultDate = $dayStart->moveMinute($toMinutes);
                break;
            }
        }

        return $resultDate->getTimeStamp();
    }

    /**
     * Returns the next start time for the given Time Tables
     * @param int $timeStamp Optional.
     * @return int
     */
    public function getNextStartTime(int $timeStamp = 0): int {
        if (count($this->timeTables) === 0) {
            return 0;
        }

        $maxDay     = $this->startMonday ? 8 : 7;
        $weeks      = [ 0, 7 ];

        $date       = new Date($timeStamp);
        $weekStart  = $date->toWeekStart($this->startMonday);
        $resultDate = new Date();

        foreach ($weeks as $week) {
            foreach ($this->timeTables as $timeTable) {
                $fromMinutes = DateUtils::timeToMinutes($timeTable->from);

                foreach ($timeTable->days as $day) {
                    if ($day >= $maxDay) {
                        continue;
                    }

                    $newDate = $weekStart->moveDay($day + $week);
                    $newDate = $newDate->moveMinute($fromMinutes);

                    if ($newDate->isAfter($date) && ($resultDate->isEmpty() || $newDate->isBefore($resultDate))) {
                        $resultDate = $newDate;
                    }
                }
            }
        }

        return $resultDate->getTimeStamp();
    }



    /**
     * Returns the Time Table as list
     * @param bool   $allDays    Optional.
     * @param string $closedText Optional.
     * @param string $timeZone   Optional.
     * @param string $isoCode    Optional.
     * @return array{days:int[],fromHour:string,toHour:string,daysText:string,timeText:string,zone:string}[]
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
            for ($i = $minDay; $i < $maxDay; $i += 1) {
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
                            timeStamp:   $dayTime,
                            startMonday: $this->startMonday,
                            language:    $isoCode,
                        );
                    } else {
                        $schedules[$id]["days"][$index] = DateUtils::getDayName(
                            day:         $elem["numbers"][$index] ?? 0,
                            startMonday: $this->startMonday,
                            language:    $isoCode,
                        );
                    }
                }
            } else {
                foreach ($elem["numbers"] as $index => $dayNumber) {
                    $schedules[$id]["days"][$index] = DateUtils::getDayName(
                        day:         $dayNumber,
                        startMonday: $this->startMonday,
                        language:    $isoCode,
                    );
                }
            }
        }

        $zone = "";
        if ($timeZone !== "") {
            $zone = TimeZone::toString((float)$timeZone);
            $zone = " ($zone)";
        }

        $result = [];
        foreach ($schedules as $elem) {
            $daysText = "";
            $amount   = count($elem["days"]);
            if ($amount > 7) {
                $daysText = NLS::getString("TIME_TABLE_ALL_HOLIDAYS", $isoCode);
            } elseif ($amount === 7) {
                $daysText = NLS::getString("TIME_TABLE_ALL_DAYS", $isoCode);
            } elseif ($amount === 2) {
                $daysText = NLS::join($elem["days"], false, $isoCode);
            } elseif ($amount === 1) {
                $daysText = $elem["days"][0] ?? "";
            } else {
                $parts = [];
                $count = 0;
                while ($count < $amount) {
                    $first = $count;
                    $last  = $count;
                    for ($i = $count + 1; $i < $amount; $i += 1) {
                        $number = $elem["numbers"][$i] ?? 0;
                        if ($number === $maxDay) {
                            break;
                        }
                        if ($number - 1 !== ($elem["numbers"][$last] ?? 0)) {
                            break;
                        }
                        $last = $i;
                    }
                    if ($last - $first > 1) {
                        $parts[] = NLS::format("TIME_TABLE_SOME_DAYS", [
                            $elem["days"][$first] ?? "",
                            $elem["days"][$last]  ?? "",
                        ], $isoCode);
                        $count = $last + 1;
                    } else {
                        $parts[] = $elem["days"][$first] ?? "";
                        $count  += 1;
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
                $timeText = NLS::getString($closedText, $isoCode);
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
