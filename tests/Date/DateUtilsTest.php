<?php
namespace Tests\Date;

use Framework\Date\DateUtils;

use PHPUnit\Framework\TestCase;

class DateUtilsTest extends TestCase {

    /** @dataProvider providerTimeToMinutes */
    public function testTimeToMinutes(string $time, ?float $timeZone, int $expected): void {
        $this->assertSame($expected, DateUtils::timeToMinutes($time, $timeZone));
    }

    public static function providerTimeToMinutes(): array {
        return [
            [ "00:00", null, 0 ],
            [ "01:30", null, 90 ],
            // timezone 0 -> serverZone (-3) => -180 minutes adjustment
            [ "12:00", 0.0, 540 ],
            // timezone 3 -> serverZone (-3) => -360 minutes adjustment
            [ "12:00", 3.0, 360 ],
            // timezone -3 -> serverZone (-3) => 0 minutes adjustment
            [ "12:00", -3.0, 720 ],
            // invalid time
            [ "invalid", null, 0 ],
            // partial invalid time
            [ "12:xx", null, 720 ],
            // with seconds
            [ "01:30:45", null, 90 ],
            // with extra part
            [ "01:30:45:123", null, 0 ],
        ];
    }


    /** @dataProvider providerMinutesToTime */
    public function testMinutesToTime(int $minutes, string $expected): void {
        $this->assertSame($expected, DateUtils::minutesToTime($minutes));
    }

    public static function providerMinutesToTime(): array {
        return [
            [ 0, "00:00" ],
            [ 90, "01:30" ],
            [ 1500, "25:00" ],
            [ 2000, "33:20" ],
            // negative minutes
            [ -30, "-00:30" ],
            [ -90, "-01:30" ],
            // big negative minutes
            [ -1500, "-25:00" ],
        ];
    }


    /** @dataProvider providerIsValidDate */
    public function testIsValidDate(string $text, bool $expected): void {
        $this->assertSame($expected, DateUtils::isValidDate($text));
    }

    public static function providerIsValidDate(): array {
        return [
            [ "", false ],
            [ "not a date", false ],
            [ "2020-01-01", true ],
            [ "2020-02-30", true ],
            [ "2020-13-01", false ],
            [ "2020-00-01", true ],
            [ "2020/01/01", true ],
            [ "5 Apr 2020", true ],
            [ "5 Apr 2020", true ],
            [ "2020-00-01", true ],
            [ "2020/01/01", true ],
            [ "+1 day", true ],
            [ "next Monday", true ],
        ];
    }


    /** @dataProvider providerIsValidDay */
    public function testIsValidDay(int|string $value, bool $withHolidays, bool $startMonday, bool $expected): void {
        $this->assertSame($expected, DateUtils::isValidDay($value, $withHolidays, $startMonday));
    }

    public static function providerIsValidDay(): array {
        return [
            [ 0, false, false, true ],
            [ 7, false, false, false ],
            [ 7, true, false, true ],
            [ 1, false, true, true ],
            [ 0, false, true, false ],
            [ "5", false, false, true ],
            [ "x", false, false, false ],
            [ -1, false, false, false ],
        ];
    }


    /** @dataProvider providerIsValidHour */
    public function testIsValidHour(string $text, ?array $minutes, int $minHour, int $maxHour, bool $expected): void {
        $this->assertSame($expected, DateUtils::isValidHour($text, $minutes, $minHour, $maxHour));
    }

    public static function providerIsValidHour(): array {
        return [
            [ "12:30", null, 0, 23, true ],
            [ "", null, 0, 23, false ],
            [ "24:00", null, 0, 23, false ],
            [ "12:30", [ "30" ], 0, 23, true ],
            [ "12:31", [ "30" ], 0, 23, false ],
            // additional edge cases
            [ "00:00", null, 0, 23, true ],
            [ "23:59", null, 0, 23, true ],
            [ "23:60", null, 0, 23, false ],
            [ "7:00", null, 0, 23, true ],
            [ "07:00", null, 8, 17, false ],
            [ "08:00", null, 8, 17, true ],
            [ "12:00", [ "00", "30" ], 0, 23, true ],
            [ "12:15", [ "00", "30" ], 0, 23, false ],
            [ "01:30:45", null, 0, 23, true ],
            [ "01", null, 0, 23, false ],
            [ "-1:00", null, 0, 23, false ],
        ];
    }


    /** @dataProvider providerIsValidPeriod */
    public function testIsValidPeriod(string $from, string $to, bool $expected): void {
        $this->assertSame($expected, DateUtils::isValidPeriod($from, $to));
    }

    public static function providerIsValidPeriod(): array {
        return [
            [ "2020-01-01", "2020-01-02", true ],
            [ "2020-01-02", "2020-01-01", false ],
            [ "2020-01-01", "2020-01-01", true ],
            [ "invalid", "2020-01-01", false ],
            [ "2020-01-01", "invalid", false ],
            [ "", "", false ],
        ];
    }


    /** @dataProvider providerIsValidHourPeriod */
    public function testIsValidHourPeriod(string $from, string $to, bool $allow24, bool $expected): void {
        $this->assertSame($expected, DateUtils::isValidHourPeriod($from, $to, $allow24));
    }

