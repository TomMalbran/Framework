<?php
namespace Tests\Date;

use Framework\IO\Errors;
use Framework\Date\Date;
use Framework\Date\DateUtils;
use Framework\Date\TimeTable;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;

class TimeTableTest extends TestCase {

    /**
     * @dataProvider createProvider
     */
    public function testCreate(mixed $input, int $expectedCount): void {
        $tt   = TimeTable::create($input);
        $list = $tt->getList();
        $this->assertCount($expectedCount, $list);

        if ($expectedCount > 0) {
            $this->assertSame("10:00", $list[0]->fromHour);
            $this->assertSame("12:00", $list[0]->toHour);
        }
    }

    public static function createProvider(): array {
        return [
            "invalid_input"    => [ "nope", 0 ],
            "empty_array"      => [[], 0 ],
            "empty_values"     => [
                [[ "days" => [], "from" => "", "to" => "" ]],
                0,
            ],
            "single_table"     => [
                [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]],
                1,
            ],
            "multiple_tables"  => [
                [
                    [ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ],
                    [ "days" => [ 2 ], "from" => "13:00", "to" => "15:00" ],
                ],
                2,
            ],
            "dictionary_input" => [
                new Dictionary([
                    "0" => [ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ],
                    "1" => [ "days" => [ 2 ], "from" => "13:00", "to" => "15:00" ],
                ]),
                2,
            ],
            "timetable_object" => [
                TimeTable::create([
                    [ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ],
                    [ "days" => [ 2 ], "from" => "13:00", "to" => "15:00" ],
                ]),
                2,
            ],
        ];
    }


    public function testIsEmpty(): void {
        $ttEmpty = TimeTable::create([]);
        $this->assertTrue($ttEmpty->isEmpty());
        $this->assertFalse($ttEmpty->isNotEmpty());

        $tt = TimeTable::create([[ "days" => [1], "from" => "10:00", "to" => "12:00" ]]);
        $this->assertFalse($tt->isEmpty());
        $this->assertTrue($tt->isNotEmpty());
    }


    /**
     * @dataProvider isValidProvider
     */
    public function testIsValid(array $input, bool $withHolidays, bool $isRequired, bool $expectedHasErrors, string $expectedKey = ""): void {
        $tt     = TimeTable::create($input, allowEmpty: true);
        $errors = new Errors();
        $valid  = $tt->isValid($errors, $withHolidays, $isRequired);

        $this->assertSame(!$expectedHasErrors, $valid);
        if ($expectedHasErrors && $expectedKey !== "") {
            $this->assertTrue($errors->has($expectedKey));
        }
    }

    public static function isValidProvider(): array {
        return [
            "valid"          => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], false, false, false, "" ],
            "missing_days"   => [ [[ "days" => [], "from" => "", "to" => "" ]], false, true, true, "timeTables-0-days" ],
            "invalid_from"   => [ [[ "days" => [ 1 ], "from" => "25:00", "to" => "26:00" ]], false, false, true, "timeTables-0-from" ],
            "invalid_to"     => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "26:00" ]], false, false, true, "timeTables-0-to" ],
            "invalid_period" => [ [[ "days" => [ 1 ], "from" => "12:00", "to" => "10:00" ]], false, false, true, "timeTables-0-from" ],
            "holiday_good"   => [ [[ "days" => [ 7 ], "from" => "10:00", "to" => "12:00" ]], true, false, false, "" ],
            "holiday_bad"    => [ [[ "days" => [ 8 ], "from" => "10:00", "to" => "12:00" ]], true, false, true, "timeTables-0-days" ],
        ];
    }


    /**
     * @dataProvider hasHolidayProvider
     */
    public function testHasHoliday(array $input, bool $expected): void {
        $tt = TimeTable::create($input);
        $this->assertSame($expected, $tt->hasHoliday());
    }

    public static function hasHolidayProvider(): array {
        return [
            "no_holiday"   => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], false ],
            "with_holiday" => [ [[ "days" => [ 8 ], "from" => "10:00", "to" => "12:00" ]], true ],
        ];
    }


    /**
     * @dataProvider isCurrentProvider
     */
    public function testIsCurrent(array $input, int $minuteGap, bool $isHoliday, bool $skipTime, bool $expected): void {
        $tt = TimeTable::create($input);
        $this->assertSame($expected, $tt->isCurrent($minuteGap, $isHoliday, $skipTime));
    }

    public static function isCurrentProvider(): array {
        return [
            "empty"        => [ [], 0, false, false, false ],
            "all_days"     => [ [[ "days" => [ 0, 1, 2, 3, 4, 5, 6 ], "from" => "00:00", "to" => "23:59" ]], 0, false, true, true ],
            "holiday_only" => [ [[ "days" => [ 8 ], "from" => "00:00", "to" => "23:59" ]], 0, true, true, true ],
        ];
    }


    /**
     * @dataProvider containsDateProvider
     */
    public function testContainsDate(array $input, string $date, string $time, int $minuteGap, bool $skipTime, bool $expected): void {
        $tt = TimeTable::create($input);
        $d = Date::create($date, $time);
        $this->assertSame($expected, $tt->containsDate($d, $minuteGap, false, $skipTime));
    }

    public static function containsDateProvider(): array {
        return [
            "inside"             => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "11:00", 0, false, true ],
            "outside"            => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "09:00", 0, false, false ],
            "skip_time"          => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "03:00", 0, true, true ],
            "minute_gap"         => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "11:40", 15, false, true ],
            "minute_gap_outside" => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "11:50", 15, false, false ],
            "holiday"            => [ [[ "days" => [ 8 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "11:00", 0, false, false ],
            "holiday_skip_time"  => [ [[ "days" => [ 8 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "03:00", 0, true, false ],
        ];
    }


    /**
     * @dataProvider currentEndProvider
     */
    public function testGetCurrentEndTime(array $input, string $date, string $time): void {
        $tt = TimeTable::create($input);
        $d = Date::create($date, $time);
        $expected = Date::create($date, "00:00")->add(minutes: DateUtils::timeToMinutes($input[0]["to"]));
        $this->assertSame($expected->toTime(), $tt->getCurrentEndTime($d)->toTime());
    }

    public static function currentEndProvider(): array {
        return [
            "normal"      => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ] ], "2023-01-02", "11:00" ],
            "exact_start" => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ] ], "2023-01-02", "10:00" ],
            "mid_day"     => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ] ], "2023-01-02", "11:30" ],
            "exact_end"   => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ] ], "2023-01-02", "12:00" ],
        ];
    }


    /**
     * @dataProvider nextStartProvider
     */
    public function testGetNextStartTime(array $input, string $date, string $time): void {
        $tt = TimeTable::create($input);
        $d = Date::create($date, $time);

        // compute expected using the same logic as TimeTable::getNextStartTime
        $weeks = [0, 7];
        $weekStart = $d->toWeekStart(false)->toDayStart();
        $expected = Date::empty();
        foreach ($weeks as $week) {
            foreach ($input[0]["days"] as $day) {
                if ($day >= 7) {
                    continue;
                }
                $fromMinutes = DateUtils::timeToMinutes($input[0]["from"]);
                $newDate = $weekStart->add(days: $day + $week)->add(minutes: $fromMinutes);
                if ($newDate->isAfter($d) && ($expected->isEmpty() || $newDate->isBefore($expected))) {
                    $expected = $newDate;
                }
            }
        }

        $this->assertSame($expected->toTime(), $tt->getNextStartTime($d)->toTime());
    }

    public static function nextStartProvider(): array {
        return [
            "same_day_before"      => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "08:00" ],
            "same_day_exact_start" => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "10:00" ],
            "same_day_during"      => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "11:00" ],
            "same_day_after"       => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-02", "13:00" ],
            "from_sunday"          => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "2023-01-01", "05:00" ],
        ];
    }


    /**
     * @dataProvider listProvider
     */
    public function testGetList(array $input, string $closedText, string $timeZone, string $isoCode, bool $allDays): void {
        $tt = TimeTable::create($input);
        $list = $tt->getList($closedText, $timeZone, $isoCode, $allDays);
        $this->assertIsArray($list);

        if (count($input) > 0) {
            $this->assertNotEmpty($list);
            $this->assertSame('10:00', $list[0]->fromHour);

            if ($timeZone !== "") {
                $this->assertNotSame('', $list[0]->zone);
            }
        } else {
            if ($allDays) {
                $this->assertNotEmpty($list);
                $this->assertSame("", $list[0]->fromHour);
            } else {
                $this->assertEmpty($list);
            }
        }
    }

    public static function listProvider(): array {
        return [
            "empty_no_allDays" => [ [], "TIME_TABLE_NO_HOURS", "", "", false ],
            "empty_allDays"    => [ [], "CLOSED_TEXT", "", "", true ],
            "one_no_zone"      => [ [ [ "days" => [1], "from" => "10:00", "to" => "12:00" ] ], "TIME_TABLE_NO_HOURS", "", "", false ],
            "one_with_zone"    => [ [ [ "days" => [1], "from" => "10:00", "to" => "12:00" ] ], "TIME_TABLE_NO_HOURS", "1.5", "", false ],
            "holiday_allDays"  => [ [ [ "days" => [8], "from" => "10:00", "to" => "12:00" ] ], "TIME_TABLE_NO_HOURS", "", "es", true ],
        ];
    }


    /**
     * @dataProvider getTextProvider
     */
    public function testGetText(array $input, string $timeZone, string $isoCode, bool $expectEmpty, bool $expectZone): void {
        $tt = TimeTable::create($input);
        $text = $tt->getText($timeZone, $isoCode);

        if ($expectEmpty) {
            $this->assertSame("", $text);
        } else {
            $this->assertIsString($text);
            $this->assertNotEmpty($text);
        }

        if ($expectZone) {
            $this->assertStringContainsString("(", $text);
        } else {
            $this->assertStringNotContainsString("(", $text);
        }
    }

    public static function getTextProvider(): array {
        return [
            "empty"         => [ [], "", "", true, false ],
            "one_no_zone"   => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "", "", false, false ],
            // note: getText maps its args into getList as (closedText, timeZone)
            "one_with_zone" => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], "", "1.5", false, true ],
        ];
    }


    /**
     * @dataProvider encodeProvider
     */
    public function testEncode(array $input, bool $expectNonEmpty): void {
        $tt = TimeTable::create($input);
        $encoded = $tt->encode();

        if ($expectNonEmpty) {
            $this->assertStringContainsString("10:00", $encoded);
        } else {
            $this->assertSame("[]", $encoded);
        }
    }

    public static function encodeProvider(): array {
        return [
            "empty"    => [ [], false ],
            "one"      => [ [[ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ]], true ],
            "multiple" => [
                [
                    [ "days" => [ 1 ], "from" => "10:00", "to" => "12:00" ],
                    [ "days" => [ 2 ], "from" => "13:00", "to" => "15:00" ],
                ],
                true,
            ],
        ];
    }
}
