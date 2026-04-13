<?php
namespace Tests\Date;

use Framework\IO\Request;
use Framework\Date\Date;
use Framework\Date\Period;
use Framework\Date\Type\PeriodType;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;

class PeriodTest extends TestCase {

    /** @dataProvider providerConstruct */
    public function testConstruct(array $input, string $prefix, PeriodType $expectedPeriod, bool $expectFromEmpty, bool $expectToEmpty, ?int $expectedFromNum, ?int $expectedToNum): void {
        $p = new Period(new Request($input), $prefix);

        $this->assertSame($expectedPeriod, $p->period);

        $this->assertSame($expectFromEmpty, $p->fromTime->isEmpty());
        $this->assertSame($expectToEmpty, $p->toTime->isEmpty());

        if ($expectedFromNum !== null) {
            $this->assertSame($expectedFromNum, $p->fromTime->toNumber());
        }

        if ($expectedToNum !== null) {
            $this->assertSame($expectedToNum, $p->toTime->toNumber());
        }
    }

    public static function providerConstruct(): array {
        return [
            "empty"        => [[], "", PeriodType::Custom, true, true, null, null ],
            "dates"        => [[ "fromDate" => "2020-03-01", "toDate" => "2020-03-05" ], "", PeriodType::Custom, false, false, 20200301, 20200305 ],
            "dates_hour"   => [[ "fromDate" => "2020-03-01", "fromHour" => "10:00", "toDate" => "2020-03-05", "toHour" => "18:30" ], "", PeriodType::Custom, false, false, 20200301, 20200305 ],
            "timestamps"   => [[ "fromTime" => strtotime("2020-03-01 10:00:00"), "toTime" => strtotime("2020-03-05 18:30:00") ], "", PeriodType::Custom, false, false, 20200301, 20200305 ],
            "prefix_dates" => [[ "pFromDate" => "2020-04-01", "pToDate" => "2020-04-02" ], "p", PeriodType::Custom, false, false, 20200401, 20200402 ],
            "period_only"  => [[ "period" => "last7days" ], "", PeriodType::Last7Days, false, false, null, null ],
        ];
    }


    /** @dataProvider providerFromPeriod */
    public function testFromPeriod(PeriodType|string $periodType, ?int $expectedFrom, ?int $expectedTo): void {
        $p = Period::fromPeriod($periodType);

        if ($periodType instanceof PeriodType) {
            $this->assertSame($periodType, $p->period);
        } else {
            $this->assertSame(PeriodType::None, $p->period);
        }

        if ($expectedFrom === null) {
            $this->assertTrue($p->fromTime->isEmpty());
        } else {
            $this->assertSame($expectedFrom, $p->fromTime->toTime());
            // From time for period-based dates should be at day start (00:00:00)
            $this->assertSame(0, $p->fromTime->toMinutes());
            $this->assertSame(0, $p->fromTime->getSecond());
        }

        if ($expectedTo === null) {
            $this->assertTrue($p->toTime->isEmpty());
        } else {
            $this->assertSame($expectedTo, $p->toTime->toTime());
            // To time for period-based dates should be at day end (23:59:59)
            $this->assertSame(23 * 60 + 59, $p->toTime->toMinutes());
            $this->assertSame(59, $p->toTime->getSecond());
        }
    }

