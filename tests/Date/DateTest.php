<?php
namespace Tests\Date;

use Framework\Date\Date;
use Framework\Date\Type\DateType;
use Framework\Date\Type\DateFormat;
use Framework\Date\TimeZone;

use PHPUnit\Framework\TestCase;

class DateTest extends TestCase {

    public function testEmpty() {
        $d = Date::empty();
        $this->assertTrue($d->isEmpty());
    }

    public function testNow() {
        $d = Date::now();
        $this->assertTrue($d->isNotEmpty());
        $this->assertSame(time(), $d->toTime());
    }


    /** @dataProvider providerCreate */
    public function testCreate(mixed $input, mixed $hour, int $expected) {
        $d = Date::create($input, $hour);
        $this->assertSame($expected, $d->toTime());
    }

    public static function providerCreate(): array {
        // Use fixed timestamps for reproducible tests
        return [
            // Date instance input
            "date" => [ Date::create(1609459200), null, 1609459200 ],
            // number input (2021-01-01 00:00:00 UTC0)
            "numeric" => [ 1609459200, null, 1609459200 ],
            // dashes date input (no hour)
            "dashes" => [ "2021-01-01", null, 1609455600 ],
            // dashes date with hour as string (appended)
            "dashes_hour" => [ "2021-01-01", "15:30", 1609511400 ],
            // dashes date with hour as numeric (NOT added to date)
            "dashes_numeric_hour" => [ "2021-01-01", 15, 1609455600 ],
            // slashes date input (no hour)
            "slashes" => [ "01/01/2021", null, 1609455600 ],
            // special strings
            "today" => [ "today", null, strtotime(date("Y-m-d")) ],
            "tomorrow" => [ "tomorrow", null, strtotime(date("Y-m-d", time() + 86400)) ],
            // negative timestamp (should be treated as valid timestamp, not empty)
            "negative_timestamp" => [ -100000, null, -100000 ],
            // invalid inputs -> empty date (timestamp 0)
            "empty_string" => [ "", null, 0 ],
            "invalid_date_string" => [ "invalid-date-string", null, 0 ],
            "zero_timestamp" => [ 0, null, 0 ],
        ];
    }


    /** @dataProvider providerCreateOrNow */
    public function testCreateOrNow($input, $hour = null, $expectNow = false) {
        if ($expectNow) {
            $now = time();
            $d = Date::createOrNow($input, $hour);
            $this->assertTrue($d->isNotEmpty());
            $this->assertSame($now, $d->toTime());
        } else {
            $d = Date::createOrNow($input, $hour);
            $this->assertTrue($d->isNotEmpty());
            $this->assertSame(Date::create($input, $hour)->toTime(), $d->toTime());
        }
    }

    public static function providerCreateOrNow(): array {
        return [
            // valid inputs should create same date as create()
            "date" => [ Date::create(1609459200), null, false ],
            "numeric" => [ 1609459200, null, false ],
            "string" => [ "2021-01-01", null, false ],
            "string_hour" => [ "2023-01-01", "15:30", false ],
            "string_numeric_hour" => [ "2023-01-01", 15, false ],
            "negative_timestamp" => [ -100000, null, false ],
            // invalid inputs should return current date
            "empty_string" => [ "", null, true ],
            "invalid_date_string" => [ "invalid-date-string", null, true ],
            "zero_timestamp" => [ 0, null, true ],
        ];
    }


    /** @dataProvider providerParse */
    public function testParse(string $input, int $expY, int $expM, int $expD) {
        $d = Date::parse($input);
        $this->assertSame($expY, $d->getYear());
        $this->assertSame($expM, $d->getMonth());
        $this->assertSame($expD, $d->getDay());
    }

    public static function providerParse(): array {
        return [
            "dashes"      => [ "2020-03-05", 2020, 3, 5 ],
            "dashes_time" => [ "2020-03-05 15:30:00", 2020, 3, 5 ],
            "iso_z"       => [ "2020-03-05T00:00:00Z", 2020, 3, 5 ],
            "slashes"     => [ "2020/03/05", 2020, 3, 5 ],
            "text"        => [ "March 5, 2020", 2020, 3, 5 ],
            "other_text"  => [ "5 Mar 2020", 2020, 3, 5 ],
            "invalid"     => [ "invalid", 0, 0, 0 ],
            "empty"       => [ "", 0, 0, 0 ],
        ];
    }


    /** @dataProvider providerCreateTime */
    public function testCreateTime(int $day, int $month, int $year, int $hour, int $minute, int $second, array $expect) {
        $d = Date::createTime($day, $month, $year, $hour, $minute, $second);
        $this->assertSame($expect["year"], $d->getYear());
        $this->assertSame($expect["month"], $d->getMonth());
        $this->assertSame($expect["day"], $d->getDay());
        $this->assertSame($expect["hour"], $d->getHour());
        $this->assertSame($expect["minute"], $d->getMinute());
        $this->assertSame($expect["second"], $d->getSecond());
    }

    public static function providerCreateTime(): array {
        return [
            "basic"    => [ 2, 4, 2020, 15, 30, 5, [ "year" => 2020, "month" => 4, "day" => 2, "hour" => 15, "minute" => 30, "second" => 5 ]],
            "midnight" => [ 1, 1, 2000, 0, 0, 0, [ "year" => 2000, "month" => 1, "day" => 1, "hour" => 0, "minute" => 0, "second" => 0 ]],
            "leap_day" => [ 29, 2, 2020, 12, 0, 0, [ "year" => 2020, "month" => 2, "day" => 29, "hour" => 12, "minute" => 0, "second" => 0 ]],
            "year_end" => [ 31, 12, 1999, 23, 59, 59, [ "year" => 1999, "month" => 12, "day" => 31, "hour" => 23, "minute" => 59, "second" => 59 ]],
            "invalid"  => [ 31, 2, 2020, 12, 0, 0, [ "year" => 2020, "month" => 3, "day" => 2, "hour" => 12, "minute" => 0, "second" => 0 ]],
            "empty"    => [ 0, 0, 0, 0, 0, 0, [ "year" => 1999, "month" => 11, "day" => 30, "hour" => 0, "minute" => 0, "second" => 0 ]],
            "negative" => [ -10, -10, -10, 0, 0, 0, [ "year" => -11, "month" => 1, "day" => 21, "hour" => 0, "minute" => 0, "second" => 0 ]],
        ];
    }


