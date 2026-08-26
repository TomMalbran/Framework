<?php
namespace Tests\Date;

use Framework\IO\Request;
use Framework\Date\Period;
use Framework\Date\Type\PeriodType;
use Framework\Utils\Dictionary;

use Traversable;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PeriodTest extends TestCase {

    #[DataProvider("providerConstruct")]
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
            "dates hour"   => [[ "fromDate" => "2020-03-01", "fromHour" => "10:00", "toDate" => "2020-03-05", "toHour" => "18:30" ], "", PeriodType::Custom, false, false, 20200301, 20200305 ],
            "timestamps"   => [[ "fromTime" => strtotime("2020-03-01 10:00:00"), "toTime" => strtotime("2020-03-05 18:30:00") ], "", PeriodType::Custom, false, false, 20200301, 20200305 ],
            "prefix dates" => [[ "pFromDate" => "2020-04-01", "pToDate" => "2020-04-02" ], "p", PeriodType::Custom, false, false, 20200401, 20200402 ],
            "period only"  => [[ "period" => "last7days" ], "", PeriodType::Last7Days, false, false, null, null ],
        ];
    }


    #[DataProvider("providerFromPeriod")]
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

    /**
     * Returns the start of a week, as an offset in days from today
     * @param int $shift
     * @return int
     */
    private static function weekStart(int $shift): int {
        // Counted from date("w") rather than with "sunday last week", which on a
        // Sunday lands on the previous one and was wrong a day in seven
        $days = $shift - (int)date("w");
        return (int)strtotime(sprintf("%+d days 00:00:00", $days));
    }

    /**
     * Returns the end of a week, as an offset in days from today
     * @param int $shift
     * @return int
     */
    private static function weekEnd(int $shift): int {
        $days = $shift + 6 - (int)date("w");
        return (int)strtotime(sprintf("%+d days 23:59:59", $days));
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

            "thisWeek"      => [ PeriodType::ThisWeek, self::weekStart(0), self::weekEnd(0) ],
            "thisMonth"     => [ PeriodType::ThisMonth, strtotime("first day of this month 00:00:00"), strtotime("last day of this month 23:59:59") ],
            "thisYear"      => [ PeriodType::ThisYear, strtotime("first day of January this year 00:00:00"), strtotime("last day of December this year 23:59:59") ],

            "pastWeek"      => [ PeriodType::PastWeek, self::weekStart(-7), self::weekEnd(-7) ],
            "pastMonth"     => [ PeriodType::PastMonth, strtotime("first day of last month 00:00:00"), strtotime("last day of last month 23:59:59") ],
            "pastYear"      => [ PeriodType::PastYear, strtotime("first day of January last year 00:00:00"), strtotime("last day of December last year 23:59:59") ],

            "nextWeek"      => [ PeriodType::NextWeek, self::weekStart(7), self::weekEnd(7) ],
            "nextMonth"     => [ PeriodType::NextMonth, strtotime("first day of next month 00:00:00"), strtotime("last day of next month 23:59:59") ],
            "nextYear"      => [ PeriodType::NextYear, strtotime("first day of January next year 00:00:00"), strtotime("last day of December next year 23:59:59") ],

            "all"           => [ PeriodType::AllPeriod, null, strtotime("today 23:59:59") ],
            "custom"        => [ PeriodType::Custom, strtotime("today 00:00:00"), strtotime("today 23:59:59") ],
            "invalid"       => [ "Invalid", null, null ],
        ];
    }


    #[DataProvider("providerFromDictionary")]
    public function testFromDictionary(array $dictData, int $expectedFrom, int $expectedTo): void {
        $dict = new Dictionary($dictData);
        $p = Period::fromDictionary($dict);

        $this->assertEquals($expectedFrom, $p->fromTime->toNumber());
        $this->assertEquals($expectedTo, $p->toTime->toNumber());
    }

    public static function providerFromDictionary(): array {
        return [
            "from and to"   => [
                [ "fromDate" => "2020-01-02", "toDate" => "2020-01-03" ],
                20200102,
                20200103,
            ],
            "same day"      => [
                [ "fromDate" => "2020-02-10", "toDate" => "2020-02-10" ],
                20200210,
                20200210,
            ],
            "reversed"      => [
                [ "fromDate" => "2020-04-05", "toDate" => "2020-04-01" ],
                20200405,
                20200401,
            ],
            "year boundary" => [
                [ "fromDate" => "2019-12-31", "toDate" => "2020-01-01" ],
                20191231,
                20200101,
            ],
        ];
    }


    #[DataProvider("providerIsEmpty")]
    public function testIsEmpty(array $input, bool $expected): void {
        $p = new Period(new Request($input));
        $this->assertSame($expected, $p->isEmpty());
        $this->assertSame(!$expected, $p->isNotEmpty());
    }

    public static function providerIsEmpty(): array {
        return [
            "empty"      => [[], true ],
            "from only"  => [[ "fromDate" => "2020-01-01" ], false ],
            "to only"    => [[ "toDate" => "2020-01-02" ], false ],
            "both dates" => [[ "fromDate" => "2020-01-01", "toDate" => "2020-01-02" ], false ],
        ];
    }


    #[DataProvider("providerIsValid")]
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


    #[DataProvider("providerGetDaysAmount")]
    public function testGetDaysAmount(PeriodType $period, int $expected): void {
        $p = Period::fromPeriod($period);
        $this->assertSame($expected, $p->getDaysAmount());
    }

    public static function providerGetDaysAmount(): array {
        // Counted with php's own calendar rather than with the Date class, which
        // is what getDaysAmount uses. Written the other way the month rows were
        // the same expression on both sides and could not fail, which is how a
        // month back from the 31st landing in the month it started in went unseen.
        $thisMonth = (int)date("t");
        $pastMonth = (int)date("t", strtotime("first day of last month"));
        $nextMonth = (int)date("t", strtotime("first day of next month"));
        $thisYear  = (int)date("L") + 365;
        $pastYear  = (int)date("L", strtotime("-1 year")) + 365;
        $nextYear  = (int)date("L", strtotime("+1 year")) + 365;

        return [
            "today"         => [ PeriodType::Today, 1 ],
            "yesterday"     => [ PeriodType::Yesterday, 1 ],
            "prevYesterday" => [ PeriodType::PrevYesterday, 1 ],
            "tomorrow"      => [ PeriodType::Tomorrow, 1 ],
            "nextTomorrow"  => [ PeriodType::NextTomorrow, 1 ],

            "last7"         => [ PeriodType::Last7Days, 7 ],
            "last15"        => [ PeriodType::Last15Days, 15 ],
            "last30"        => [ PeriodType::Last30Days, 30 ],
            "last60"        => [ PeriodType::Last60Days, 60 ],
            "last90"        => [ PeriodType::Last90Days, 90 ],
            "last120"       => [ PeriodType::Last120Days, 120 ],
            "lastYear"      => [ PeriodType::LastYear, 365 ],

            "thisWeek"      => [ PeriodType::ThisWeek, 7 ],
            "thisMonth"     => [ PeriodType::ThisMonth, $thisMonth ],
            "thisYear"      => [ PeriodType::ThisYear, $thisYear ],

            "pastWeek"      => [ PeriodType::PastWeek, 7 ],
            "pastMonth"     => [ PeriodType::PastMonth, $pastMonth ],
            "pastYear"      => [ PeriodType::PastYear, $pastYear ],

            "nextWeek"      => [ PeriodType::NextWeek, 7 ],
            "nextMonth"     => [ PeriodType::NextMonth, $nextMonth ],
            "nextYear"      => [ PeriodType::NextYear, $nextYear ],

            "allPeriod"     => [ PeriodType::AllPeriod, 0 ],
            "custom"        => [ PeriodType::Custom, 0 ],
        ];
    }


    #[DataProvider("providerPeriodTypeGetName")]
    public function testPeriodTypeGetName(PeriodType $period, string $expected): void {
        $this->assertSame($expected, $period->getName());
    }

    public static function providerPeriodTypeGetName(): array {
        return [
            "none"          => [ PeriodType::None, "none" ],
            "today"         => [ PeriodType::Today, "today" ],
            "prevYesterday" => [ PeriodType::PrevYesterday, "prevYesterday" ],
            "last7Days"     => [ PeriodType::Last7Days, "last7Days" ],
            "lastYear"      => [ PeriodType::LastYear, "lastYear" ],
            "thisMonth"     => [ PeriodType::ThisMonth, "thisMonth" ],
            "pastYear"      => [ PeriodType::PastYear, "pastYear" ],
            "nextWeek"      => [ PeriodType::NextWeek, "nextWeek" ],
            "allPeriod"     => [ PeriodType::AllPeriod, "allPeriod" ],
            "custom"        => [ PeriodType::Custom, "custom" ],
        ];
    }


    #[DataProvider("providerIterator")]
    public function testIterator(array $requestData, array $expectedNumbers): void {
        $p = new Period(new Request($requestData));

        // The foreach below is what calls it, and asking for it is what names it
        $this->assertInstanceOf(Traversable::class, $p->getIterator());

        $numbers = [];
        foreach ($p as $d) {
            $numbers[] = $d->toNumber();
        }

        $this->assertEquals($expectedNumbers, $numbers);
    }

    public static function providerIterator(): array {
        return [
            "single day" => [
                [ "fromDate" => "2020-01-01", "toDate" => "2020-01-01" ],
                [ 20200101 ],
            ],
            "three days" => [
                [ "fromDate" => "2020-01-01", "toDate" => "2020-01-03" ],
                [ 20200101, 20200102, 20200103 ],
            ],
            "reversed"   => [
                [ "fromDate" => "2020-01-03", "toDate" => "2020-01-01" ],
                [],
            ],
            "empty"      => [
                [],
                [],
            ],
        ];
    }
}