    public static function providerIsValidHourPeriod(): array {
        return [
            [ "09:00", "10:00", false, true ],
            [ "10:00", "09:00", false, false ],
            [ "23:00", "24:00", true, true ],
            [ "00:00", "00:00", false, false ],
            [ "25:00", "26:00", false, false ],
            [ "invalid", "10:00", false, false ],
            [ "10:00", "invalid", false, false ],
            [ "", "", false, false ],
        ];
    }


    /** @dataProvider providerIsValidFullPeriod */
    public function testIsValidFullPeriod(string $fromDate, string $fromHour, string $toDate, string $toHour, bool $expected): void {
        $this->assertSame($expected, DateUtils::isValidFullPeriod($fromDate, $fromHour, $toDate, $toHour));
    }

    public static function providerIsValidFullPeriod(): array {
        return [
            // same day, from < to
            [ "2020-01-01", "09:00", "2020-01-01", "17:00", true ],
            // same day, from > to
            [ "2020-01-01", "17:00", "2020-01-01", "09:00", false ],
            // spanning multiple days
            [ "2020-01-01", "09:00", "2020-01-02", "08:00", true ],
            // reversed dates
            [ "2020-01-02", "00:00", "2020-01-01", "23:59", false ],
            // same date and same time (not allowed)
            [ "2020-01-01", "09:00", "2020-01-01", "09:00", false ],
            // invalid inputs: bad date or hour
            [ "invalid", "09:00", "2020-01-01", "10:00", false ],
            [ "2020-01-01", "invalid", "2020-01-01", "10:00", false ],
            [ "2020-01-01", "09:00", "invalid", "10:00", false ],
            [ "2020-01-01", "09:00", "2020-01-01", "invalid", false ],
            // empty parts
            [ "", "09:00", "2020-01-01", "10:00", false ],
            [ "2020-01-01", "invalid", "2020-01-01", "10:00", false ],
        ];
    }


    /** @dataProvider providerIsValidWeekDay */
    public function testIsValidWeekDay(int $wd, bool $startMonday, bool $expected): void {
        $this->assertSame($expected, DateUtils::isValidWeekDay($wd, $startMonday));
    }

    public static function providerIsValidWeekDay(): array {
        return [
            // Start Sunday: 0=Sunday, 6=Saturday
            [ -1, false, false ],
            [ 0, false, true ],
            [ 7, false, false ],
            // Start Monday: 1=Monday, 7=Sunday
            [ -1, true, false ],
            [ 0, true, false ],
            [ 7, true, true ],
            [ 8, true, false ],
        ];
    }


    /** @dataProvider providerGetDayName */
    public function testGetDayName(int $day, bool $startMonday, int $length, bool $upper, bool $isEmpty): void {
        $result = DateUtils::getDayName($day, $startMonday, $length, $upper);
        $this->assertIsString($result);

        if ($length > 0) {
            $this->assertLessThanOrEqual($length, mb_strlen($result));
        }
        if ($upper) {
            $this->assertSame(mb_strtoupper($result), $result);
        }

        if ($isEmpty) {
            $this->assertEmpty($result);
        } else {
            $this->assertNotEmpty($result);
        }
    }

    public static function providerGetDayName(): array {
        return [
            // Sunday (startMonday = false)
            [ 0, false, 0, false, false ],
            // Monday when starting week on Monday
            [ 1, true, 0, false, true ],
            // Saturday with short length
            [ 6, false, 3, false, false ],
            // Monday abbreviated but uppercase
            [ 1, false, 2, true, false ],
            // Invalid day
            [ 7, false, 0, false, true ],
            // Invalid day with startMonday = true
            [ 0, true, 0, false, true ],
            // Negative day
            [ -1, false, 0, false, true ],
        ];
    }


    /** @dataProvider providerGetMonthName */
    public function testGetMonthName(int $month, int $length, bool $upper, bool $isEmpty): void {
        $result = DateUtils::getMonthName($month, $length, $upper);
        $this->assertIsString($result);

        if ($length > 0) {
            $this->assertLessThanOrEqual($length, mb_strlen($result));
        }
        if ($upper) {
            $this->assertSame(mb_strtoupper($result), $result);
        }

        if ($isEmpty) {
            $this->assertEmpty($result);
        } else {
            $this->assertNotEmpty($result);
        }
    }

    public static function providerGetMonthName(): array {
        return [
            // January
            [ 1, 0, false, false ],
            // April
            [ 4, 0, false, false ],
            // December abbreviated
            [ 12, 3, false, false ],
            // March uppercase
            [ 3, 0, true, false ],
            // Invalid month
            [ 13, 0, false, true ],
            // Invalid month with length
            [ 13, 3, false, true ],
            // Negative month
            [ -1, 0, false, true ],
        ];
    }


    /** @dataProvider providerGetDayString */
    public function testGetDayString(int $seconds, string $expected): void {
        $this->assertSame($expected, DateUtils::getDayString($seconds));
    }