    /** @dataProvider providerMax */
    public function testMax(array $dates, int $expected) {
        $result = Date::max(...$dates);
        $this->assertSame($expected, $result->toTime());
    }

    public static function providerMax(): array {
        return [
            "basic"           => [[ Date::create(1000), Date::create(2000), Date::create(1500) ], 2000],
            "different_order" => [[ Date::create(1500), Date::create(1000), Date::create(2000) ], 2000],
            "same_values"     => [[ Date::create(2000), Date::create(2000) ], 2000],
            "includes_empty"  => [[ Date::create(0), Date::create(1000), Date::create(500) ], 1000],
            "empty_array"     => [[], 0],
        ];
    }


    /** @dataProvider providerIsEmpty */
    public function testIsEmpty(mixed $input, mixed $hour = null, bool $expect) {
        $d = Date::create($input, $hour);
        $this->assertSame($expect, $d->isEmpty());
    }

    public static function providerIsEmpty(): array {
        return [
            "date" => [ Date::create(1609459200), null, false ],
            "numeric" => [ 1609459200, null, false ],
            "string" => [ "2021-01-01", null, false ],
            "string_hour" => [ "2023-01-01", "15:30", false ],
            "string_numeric_hour" => [ "2023-01-01", 15, false ],
            // invalid inputs should return empty date
            "empty_string" => [ "", null, true ],
            "invalid_date_string" => [ "invalid-date-string", null, true ],
        ];
    }


    /** @dataProvider providerIsNotEmpty */
    public function testIsNotEmpty(mixed $input, mixed $hour = null, bool $expect) {
        $d = Date::create($input, $hour);
        $this->assertSame($expect, $d->isNotEmpty());
    }

    public static function providerIsNotEmpty(): array {
        return [
            "date" => [ Date::create(1609459200), null, true ],
            "numeric" => [ 1609459200, null, true ],
            "string" => [ "2021-01-01", null, true ],
            "string_hour" => [ "2023-01-01", "15:30", true ],
            "string_numeric_hour" => [ "2023-01-01", 15, true ],
            // invalid inputs should return empty date
            "empty_string" => [ "", null, false ],
            "invalid_date_string" => [ "invalid-date-string", null, false ],
        ];
    }


    /** @dataProvider providerSet */
    public function testSet(array $setArgs, Date $date) {
        $d = $date->set(...$setArgs);

        $expected = array_merge([
            "year"   => $date->getYear(),
            "month"  => $date->getMonth(),
            "day"    => $date->getDay(),
            "hour"   => $date->getHour(),
            "minute" => $date->getMinute(),
            "second" => $date->getSecond(),
        ], $setArgs);

        $this->assertSame($expected["year"], $d->getYear());
        $this->assertSame($expected["month"], $d->getMonth());
        $this->assertSame($expected["day"], $d->getDay());
        $this->assertSame($expected["hour"], $d->getHour());
        $this->assertSame($expected["minute"], $d->getMinute());
        $this->assertSame($expected["second"], $d->getSecond());
    }

    public static function providerSet(): array {
        $date = Date::createTime(1, 1, 2020, 5, 6, 7);

        return [
            "year_only"   => [[ "year" => 2021 ], $date ],
            "month_only"  => [[ "month" => 12 ], $date ],
            "day_only"    => [[ "day" => 15 ], $date ],
            "hour_only"   => [[ "hour" => 10 ], $date ],
            "minute_only" => [[ "minute" => 30 ], $date ],
            "second_only" => [[ "second" => 45 ], $date ],
            "some_fields" => [[ "month" => 12, "minute" => 30 ], $date ],
            "all_fields"  => [[ "year" => 1999, "month" => 11, "day" => 30, "hour" => 0, "minute" => 1, "second" => 2 ], $date ],
            "no_fields"   => [[], $date ],
            "empty"       => [[], Date::empty() ],
        ];
    }


    /** @dataProvider providerSetHourMinute */
    public function testSetHourMinute(string $input, int $expectHour, int $expectMinute, bool $expectEmpty) {
        $d = Date::createTime(1, 1, 2020, 5, 6, 7);
        $d3 = $d->setHourMinute($input);

        if ($expectEmpty) {
            $this->assertTrue($d3->isEmpty());
        } else {
            $this->assertSame($expectHour, $d3->getHour());
            $this->assertSame($expectMinute, $d3->getMinute());
        }
    }

    public static function providerSetHourMinute(): array {
        return [
            "leading_zero"    => [ "08:20", 8, 20, false ],
            "no_leading_zero" => [ "8:5", 8, 5, false ],
            "midnight"        => [ "00:00", 0, 0, false ],
            "max"             => [ "23:59", 23, 59, false ],
            // invalid hour/minute should return empty date
            "date"            => [ "2020-01-01", 0, 0, true ],
            "date_time"       => [ "2020-01-01 15:30", 0, 0, true ],
            "invalid_empty"   => [ "", 0, 0, true ],
            "invalid_text"    => [ "not-a-time", 0, 0, true ],
        ];
    }


    /** @dataProvider providerAdd */
    public function testAdd(array $addArgs, array $base, array $expect) {
        $d = Date::createTime($base["day"], $base["month"], $base["year"], $base["hour"], $base["minute"], $base["second"]);
        $d2 = $d->add(...$addArgs);

        $this->assertSame($expect["year"], $d2->getYear());
        $this->assertSame($expect["month"], $d2->getMonth());
        $this->assertSame($expect["day"], $d2->getDay());
        $this->assertSame($expect["hour"], $d2->getHour());
        $this->assertSame($expect["minute"], $d2->getMinute());
        $this->assertSame($expect["second"], $d2->getSecond());
    }

