<?php
namespace Tests\Date;

use Framework\Date\TimeZone;
use Tests\ReflectionHelpers;

use PHPUnit\Framework\TestCase;

class TimeZoneTest extends TestCase {
    use ReflectionHelpers;

    protected function setUp(): void {
        // reset internal static state before each test
        $this->setPrivateStaticProperty(TimeZone::class, "stackZones", []);
        $this->setPrivateStaticProperty(TimeZone::class, "serverZone", -3.0);
        $this->setPrivateStaticProperty(TimeZone::class, "timeDiff", 0.0);
    }


    /**
     * @dataProvider setTimeZoneProvider
     */
    public function testSetTimeZone(float $input, float $expected): void {
        $result = TimeZone::setTimeZone($input);
        $this->assertSame($expected, $result);
    }

    public static function setTimeZoneProvider(): array {
        return [
            // input in minutes -> normalized to hours (-180 -> -3)
            "minutes" => [ -180.0, 0.0 ],
            // input in hours (note: current implementation divides also when <60)
            "hours"   => [ -3.0, -2.95 ],
        ];
    }


    /**
     * @dataProvider pushPopProvider
     */
    public function testPushAndPopTimeZone(array $initialStack, float $pushValue, float $expectedAfterPush, float $expectedAfterPop): void {
        // set initial stack
        $this->setPrivateStaticProperty(TimeZone::class, "stackZones", $initialStack);

        $extraPop = TimeZone::popTimeZone();
        $this->assertSame(0.0, $extraPop);

        $afterPush = TimeZone::pushTimeZone($pushValue);
        $this->assertSame($expectedAfterPush, $afterPush);

        $afterPop = TimeZone::popTimeZone();
        $this->assertSame($expectedAfterPop, $afterPop);
    }

    public static function pushPopProvider(): array {
        return [
            "empty_stack" => [ [], -180.0, 0.0, 0.0 ],
            "one_item"    => [ [ -3.0 ], -120.0, -1.0, -1.0 ],
        ];
    }


    /**
     * @dataProvider currentDiffProvider
     */
    public function testGetCurrentTimeDiff(float $value): void {
        $this->setPrivateStaticProperty(TimeZone::class, "timeDiff", $value);
        $this->assertSame($value, TimeZone::getCurrentTimeDiff());
    }

    public static function currentDiffProvider(): array {
        return [
            "zero"     => [ 0.0 ],
            "negative" => [ -2.5 ],
        ];
    }


    /**
     * @dataProvider calcProvider
     */
    public function testCalcTimeDiff(float $input, float $expected): void {
        $this->assertSame($expected, TimeZone::calcTimeDiff($input));
    }

    public static function calcProvider(): array {
        return [
            "same_as_server" => [ -3.0, 0.0 ],
            "zero"           => [ 0.0, -3.0 ],
            "positive"       => [ 2.5, -5.5 ],
        ];
    }


    /**
     * @dataProvider toUserProvider
     */
    public function testToUserTime(float $timeDiff, int $input, bool $useTimeZone, int $expected): void {
        $this->setPrivateStaticProperty(TimeZone::class, "timeDiff", $timeDiff);
        $this->assertSame($expected, TimeZone::toUserTime($input, $useTimeZone));
    }

    public static function toUserProvider(): array {
        return [
            "zero_value"  => [ 1.0, 0, true, 0 ],
            "apply_diff"  => [ -1.0, 3600, true, 7200 ],
            "ignore_zone" => [ -1.0, 3600, false, 3600 ],
        ];
    }


    /**
     * @dataProvider toServerProvider
     */
    public function testToServerTime(float $timeDiff, int $input, bool $useTimeZone, int $expected): void {
        $this->setPrivateStaticProperty(TimeZone::class, "timeDiff", $timeDiff);
        $this->assertSame($expected, TimeZone::toServerTime($input, $useTimeZone));
    }

    public static function toServerProvider(): array {
        return [
            "zero_value"  => [ 1.0, 0, true, 0 ],
            "apply_diff"  => [ -1.0, 3600, true, 0 ],
            "ignore_zone" => [ -1.0, 3600, false, 3600 ],
        ];
    }


    /**
     * @dataProvider toStringProvider
     */
    public function testToString(float $input, string $expected): void {
        $this->assertSame($expected, TimeZone::toString($input));
    }

    public static function toStringProvider(): array {
        return [
            "negative_hours" => [ -3.0, "GMT -3:00" ],
            "positive_half"  => [ 5.5, "GMT +5:30" ],
            "zero"           => [ 0.0, "GMT +0:00" ],
        ];
    }
}