    public static function providerGetDayString(): array {
        return [
            [ 0, "0d" ],
            [ 86399, "0d" ],
            [ 86400, "1d" ],
            [ 172800, "2d" ],
            [ -200, "-1d" ],
        ];
    }


    /** @dataProvider providerGetMinString */
    public function testGetMinString(int|float $minutes, int $decimals, string $expected): void {
        $this->assertSame($expected, DateUtils::getMinString($minutes, $decimals));
    }

    public static function providerGetMinString(): array {
        return [
            // In minutes
            [ -33, 0, "-33m" ],
            [ 0, 0, "0m" ],
            [ 30, 0, "30m" ],
            [ 119, 0, "119m" ],
            // In hours
            [ -150, 1, "-2.5h" ],
            [ 120, 0, "2h" ],
            [ 1440, 0, "24h" ],
            [ 150, 1, "2.5h" ],
            [ 333, 2, "5.55h" ],
            // In days
            [ -4320, 0, "-3d" ],
            [ 4320, 0, "3d" ],
            [ 4330, 1, "3d" ],
            [ 4608, 1, "3.2d" ],
            [ 10000, 2, "6.94d" ],
        ];
    }


    /** @dataProvider providerGetSecString */
    public function testGetSecString(int $seconds, int $decimals, string $expected): void {
        $this->assertSame($expected, DateUtils::getSecString($seconds, $decimals));
    }

    public static function providerGetSecString(): array {
        return [
            // In seconds
            [ -30, 0, "-30s" ],
            [ 0, 0, "0s" ],
            [ 30, 0, "30s" ],
            [ 119, 0, "119s" ],
            // In minutes
            [ -150, 1, "-2.5m" ],
            [ 120, 0, "2m" ],
            [ 150, 1, "2.5m" ],
            [ 333, 2, "5.55m" ],
            [ -4320, 0, "-72m" ],
            // In hours
            [ -9000, 1, "-2.5h" ],
            [ 9000, 1, "2.5h" ],
        ];
    }


    /** @dataProvider providerParseDate */
    public function testParseDate(string $text, string $expectedYmd): void {
        $ts = DateUtils::parseDate($text);
        if ($expectedYmd === "") {
            $this->assertSame(0, $ts);
        } else {
            $this->assertSame($expectedYmd, date("Y-m-d", $ts));
        }
    }

    public static function providerParseDate(): array {
        $currentYear = date("Y");

        return [
            // Date with slashes
            "slashes-1"  => [ "05/04/2020", "2020-04-05" ],
            "slashes-2"  => [ "5/4/2020", "2020-04-05" ],
            "slashes-3"  => [ "2020/04/05", "2020-04-05" ],
            "slashes-4"  => [ "05/04/20", "2020-04-05" ],
            "slashes-5"  => [ "05/04/60", "1960-04-05" ],
            "slashes-6"  => [ "05/420", "2020-04-05" ],
            "slashes-7"  => [ "05/1120", "2020-11-05" ],
            "slashes-8"  => [ "05/42020", "2020-04-05" ],
            "slashes-9"  => [ "05/112020", "2020-11-05" ],
            "slashes-10" => [ "5/4", "$currentYear-04-05" ],
            "slashes-11" => [ "5/", "" ],
            "slashes-12" => [ "/", "" ],
            "slashes-13" => [ "//", "" ],
            "slashes-14" => [ "1/1/2222", "" ],

            // Date with dashes
            "dashes-1"  => [ "05-04-2020", "2020-04-05" ],
            "dashes-2"  => [ "5-4-2020", "2020-04-05" ],
            "dashes-3"  => [ "2020-04-05", "2020-04-05" ],
            "dashes-4"  => [ "05-04-20", "2020-04-05" ],
            "dashes-5"  => [ "05-04-60", "1960-04-05" ],
            "dashes-6"  => [ "05-4-20", "2020-04-05" ],
            "dashes-7"  => [ "05-1120", "2020-11-05" ],
            "dashes-8"  => [ "05-42020", "2020-04-05" ],
            "dashes-9"  => [ "05-112020", "2020-11-05" ],
            "dashes-10" => [ "5-4", "$currentYear-04-05" ],
            "dashes-11" => [ "5-", "" ],
            "dashes-12" => [ "-", "" ],
            "dashes-13" => [ "--", "" ],

            // Date with names
            "named-1"  => [ "5 April 2020", "2020-04-05" ],
            "named-2"  => [ "5 Apr 2020", "2020-04-05" ],
            "named-3"  => [ "5 APR 2020", "2020-04-05" ],
            "named-4"  => [ "5 APR", "$currentYear-04-05" ],
            "named-5"  => [ "2020 apr 5", "2020-04-05" ],
            "named-6"  => [ "5 apr 20", "2020-04-05" ],
            "named-7"  => [ "60 apr 5", "1960-04-05" ],
            "named-8"  => [ "960 apr 5", "1960-04-05" ],
            "named-9"  => [ "April 2020", "" ],
            "named-10" => [ "April 2120", "" ],
            "named-11" => [ "-5 April 2020", "" ],
            "named-12" => [ "April", "" ],
            "named-13" => [ "5", "" ],
            "named-14" => [ "", "" ],
        ];
    }
}