    public static function providerAdd(): array {
        $base = [ "year" => 2020, "month" => 10, "day" => 10, "hour" => 0, "minute" => 0, "second" => 0 ];

        return [
            "add_days"     => [[ "days"    => 5  ], $base, [ "year" => 2020, "month" => 10, "day" => 15, "hour" => 0, "minute" => 0,  "second" => 0  ]],
            "add_weeks"    => [[ "weeks"   => 2  ], $base, [ "year" => 2020, "month" => 10, "day" => 24, "hour" => 0, "minute" => 0,  "second" => 0  ]],
            "add_months"   => [[ "months"  => 1  ], $base, [ "year" => 2020, "month" => 11, "day" => 10, "hour" => 0, "minute" => 0,  "second" => 0  ]],
            "add_years"    => [[ "years"   => 2  ], $base, [ "year" => 2022, "month" => 10, "day" => 10, "hour" => 0, "minute" => 0,  "second" => 0  ]],
            "add_hours"    => [[ "hours"   => 5  ], $base, [ "year" => 2020, "month" => 10, "day" => 10, "hour" => 5, "minute" => 0,  "second" => 0  ]],
            "add_minutes"  => [[ "minutes" => 30 ], $base, [ "year" => 2020, "month" => 10, "day" => 10, "hour" => 0, "minute" => 30, "second" => 0  ]],
            "add_seconds"  => [[ "seconds" => 30 ], $base, [ "year" => 2020, "month" => 10, "day" => 10, "hour" => 0, "minute" => 0,  "second" => 30 ]],
            "add_negative" => [[ "days"    => -5 ], $base, [ "year" => 2020, "month" => 10, "day" => 5,  "hour" => 0, "minute" => 0,  "second" => 0  ]],
            "add_multiple" => [[ "days"    => 1, "hours" => 2, "minutes" => 15 ], $base, [ "year" => 2020, "month" => 10, "day" => 11, "hour" => 2, "minute" => 15, "second" => 0 ]],
        ];
    }


    /** @dataProvider providerSubtract */
    public function testSubtract(array $subArgs, array $base, array $expect) {
        $d = Date::createTime($base["day"], $base["month"], $base["year"], $base["hour"], $base["minute"], $base["second"]);
        $d2 = $d->subtract(...$subArgs);

        $this->assertSame($expect["year"], $d2->getYear());
        $this->assertSame($expect["month"], $d2->getMonth());
        $this->assertSame($expect["day"], $d2->getDay());
        $this->assertSame($expect["hour"], $d2->getHour());
        $this->assertSame($expect["minute"], $d2->getMinute());
        $this->assertSame($expect["second"], $d2->getSecond());
    }

    public static function providerSubtract(): array {
        $base = [ "year" => 2020, "month" => 10, "day" => 10, "hour" => 0, "minute" => 0, "second" => 0 ];

        return [
            "sub_days"     => [[ "days"    => 5  ], $base, [ "year" => 2020, "month" => 10, "day" => 5,  "hour" => 0,  "minute" => 0,  "second" => 0  ]],
            "sub_weeks"    => [[ "weeks"   => 2  ], $base, [ "year" => 2020, "month" => 9,  "day" => 26, "hour" => 0,  "minute" => 0,  "second" => 0  ]],
            "sub_months"   => [[ "months"  => 1  ], $base, [ "year" => 2020, "month" => 9,  "day" => 10, "hour" => 0,  "minute" => 0,  "second" => 0  ]],
            "sub_years"    => [[ "years"   => 2  ], $base, [ "year" => 2018, "month" => 10, "day" => 10, "hour" => 0,  "minute" => 0,  "second" => 0  ]],
            "sub_hours"    => [[ "hours"   => 2  ], $base, [ "year" => 2020, "month" => 10, "day" => 9,  "hour" => 22, "minute" => 0,  "second" => 0  ]],
            "sub_minutes"  => [[ "minutes" => 30 ], $base, [ "year" => 2020, "month" => 10, "day" => 9,  "hour" => 23, "minute" => 30, "second" => 0  ]],
            "sub_seconds"  => [[ "seconds" => 30 ], $base, [ "year" => 2020, "month" => 10, "day" => 9,  "hour" => 23, "minute" => 59, "second" => 30 ]],
            "sub_negative" => [[ "days"    => -5 ], $base, [ "year" => 2020, "month" => 10, "day" => 15, "hour" => 0,  "minute" => 0,  "second" => 0  ]],
            "sub_multiple" => [[ "days"    => 1, "hours" => 2, "minutes" => 15 ], $base, [ "year" => 2020, "month" => 10, "day" => 8, "hour" => 21, "minute" => 45, "second" => 0 ]],
        ];
    }


