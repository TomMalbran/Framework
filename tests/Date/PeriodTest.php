<?php
namespace Tests\Date;

use Framework\IO\Request;
use Framework\Date\Date;
use Framework\Date\Period;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;

class PeriodTest extends TestCase {

    /**
     * @dataProvider providerConstruct
     */
    public function testConstruct(array $input, string $prefix, string $expectedPeriod, bool $expectFromEmpty, bool $expectToEmpty, ?int $expectedFromNum, ?int $expectedToNum): void {
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
            "empty"          => [ [], "", Period::Custom, true, true, null, null ],
            "explicit_dates" => [[ "fromDate" => "2020-03-01", "toDate" => "2020-03-05" ], "", Period::Custom, false, false, 20200301, 20200305 ],
            "prefix_dates"   => [[ "pFromDate" => "2020-04-01", "pToDate" => "2020-04-02" ], "p", Period::Custom, false, false, 20200401, 20200402 ],
            "period_only"    => [[ "period" => Period::Last7Days ], "", Period::Last7Days, false, false, null, null ],
        ];
    }


    /**
     * @dataProvider providerFromPeriod
     */
    public function testFromPeriod(string $periodValue, ?int $expectedFrom, ?int $expectedTo): void {
        $p = Period::fromPeriod($periodValue);
        $this->assertSame($periodValue, $p->period);

        if ($expectedFrom === null) {
            $this->assertTrue($p->fromTime->isEmpty());
        } else {
            $this->assertSame($expectedFrom, $p->fromTime->toNumber());
        }

        if ($expectedTo === null) {
            $this->assertTrue($p->toTime->isEmpty());
        } else {
            $this->assertSame($expectedTo, $p->toTime->toNumber());
        }
    }

    public static function providerFromPeriod(): array {
        $today          = intval(date("Ymd"));
        $tomorrow       = intval(date("Ymd", strtotime("+1 day")));
        $last7_from     = intval(date("Ymd", strtotime("-7 days")));
        $thisMonth_from = intval(date("Ymd", strtotime("first day of this month")));
        $thisMonth_to   = intval(date("Ymd", strtotime("last day of this month")));

        return [
            "last7Days" => [ Period::Last7Days, $last7_from, $today ],
            "today"     => [ Period::Today, $today, $today ],
            "thisMonth" => [ Period::ThisMonth, $thisMonth_from, $thisMonth_to ],
            "tomorrow"  => [ Period::Tomorrow, $tomorrow, $tomorrow ],
            "custom"    => [ Period::Custom, $today, $today ],
        ];
    }


    /**
     * @dataProvider providerFromDictionary
     */
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


    /**
     * @dataProvider providerIsEmpty
     */
    public function testIsEmpty(array $input, bool $expected): void {
        $p = new Period(new Request($input));
        $this->assertSame($expected, $p->isEmpty());
    }

    public static function providerIsEmpty(): array {
        return [
            "empty"      => [[], true ],
            "from_only"  => [[ "fromDate" => "2020-01-01" ], false ],
            "to_only"    => [[ "toDate"   => "2020-01-02" ], false ],
            "both_dates" => [[ "fromDate" => "2020-01-01", "toDate" => "2020-01-02" ], false ],
        ];
    }


    /**
     * @dataProvider providerIsValid
     */
    public function testIsValid(string $value, bool $expected): void {
        $this->assertSame($expected, Period::isValid($value));
    }

    public static function providerIsValid(): array {
        return [
            "valid_today" => [ Period::Today, true ],
            "invalid"     => [ "not_a_period", false ],
            "empty"       => [ "", false ],
        ];
    }


    /**
     * @dataProvider providerGetName
     */
    public function testGetName(string $value, string $expected): void {
        $this->assertSame($expected, Period::getName($value));
    }

    public static function providerGetName(): array {
        return [
            "today"   => [ Period::Today, Period::$names[Period::Today] ],
            "unknown" => [ "bogus", "" ],
        ];
    }


    public function testGetSelect(): void {
        $select = Period::getSelect();
        $this->assertIsArray($select);
        $this->assertNotEmpty($select);
    }


    /**
     * @dataProvider providerGetDays
     */
    public function testGetDays(string $period, int $expected): void {
        $this->assertSame($expected, Period::getDays($period));
    }

    public static function providerGetDays(): array {
        return [
            "last7"     => [ Period::Last7Days, 7 ],
            "today"     => [ Period::Today, 1 ],
            "thisMonth" => [ Period::ThisMonth, Date::now()->getMonthDays() ],
        ];
    }


    /**
     * @dataProvider providerGetDaysAmount
     */
    public function testGetDaysAmount(string $periodValue, int $expected): void {
        $p = new Period(new Request([ "period" => $periodValue ]));
        $this->assertSame($expected, $p->getDaysAmount());
    }

    public static function providerGetDaysAmount(): array {
        return [
            "last7" => [ Period::Last7Days, 7 ],
            "today" => [ Period::Today, 1 ],
        ];
    }


    /**
     * @dataProvider providerIterator
     */
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