    public static function providerFromPeriod(): array {
        return [
            "today"         => [ PeriodType::Today, strtotime("today 00:00:00"), strtotime("today 23:59:59") ],
            "yesterday"     => [ PeriodType::Yesterday, strtotime("-1 day 00:00:00"), strtotime("-1 day 23:59:59") ],
            "prevYesterday" => [ PeriodType::PrevYesterday, strtotime("-2 day 00:00:00"), strtotime("-2 day 23:59:59") ],
            "tomorrow"      => [ PeriodType::Tomorrow, strtotime("+1 day 00:00:00"), strtotime("+1 day 23:59:59") ],
            "nextTomorrow"  => [ PeriodType::NextTomorrow, strtotime("+2 day 00:00:00"), strtotime("+2 day 23:59:59") ],

            "last7"         => [ PeriodType::Last7Days, strtotime("-7 days 00:00:00"), strtotime("today 23:59:59") ],
            "last15"        => [ PeriodType::Last15Days, strtotime("-15 days 00:00:00"), strtotime("today 23:59:59") ],
            "last30"        => [ PeriodType::Last30Days, strtotime("-30 days 00:00:00"), strtotime("today 23:59:59") ],
            "last60"        => [ PeriodType::Last60Days, strtotime("-60 days 00:00:00"), strtotime("today 23:59:59") ],
            "last90"        => [ PeriodType::Last90Days, strtotime("-90 days 00:00:00"), strtotime("today 23:59:59") ],
            "last120"       => [ PeriodType::Last120Days, strtotime("-120 days 00:00:00"), strtotime("today 23:59:59") ],
            "lastYear"      => [ PeriodType::LastYear, strtotime("-1 year 00:00:00"), strtotime("today 23:59:59") ],

            "thisWeek"      => [ PeriodType::ThisWeek, strtotime("sunday last week 00:00:00"), strtotime("saturday this week 23:59:59") ],
            "thisMonth"     => [ PeriodType::ThisMonth, strtotime("first day of this month 00:00:00"), strtotime("last day of this month 23:59:59") ],
            "thisYear"      => [ PeriodType::ThisYear, strtotime("first day of January this year 00:00:00"), strtotime("last day of December this year 23:59:59") ],

            "pastWeek"      => [ PeriodType::PastWeek, strtotime("sunday last week -7 days 00:00:00"), strtotime("saturday this week -7 days 23:59:59") ],
            "pastMonth"     => [ PeriodType::PastMonth, strtotime("first day of last month 00:00:00"), strtotime("last day of last month 23:59:59") ],
            "pastYear"      => [ PeriodType::PastYear, strtotime("first day of January last year 00:00:00"), strtotime("last day of December last year 23:59:59") ],

            "nextWeek"      => [ PeriodType::NextWeek, strtotime("sunday last week +7 days 00:00:00"), strtotime("saturday this week +7 days 23:59:59") ],
            "nextMonth"     => [ PeriodType::NextMonth, strtotime("first day of next month 00:00:00"), strtotime("last day of next month 23:59:59") ],
            "nextYear"      => [ PeriodType::NextYear, strtotime("first day of January next year 00:00:00"), strtotime("last day of December next year 23:59:59") ],

            "all"           => [ PeriodType::AllPeriod, null, strtotime("today 23:59:59") ],
            "custom"        => [ PeriodType::Custom, strtotime("today 00:00:00"), strtotime("today 23:59:59") ],
            "invalid"       => [ "Invalid", null, null ],
        ];
    }


    /** @dataProvider providerFromDictionary */
    public function testFromDictionary(array $dictData, int $expectedFrom, int $expectedTo): void {
        $dict = new Dictionary($dictData);
        $p = Period::fromDictionary($dict);

        $this->assertEquals($expectedFrom, $p->fromTime->toNumber());
        $this->assertEquals($expectedTo, $p->toTime->toNumber());
    }

    public static function providerFromDictionary(): array {
        return [
            "from_and_to" => [
                [ "fromDate" => "2020-01-02", "toDate" => "2020-01-03" ],
                20200102,
                20200103,
            ],
            "same_day" => [
                [ "fromDate" => "2020-02-10", "toDate" => "2020-02-10" ],
                20200210,
                20200210,
            ],
            "reversed" => [
                [ "fromDate" => "2020-04-05", "toDate" => "2020-04-01" ],
                20200405,
                20200401,
            ],
            "year_boundary" => [
                [ "fromDate" => "2019-12-31", "toDate" => "2020-01-01" ],
                20191231,
                20200101,
            ],
        ];
    }


    /** @dataProvider providerIsEmpty */
    public function testIsEmpty(array $input, bool $expected): void {
        $p = new Period(new Request($input));
        $this->assertSame($expected, $p->isEmpty());
        $this->assertSame(!$expected, $p->isNotEmpty());
    }