    /** @dataProvider providerToTime */
    public function testToTime(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toTime());
    }

    public static function providerToTime(): array {
        return [
            "null"      => [ null, 0 ],
            "empty"     => [ "", 0 ],
            "invalid"   => [ "invalid", 0 ],
            "timestamp" => [ 1000, 1000 ],
            "string"    => [ "1000", 1000 ],
            "dashes"    => [ "2020-02-03", 1580684400 ],
            "date"      => [ Date::createTime(4, 5, 2021, 7, 8, 9), 1620104889 ],
        ];
    }


    /** @dataProvider providerToNumber */
    public function testToNumber(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toNumber());
    }

    public static function providerToNumber(): array {
        return [
            "null"      => [ null, 0 ],
            "empty"     => [ "", 0 ],
            "invalid"   => [ "invalid", 0 ],
            "timestamp" => [ 1000, 19700101 ],
            "dashes"    => [ "1970-01-01", 19700101 ],
            "future"    => [ "2000-01-01", 20000101 ],
            "date"      => [ Date::createTime(4, 5, 2021, 7, 8, 9), 20210504 ],
        ];
    }


    /** @dataProvider providerToMinutes */
    public function testToMinutes(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toMinutes());
    }

    public static function providerToMinutes(): array {
        return [
            "null"        => [ null, 0 ],
            "empty"       => [ "", 0 ],
            "invalid"     => [ "invalid", 0 ],
            "midnight"    => [ "1970-01-01 00:00:00", 0 ],
            "one_minute"  => [ "1970-01-01 00:01:00", 1 ],
            "hours_minds" => [ "1970-01-01 02:30:00", 150 ],
            "date"        => [ Date::createTime(4, 5, 2021, 4, 20, 0), 4 * 60 + 20 ],
        ];
    }


    /** @dataProvider providerToServerTime */
    public function testToServerTime(int $input, bool $useTimeZone, int $expected) {
        $d = Date::create($input);
        $d2 = $d->toServerTime($useTimeZone);
        $this->assertSame($expected, $d2->toTime());
    }

    public static function providerToServerTime(): array {
        return [
            "zero_true"       => [ 0, true, TimeZone::toServerTime(0, true) ],
            "timestamp_true"  => [ 1609459200, true, TimeZone::toServerTime(1609459200, true) ],
            "timestamp_false" => [ 1609459200, false, TimeZone::toServerTime(1609459200, false) ],
        ];
    }


    /** @dataProvider providerToDayMoment */
    public function testToDayMoment(mixed $input, DateType $type, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toDayMoment($type)->toTime());
    }

    public static function providerToDayMoment() {
        return [
            "null"        => [ null, DateType::None, 0 ],
            "empty"       => [ "", DateType::Start, 0 ],
            "invalid"     => [ "not-a-date", DateType::Middle, 0 ],
            "day_none"    => [ "2020-02-03 12:34:56", DateType::None, 1580729696 ],
            "day_start"   => [ "2020-02-03 12:34:56", DateType::Start, 1580684400 ],
            "day_middle"  => [ "2020-02-03 12:34:56", DateType::Middle, 1580727600 ],
            "day_end"     => [ "2020-02-03 12:34:56", DateType::End, 1580770799 ],
            "date_start"  => [ Date::createTime(3, 2, 2020, 12, 34, 56), DateType::Start, 1580684400 ],
            "date_middle" => [ Date::createTime(3, 2, 2020, 12, 34, 56), DateType::Middle, 1580727600 ],
            "date_end"    => [ Date::createTime(3, 2, 2020, 12, 34, 56), DateType::End, 1580770799 ],
        ];
    }


    /** @dataProvider providerToWeekStart */
    public function testToWeekStart(mixed $input, bool $startMonday, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toWeekStart($startMonday)->toTime());
    }

    public static function providerToWeekStart(): array {
        return [
            "null"        => [ null, false, 0 ],
            "empty"       => [ "", false, 0 ],
            "invalid"     => [ "not-a-date", false, 0 ],
            "day_sunday"  => [ "2020-02-03 12:34:56", false, 1580643296 ],
            "day_monday"  => [ "2020-02-03 12:34:56", true, 1580643296 ],
            "date_sunday" => [ Date::createTime(3, 2, 2020, 12, 34, 56), false, 1580643296 ],
            "date_monday" => [ Date::createTime(3, 2, 2020, 12, 34, 56), true, 1580643296 ],
        ];
    }


    /** @dataProvider providerToWeekEnd */
    public function testToWeekEnd(mixed $input, bool $startMonday, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toWeekEnd($startMonday)->toTime());
    }

    public static function providerToWeekEnd(): array {
        return [
            "null"        => [ null, false, 0 ],
            "empty"       => [ "", false, 0 ],
            "invalid"     => [ "not-a-date", false, 0 ],
            "day_sunday"  => [ "2020-02-03 12:34:56", false, 1581161696 ],
            "day_monday"  => [ "2020-02-03 12:34:56", true, 1581161696 ],
            "date_sunday" => [ Date::createTime(3, 2, 2020, 12, 34, 56), false, 1581161696 ],
            "date_monday" => [ Date::createTime(3, 2, 2020, 12, 34, 56), true, 1581161696 ],
        ];
    }


    /** @dataProvider providerToMonthStart */
    public function testToMonthStart(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toMonthStart()->toTime());
    }

    public static function providerToMonthStart(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 1580511600 ],
            "day_time" => [ "2020-02-03 12:34:56", 1580556896 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 1580556896 ],
        ];
    }


    /** @dataProvider providerToMonthEnd */
    public function testToMonthEnd(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toMonthEnd()->toTime());
    }

    public static function providerToMonthEnd(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day_jan"  => [ "2020-01-05 12:34:56", 1580470496 ],
            "day_feb"  => [ "2020-02-05 12:34:56", 1582976096 ],
            "day_mar"  => [ "2020-03-05 12:34:56", 1585650896 ],
            "date_apr" => [ Date::createTime(5, 4, 2020, 0, 0, 0), 1588197600 ],
        ];
    }


    /** @dataProvider providerToYearStart */
    public function testToYearStart(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toYearStart()->toTime());
    }

    public static function providerToYearStart(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 1577833200 ],
            "day_time" => [ "2020-02-03 12:34:56", 1577878496 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 1577878496 ],
        ];
    }


    /** @dataProvider providerToYearEnd */
    public function testToYearEnd(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toYearEnd()->toTime());
    }

    public static function providerToYearEnd(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 1609369200 ],
            "day_time" => [ "2020-02-03 12:34:56", 1609414496 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 1609414496 ],
        ];
    }


    /** @dataProvider providerGetYear */
    public function testGetYear(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getYear());
    }

    public static function providerGetYear(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 2020 ],
            "day_time" => [ "2020-02-03 12:34:56", 2020 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 2020 ],
        ];
    }


    /** @dataProvider providerGetYearDays */
    public function testGetYearDays(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getYearDays());
    }

    public static function providerGetYearDays(): array {
        return [
            "null"      => [ null, 0 ],
            "empty"     => [ "", 0 ],
            "invalid"   => [ "not-a-date", 0 ],
            "non_leap"  => [ "2019-06-01", 365 ],
            "leap"      => [ "2020-02-03", 366 ],
            "date_leap" => [ Date::createTime(29, 2, 2020), 366 ],
        ];
    }


    /** @dataProvider providerGetMonth */
    public function testGetMonth(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getMonth());
    }

    public static function providerGetMonth(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 2 ],
            "day_time" => [ "2020-02-03 12:34:56", 2 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 2 ],
        ];
    }


    /** @dataProvider providerGetMonthZero */
    public function testGetMonthZero(mixed $input, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getMonthZero());
    }

    public static function providerGetMonthZero(): array {
        return [
            "null"     => [ null, "" ],
            "empty"    => [ "", "" ],
            "invalid"  => [ "not-a-date", "" ],
            "day"      => [ "2020-02-03", "02" ],
            "day_time" => [ "2020-02-03 12:34:56", "02" ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), "02" ],
        ];
    }


    /** @dataProvider providerGetMonthDays */
    public function testGetMonthDays(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getMonthDays());
    }

    public static function providerGetMonthDays(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "jan"      => [ "2020-01-05 12:34:56", 31 ],
            "feb_leap" => [ "2020-02-03", 29 ],
            "jun"      => [ "2019-06-01", 30 ],
            "date"     => [ Date::createTime(29, 2, 2020), 29 ],
        ];
    }


    /** @dataProvider providerGetMonthName */
    public function testGetMonthName(mixed $input, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getMonthName());
    }

    public static function providerGetMonthName(): array {
        return [
            "null"     => [ null, "" ],
            "empty"    => [ "", "" ],
            "invalid"  => [ "not-a-date", "" ],
            "day"      => [ "2020-02-03", "February" ],
            "day_time" => [ "2020-02-03 12:34:56", "February" ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), "February" ],
        ];
    }


    /** @dataProvider providerGetDay */
    public function testGetDay(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getDay());
    }

    public static function providerGetDay(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 3 ],
            "day_time" => [ "2020-02-03 12:34:56", 3 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 3 ],
        ];
    }


    /** @dataProvider providerGetDayZero */
    public function testGetDayZero(mixed $input, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getDayZero());
    }

    public static function providerGetDayZero(): array {
        return [
            "null"     => [ null, "" ],
            "empty"    => [ "", "" ],
            "invalid"  => [ "not-a-date", "" ],
            "day"      => [ "2020-02-03", "03" ],
            "day_time" => [ "2020-02-03 12:34:56", "03" ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), "03" ],
        ];
    }


    /** @dataProvider providerGetDayOfWeek */
    public function testGetDayOfWeek(mixed $input, bool $startMonday, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getDayOfWeek($startMonday));
    }

    public static function providerGetDayOfWeek(): array {
        return [
            "null"       => [ null, false, 0 ],
            "empty"      => [ "", false, 0 ],
            "invalid"    => [ "not-a-date", false, 0 ],
            "monday_sun" => [ "2020-02-03", false, 1 ],
            "monday_mon" => [ "2020-02-03", true, 1 ],
            "sunday_sun" => [ "2020-02-02", false, 0 ],
            "sunday_mon" => [ "2020-02-02", true, 7 ],
        ];
    }


    /** @dataProvider providerGetDayName */
    public function testGetDayName(mixed $input, bool $startMonday, int $length, bool $inUpperCase, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getDayName($startMonday, $length, $inUpperCase));
    }

    public static function providerGetDayName(): array {
        return [
            "monday"       => [ "2020-02-03", false, 0, false, "Monday" ],
            "monday_short" => [ "2020-02-03", false, 3, false, "Mon" ],
            "monday_upper" => [ "2020-02-03", false, 0, true,  "MONDAY" ],
        ];
    }


    /** @dataProvider providerGetDayMonth */
    public function testGetDayMonth(mixed $input, int $monthLength, bool $inUpperCase, string $language, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getDayMonth($monthLength, $inUpperCase, $language));
    }

    public static function providerGetDayMonth(): array {
        return [
            "null"        => [ null, 0, false, "", "" ],
            "empty"       => [ "", 0, false, "", "" ],
            "invalid"     => [ "not-a-date", 0, false, "", "" ],
            "day_default" => [ "2020-02-03", 0, false, "", "03 February" ],
            "day_short"   => [ "2020-02-03", 3, false, "", "03 Feb" ],
            "day_upper"   => [ "2020-02-03", 0, true,  "", "03 FEBRUARY" ],
        ];
    }


    /** @dataProvider providerGetHour */
    public function testGetHour(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getHour());
    }

    public static function providerGetHour(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 0 ],
            "day_time" => [ "2020-02-03 12:34:56", 12 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 12 ],
        ];
    }


    /** @dataProvider providerGetMinute */
    public function testGetMinute(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getMinute());
    }

    public static function providerGetMinute(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 0 ],
            "day_time" => [ "2020-02-03 12:34:56", 34 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 34 ],
        ];
    }


    /** @dataProvider providerGetSecond */
    public function testGetSecond(mixed $input, int $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->getSecond());
    }

    public static function providerGetSecond(): array {
        return [
            "null"     => [ null, 0 ],
            "empty"    => [ "", 0 ],
            "invalid"  => [ "not-a-date", 0 ],
            "day"      => [ "2020-02-03", 0 ],
            "day_time" => [ "2020-02-03 12:34:56", 56 ],
            "date"     => [ Date::createTime(3, 2, 2020, 12, 34, 56), 56 ],
        ];
    }


    /** @dataProvider providerIsToday */
    public function testIsToday(mixed $input, bool $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->isToday());
    }

    public static function providerIsToday(): array {
        return [
            "null"      => [ null, false ],
            "empty"     => [ "", false ],
            "invalid"   => [ "not-a-date", false ],
            "today"     => [ date("Y-m-d"), true ],
            "yesterday" => [ date("Y-m-d", time() - 86400), false ],
        ];
    }


    /** @dataProvider providerIsPast */
    public function testIsPast(mixed $input, bool $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->isPast());
    }

    public static function providerIsPast(): array {
        return [
            "null"      => [ null, false ],
            "empty"     => [ "", false ],
            "invalid"   => [ "not-a-date", false ],
            "past"      => [ date("Y-m-d", time() - 86400), true ],
            "future"    => [ date("Y-m-d", time() + 86400), false ],
        ];
    }


    /** @dataProvider providerIsFuture */
    public function testIsFuture(mixed $input, bool $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->isFuture());
    }

    public static function providerIsFuture(): array {
        return [
            "null"      => [ null, false ],
            "empty"     => [ "", false ],
            "invalid"   => [ "not-a-date", false ],
            "past"      => [ date("Y-m-d", time() - 86400), false ],
            "future"    => [ date("Y-m-d", time() + 86400), true ],
        ];
    }


    /** @dataProvider providerIsCurrentMonth */
    public function testIsCurrentMonth(mixed $input, bool $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->isCurrentMonth());
    }

    public static function providerIsCurrentMonth(): array {
        return [
            "null"      => [ null, false ],
            "empty"     => [ "", false ],
            "invalid"   => [ "not-a-date", false ],
            "current"   => [ date("Y-m-d"), true ],
            "past"      => [ date("Y-m-d", time() - 86400 * 30), false ],
            "future"    => [ date("Y-m-d", time() + 86400 * 30), false ],
        ];
    }


    /** @dataProvider providerIsEqual */
    public function testIsEqual(mixed $input1, mixed $input2, bool $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->isEqual($d2));
    }

    public static function providerIsEqual(): array {
        return [
            "both_null"      => [ null, null, true ],
            "both_empty"     => [ "", "", true ],
            "both_invalid"   => [ "not-a-date", "not-a-date", true ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", false ],
            "same_date"      => [ "2020-02-03", "2020-02-03", true ],
            "same_date_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:56", true ],
            "same_date_obj"  => [ Date::createTime(3, 2, 2020, 12, 34, 56), Date::createTime(3, 2, 2020, 12, 34, 56), true ],
            "different"      => [ "2020-02-03", "2021-02-03", false ],
            "different_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:57", false ],
        ];
    }


    /** @dataProvider providerIsNotEqual */
    public function testIsNotEqual(mixed $input1, mixed $input2, bool $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->isNotEqual($d2));
    }

    public static function providerIsNotEqual(): array {
        return [
            "both_null"      => [ null, null, false ],
            "both_empty"     => [ "", "", false ],
            "both_invalid"   => [ "not-a-date", "not-a-date", false ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", true ],
            "same_date"      => [ "2020-02-03", "2020-02-03", false ],
            "same_date_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:56", false ],
            "same_date_obj"  => [ Date::createTime(3, 2, 2020, 12, 34, 56), Date::createTime(3, 2, 2020, 12, 34, 56), false ],
            "different"      => [ "2020-02-03", "2021-02-03", true ],
            "different_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:57", true ],
        ];
    }


    /** @dataProvider providerIsBefore */
    public function testIsBefore(mixed $input1, mixed $input2, bool $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->isBefore($d2));
    }

    public static function providerIsBefore(): array {
        return [
            "both_null"      => [ null, null, false ],
            "both_empty"     => [ "", "", false ],
            "both_invalid"   => [ "not-a-date", "not-a-date", false ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", false ],
            "same_date"      => [ "2020-02-03", "2020-02-03", false ],
            "same_date_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:56", false ],
            "before"         => [ "2020-02-03", "2021-02-03", true ],
            "after"          => [ "2021-02-03", "2020-02-03", false ],
        ];
    }


    /** @dataProvider providerIsBeforeOrEqual */
    public function testIsBeforeOrEqual(mixed $input1, mixed $input2, bool $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->isBeforeOrEqual($d2));
    }

    public static function providerIsBeforeOrEqual(): array {
        return [
            "both_null"      => [ null, null, true ],
            "both_empty"     => [ "", "", true ],
            "both_invalid"   => [ "not-a-date", "not-a-date", true ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", false ],
            "same_date"      => [ "2020-02-03", "2020-02-03", true ],
            "same_date_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:56", true ],
            "before"         => [ "2020-02-03", "2021-02-03", true ],
            "after"          => [ "2021-02-03", "2020-02-03", false ],
        ];
    }


    /** @dataProvider providerIsAfter */
    public function testIsAfter(mixed $input1, mixed $input2, bool $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->isAfter($d2));
    }

    public static function providerIsAfter(): array {
        return [
            "both_null"      => [ null, null, false ],
            "both_empty"     => [ "", "", false ],
            "both_invalid"   => [ "not-a-date", "not-a-date", false ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", false ],
            "same_date"      => [ "2020-02-03", "2020-02-03", false ],
            "same_date_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:56", false ],
            "before"         => [ "2020-02-03", "2021-02-03", false ],
            "after"          => [ "2021-02-03", "2020-02-03", true ],
        ];
    }


    /** @dataProvider providerIsAfterOrEqual */
    public function testIsAfterOrEqual(mixed $input1, mixed $input2, bool $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->isAfterOrEqual($d2));
    }

    public static function providerIsAfterOrEqual(): array {
        return [
            "both_null"      => [ null, null, true ],
            "both_empty"     => [ "", "", true ],
            "both_invalid"   => [ "not-a-date", "not-a-date", true ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", false ],
            "same_date"      => [ "2020-02-03", "2020-02-03", true ],
            "same_date_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:56", true ],
            "before"         => [ "2020-02-03", "2021-02-03", false ],
            "after"          => [ "2021-02-03", "2020-02-03", true ],
        ];
    }


    /** @dataProvider providerIsBetween */
    public function testIsBetween(mixed $input, mixed $start, mixed $end, bool $expected) {
        $d = Date::create($input);
        $s = Date::create($start);
        $e = Date::create($end);
        $this->assertSame($expected, $d->isBetween($s, $e));
    }

    public static function providerIsBetween(): array {
        return [
            "null"           => [ null, null, null, false ],
            "empty"          => [ "", "", "", false ],
            "invalid"        => [ "not-a-date", "not-a-date", "not-a-date", false ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", "2020-02-04", false ],
            "two_invalid"    => [ null, "not-a-date", "not-a-date", false ],
            "same_date"      => [ "2020-02-03", "2020-02-03", "2020-02-03", true ],
            "same_date_time" => [ "2020-02-03 12:34:56", "2020-02-03 12:34:56", "2020-02-03 12:34:56", true ],
            "between"        => [ "2020-02-03", "2020-02-01", "2020-02-05", true ],
            "before"         => [ "2020-02-01", "2020-02-03", "2020-02-05", false ],
            "after"          => [ "2020-02-06", "2020-02-03", "2020-02-05", false ],
        ];
    }


    /** @dataProvider providerIsAtDayStart */
    public function testIsAtDayStart(mixed $input, bool $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->isAtDayStart());
    }

    public static function providerIsAtDayStart(): array {
        return [
            "null"     => [ null, false ],
            "empty"    => [ "", false ],
            "invalid"  => [ "not-a-date", false ],
            "day"      => [ "2020-02-03", true ],
            "day_time" => [ "2020-02-03 12:34:56", false ],
            "date"     => [ Date::createTime(3, 2, 2020, 0, 0, 0), true ],
        ];
    }


    /** @dataProvider providerGetDaysDiff */
    public function testGetDaysDiff(mixed $input1, mixed $input2, int $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->getDaysDiff($d2));
    }

    public static function providerGetDaysDiff(): array {
        return [
            "both_null"      => [ null, null, 0 ],
            "both_empty"     => [ "", "", 0 ],
            "both_invalid"   => [ "not-a-date", "not-a-date", 0 ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", 0 ],
            "same_date"      => [ "2020-02-03", "2020-02-03", 0 ],
            "same_date_time" => [ "2020-02-03 11:34:56", "2020-02-03 12:34:56", 0 ],
            "diff_one_day"   => [ "2020-02-03", "2020-02-04", 1 ],
            "diff_five_days" => [ "2020-02-01", "2020-02-06", 5 ],
        ];
    }


    /** @dataProvider providerGetWeeksDiff */
    public function testGetWeeksDiff(mixed $input1, mixed $input2, int $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->getWeeksDiff($d2));
    }

    public static function providerGetWeeksDiff(): array {
        return [
            "both_null"      => [ null, null, 0 ],
            "both_empty"     => [ "", "", 0 ],
            "both_invalid"   => [ "not-a-date", "not-a-date", 0 ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", 0 ],
            "same_date"      => [ "2020-02-03", "2020-02-03", 0 ],
            "same_date_time" => [ "2020-02-03 11:34:56", "2020-02-03 12:34:56", 0 ],
            "diff_one_week"  => [ "2020-02-03", "2020-02-10", 1 ],
            "diff_two_weeks" => [ "2020-02-03", "2020-02-17", 2 ],
        ];
    }


    /** @dataProvider providerGetHoursDiff */
    public function testGetHoursDiff(mixed $input1, mixed $input2, int $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->getHoursDiff($d2));
    }

    public static function providerGetHoursDiff(): array {
        return [
            "both_null"      => [ null, null, 0 ],
            "both_empty"     => [ "", "", 0 ],
            "both_invalid"   => [ "not-a-date", "not-a-date", 0 ],
            "one_invalid"    => [ "2020-02-03", "not-a-date", 0 ],
            "same_date"      => [ "2020-02-03", "2020-02-03", 0 ],
            "same_date_time" => [ "2020-02-03 11:34:56", "2020-02-03 12:34:56", 1 ],
            "diff_one_hour"  => [ "2020-02-03 11:00:00", "2020-02-03 12:00:00", 1 ],
            "diff_two_hours" => [ "2020-02-03 10:00:00", "2020-02-03 12:00:00", 2 ],
        ];
    }


    /** @dataProvider providerGetMinutesDiff */
    public function testGetMinutesDiff(mixed $input1, mixed $input2, int $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->getMinutesDiff($d2));
    }

    public static function providerGetMinutesDiff(): array {
        return [
            "both_null"        => [ null, null, 0 ],
            "both_empty"       => [ "", "", 0 ],
            "both_invalid"     => [ "not-a-date", "not-a-date", 0 ],
            "one_invalid"      => [ "2020-02-03", "not-a-date", 0 ],
            "same_date"        => [ "2020-02-03", "2020-02-03", 0 ],
            "same_date_time"   => [ "2020-02-03 12:00:00", "2020-02-03 12:34:00", 34 ],
            "diff_one_minute"  => [ "2020-02-03 12:00:00", "2020-02-03 12:01:00", 1 ],
            "diff_two_minutes" => [ "2020-02-03 12:00:00", "2020-02-03 12:02:00", 2 ],
        ];
    }


    /** @dataProvider providerGetSecondsDiff */
    public function testGetSecondsDiff(mixed $input1, mixed $input2, int $expected) {
        $d1 = Date::create($input1);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->getSecondsDiff($d2));
    }

    public static function providerGetSecondsDiff(): array {
        return [
            "both_null"        => [ null, null, 0 ],
            "both_empty"       => [ "", "", 0 ],
            "both_invalid"     => [ "not-a-date", "not-a-date", 0 ],
            "one_invalid"      => [ "2020-02-03", "not-a-date", 0 ],
            "same_date"        => [ "2020-02-03", "2020-02-03", 0 ],
            "same_date_time"   => [ "2020-02-03 12:00:00", "2020-02-03 12:00:34", 34 ],
            "diff_one_second"  => [ "2020-02-03 12:00:00", "2020-02-03 12:00:01", 1 ],
            "diff_two_seconds" => [ "2020-02-03 12:00:00", "2020-02-03 12:00:02", 2 ],
        ];
    }


    /** @dataProvider providerGetAge */
    public function testGetAge(mixed $input, mixed $reference, int $expected) {
        $d = Date::create($input);
        $r = Date::create($reference);
        $this->assertSame($expected, $d->getAge($r));
    }

    public static function providerGetAge(): array {
        return [
            "null"             => [ null, null, 0 ],
            "empty"            => [ "", "", 0 ],
            "invalid"          => [ "not-a-date", "not-a-date", 0 ],
            "same_date_time"   => [ "2020-02-03 12:34:56", "2020-02-03 12:34:56", 0 ],
            "age_zero"         => [ "2020-02-03", "2020-02-03", 0 ],
            "age_one"          => [ "2019-02-03", "2020-02-03", 1 ],
            "age_five"         => [ "2015-02-03", "2020-02-03", 5 ],
            "age_not_birthday" => [ "2015-02-04", "2020-02-03", 4 ],
            "age_old"          => [ "1950-02-03", "2020-02-03", 70 ],
            "default_now"      => [ "2020-02-03", null, (int)date("Y") - 2020 ],
        ];
    }


    /** @dataProvider providerFormat */
    public function testFormat(mixed $input, string $format, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->format($format));
    }

    public static function providerFormat(): array {
        return [
            "null"     => [ null, "Y-m-d", "" ],
            "empty"    => [ "", "Y-m-d", "" ],
            "invalid"  => [ "not-a-date", "Y-m-d", "" ],
            "day"      => [ "2020-02-03", "Y-m-d", "2020-02-03" ],
            "day_time" => [ "2020-02-03 12:34:56", "Y-m-d H:i:s", "2020-02-03 12:34:56" ],
        ];
    }


    /** @dataProvider providerToString */
    public function testToString(mixed $input, DateFormat $format, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toString($format));
    }

    public static function providerToString(): array {
        return [
            "null"            => [ null, DateFormat::Time, "" ],
            "empty"           => [ "", DateFormat::Time, "" ],
            "invalid"         => [ "not-a-date", DateFormat::Time, "" ],
            // valid cases for each format
            "time"            => [ "2020-02-03 12:34:56", DateFormat::Time, "12:34" ],
            "dashes"          => [ "2020-02-03 12:34:56", DateFormat::Dashes, "03-02-2020" ],
            "dashes_time"     => [ "2020-02-03 12:34:56", DateFormat::DashesTime, "03-02-2020 12:34" ],
            "dashes_seconds"  => [ "2020-02-03 12:34:56", DateFormat::DashesSeconds, "03-02-2020 12:34:56" ],
            "reverse"         => [ "2020-02-03 12:34:56", DateFormat::Reverse, "2020-02-03" ],
            "reverse_time"    => [ "2020-02-03 12:34:56", DateFormat::ReverseTime, "2020-02-03 12:34" ],
            "reverse_seconds" => [ "2020-02-03 12:34:56", DateFormat::ReverseSeconds, "2020-02-03 12:34:56" ],
            "slashes"         => [ "2020-02-03 12:34:56", DateFormat::Slashes, "03/02/2020" ],
            "slashes_time"    => [ "2020-02-03 12:34:56", DateFormat::SlashesTime, "03/02/2020 12:34" ],
            "slashes_seconds" => [ "2020-02-03 12:34:56", DateFormat::SlashesSeconds, "03/02/2020 12:34:56" ],
        ];
    }


    /** @dataProvider providerToISOString */
    public function testToISOString(mixed $input, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toISOString());
    }

    public static function providerToISOString(): array {
        return [
            "null"     => [ null, "" ],
            "empty"    => [ "", "" ],
            "invalid"  => [ "not-a-date", "" ],
            "day"      => [ "2020-02-03", "2020-02-03T00:00:00+01:00" ],
            "day_time" => [ "2020-02-03 12:34:56", "2020-02-03T12:34:56+01:00" ],
        ];
    }


    /** @dataProvider providerToUTCString */
    public function testToUTCString(mixed $input, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, $d->toUTCString());
    }

    public static function providerToUTCString(): array {
        return [
            "null"     => [ null, "" ],
            "empty"    => [ "", "" ],
            "invalid"  => [ "not-a-date", "" ],
            "day"      => [ "2020-02-03", "2020-02-03T00:00:00Z" ],
            "day_time" => [ "2020-02-03 12:34:56", "2020-02-03T12:34:56Z" ],
        ];
    }


    /** @dataProvider providerToHourPeriodString */
    public function testToHourPeriodString(mixed $input, mixed $input2, string $expected) {
        $d1 = Date::create($input);
        $d2 = Date::create($input2);
        $this->assertSame($expected, $d1->toHourPeriodString($d2));
    }

    public static function providerToHourPeriodString(): array {
        return [
            "null"    => [ null, null, "" ],
            "empty"   => [ "", "", "" ],
            "invalid" => [ "not-a-date", "", "" ],
            "day"     => [ "2020-02-03", "2020-02-04", "00:00 - 00:00" ],
            "before"  => [ "2020-02-03 10:34:56", "2020-02-03 12:56:34", "10:34 - 12:56" ],
            "after"   => [ "2020-02-03 12:34:56", "2020-02-03 10:56:34", "10:56 - 12:34" ],
        ];
    }


    /** @dataProvider providerJsonSerialize */
    public function testJsonSerialize(mixed $input, string $expected) {
        $d = Date::create($input);
        $this->assertSame($expected, json_encode($d));
    }

    public static function providerJsonSerialize(): array {
        return [
            "null"     => [ null, "0" ],
            "empty"    => [ "", "0" ],
            "invalid"  => [ "not-a-date", "0" ],
            "day"      => [ "2020-02-03", "1580684400" ],
            "day_time" => [ "2020-02-03 12:34:56", "1580729696" ],
            "old_date" => [ "1920-02-03", "-1575075600" ],
            "old_time" => [ "1920-02-03 12:34:56", "-1575030304" ],
        ];
    }


    public function testDateTypeName() {
        $this->assertSame("none", DateType::None->getName());
        $this->assertSame("start", DateType::Start->getName());
        $this->assertSame("middle", DateType::Middle->getName());
        $this->assertSame("end", DateType::End->getName());
    }
}
