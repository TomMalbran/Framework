<?php
namespace Tests\Date;

use Framework\Date\Timer;
use Tests\ReflectionHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TimerTest extends TestCase {
    use ReflectionHelpers;

    public function testEnd(): void {
        $timer = new Timer();
        // make startTime deterministic
        $this->setPrivateProperty($timer, "startTime", 1000.0);
        $this->setPrivateProperty($timer, "endTime", 0.0);

        $timer->end();

        $end = $this->getPrivateProperty($timer, "endTime");
        $this->assertIsFloat($end);
        $this->assertGreaterThan(1000.0, $end);
    }


    #[DataProvider("elapsedSecondsProvider")]
    public function testGetElapsedSeconds(float $start, float $end, float $expected): void {
        $timer = new Timer();
        $this->setPrivateProperty($timer, "startTime", $start);
        $this->setPrivateProperty($timer, "endTime", $end);

        $this->assertSame($expected, $timer->getElapsedSeconds());
    }

    public static function elapsedSecondsProvider(): array {
        return [
            "zero"         => [ 1000.0, 1000.0, 0.0 ],
            "small"        => [ 1000.0, 1000.1234, 0.12 ],
            "one_and_half" => [ 1000.0, 1001.567, 1.57 ],
        ];
    }


    #[DataProvider("elapsedSecondsIntProvider")]
    public function testGetElapsedSecondsInt(float $start, float $end, int $expected): void {
        $timer = new Timer();
        $this->setPrivateProperty($timer, "startTime", $start);
        $this->setPrivateProperty($timer, "endTime", $end);

        $this->assertSame($expected, $timer->getElapsedSecondsInt());
    }

    public static function elapsedSecondsIntProvider(): array {
        return [
            "round_down" => [ 1000.0, 1000.4, 0 ],
            "round_up"   => [ 1000.0, 1000.5, 1 ],
            "several"    => [ 1000.0, 1002.49, 2 ],
        ];
    }


    #[DataProvider("elapsedMinutesProvider")]
    public function testGetElapsedMinutes(float $start, float $end, float $expected): void {
        $timer = new Timer();
        $this->setPrivateProperty($timer, "startTime", $start);
        $this->setPrivateProperty($timer, "endTime", $end);

        $this->assertSame($expected, $timer->getElapsedMinutes());
    }

    public static function elapsedMinutesProvider(): array {
        return [
            "half_minute"  => [ 1000.0, 1030.0, 0.5 ],
            "one_minute"   => [ 1000.0, 1060.0, 1.0 ],
            "two_and_half" => [ 1000.0, 1150.0, 2.5 ],
        ];
    }


    #[DataProvider("elapsedTextProvider")]
    public function testGetElapsedText(float $start, float $end, string $expected): void {
        $timer = new Timer();
        $this->setPrivateProperty($timer, "startTime", $start);
        $this->setPrivateProperty($timer, "endTime", $end);

        $this->assertSame($expected, $timer->getElapsedText());
    }

    public static function elapsedTextProvider(): array {
        return [
            "seconds_only"        => [ 1000.0, 1000.12, "0.12 s" ],
            "minutes_and_seconds" => [ 1000.0, 1060.0, "1 m (60 s)" ],
            "multiple_minutes"    => [ 1000.0, 1123.45, "2.06 m (123.45 s)" ],
        ];
    }
}