    public static function providerIsEmpty(): array {
        return [
            "empty"      => [[], true ],
            "from_only"  => [[ "fromDate" => "2020-01-01" ], false ],
            "to_only"    => [[ "toDate"   => "2020-01-02" ], false ],
            "both_dates" => [[ "fromDate" => "2020-01-01", "toDate" => "2020-01-02" ], false ],
        ];
    }


    /** @dataProvider providerIsValid */
    public function testIsValid(PeriodType|string $value, bool $expected): void {
        $this->assertSame($expected, Period::isValid($value));
    }

    public static function providerIsValid(): array {
        return [
            "today"     => [ PeriodType::Today, true ],
            "yesterday" => [ "yesterday", true ],
            "invalid"   => [ "not_a_period", false ],
            "empty"     => [ "", false ],
        ];
    }


    /** @dataProvider providerGetDaysAmount */
    public function testGetDaysAmount(PeriodType $period, int $expected): void {
        $p = Period::fromPeriod($period);
        $this->assertSame($expected, $p->getDaysAmount());
    }

    public static function providerGetDaysAmount(): array {
        $date = Date::now();

        return [
            "today"          => [ PeriodType::Today, 1 ],
            "yesterday"      => [ PeriodType::Yesterday, 1 ],
            "prevYesterday"  => [ PeriodType::PrevYesterday, 1 ],
            "tomorrow"       => [ PeriodType::Tomorrow, 1 ],
            "nextTomorrow"   => [ PeriodType::NextTomorrow, 1 ],

            "last7"          => [ PeriodType::Last7Days, 7 ],
            "last15"         => [ PeriodType::Last15Days, 15 ],
            "last30"         => [ PeriodType::Last30Days, 30 ],
            "last60"         => [ PeriodType::Last60Days, 60 ],
            "last90"         => [ PeriodType::Last90Days, 90 ],
            "last120"        => [ PeriodType::Last120Days, 120 ],
            "lastYear"       => [ PeriodType::LastYear, 365 ],

            "thisWeek"       => [ PeriodType::ThisWeek, 7 ],
            "thisMonth"      => [ PeriodType::ThisMonth, $date->getMonthDays() ],
            "thisYear"       => [ PeriodType::ThisYear, $date->getYearDays() ],

            "pastWeek"       => [ PeriodType::PastWeek, 7 ],
            "pastMonth"      => [ PeriodType::PastMonth, $date->subtract(months: 1)->getMonthDays() ],
            "pastYear"       => [ PeriodType::PastYear, $date->subtract(years: 1)->getYearDays() ],

            "nextWeek"       => [ PeriodType::NextWeek, 7 ],
            "nextMonth"      => [ PeriodType::NextMonth, $date->add(months: 1)->getMonthDays() ],
            "nextYear"       => [ PeriodType::NextYear, $date->add(years: 1)->getYearDays() ],

            "allPeriod"      => [ PeriodType::AllPeriod, 0 ],
            "custom"         => [ PeriodType::Custom, 0 ],
        ];
    }


    /** @dataProvider providerIterator */
    public function testIterator(array $requestData, array $expectedNumbers): void {
        $p = new Period(new Request($requestData));
        $numbers = [];
        foreach ($p as $d) {
            $numbers[] = $d->toNumber();
        }

        $this->assertEquals($expectedNumbers, $numbers);
    }

    public static function providerIterator(): array {
        return [
            "single_day" => [
                [ "fromDate" => "2020-01-01", "toDate" => "2020-01-01" ],
                [ 20200101 ],
            ],
            "three_days" => [
                [ "fromDate" => "2020-01-01", "toDate" => "2020-01-03" ],
                [ 20200101, 20200102, 20200103 ],
            ],
            "reversed" => [
                [ "fromDate" => "2020-01-03", "toDate" => "2020-01-01" ],
                [],
            ],
            "empty" => [
                [],
                [],
            ],
        ];
    }
}
