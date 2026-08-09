<?php
namespace Tests\Discovery;

use Framework\Discovery\Attr\CronJob;

use Tests\Discovery\Fixture\Jobs;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use Attribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * The CronJob Attribute
 *
 * Nothing in the Framework reads it, so these go by how the other attributes
 * are read, and pin the order of the five fields down before something does.
 */
class CronJobTest extends TestCase {

    /**
     * Returns the CronJob attribute on the given fixture method
     * @param string $method
     * @return CronJob|null
     */
    private function attribute(string $method): ?CronJob {
        $reflection = new ReflectionMethod(Jobs::class, $method);
        $attributes = $reflection->getAttributes(CronJob::class);
        return isset($attributes[0]) ? $attributes[0]->newInstance() : null;
    }



    /**
     * A method carrying the attribute, and the crontab line it spells out
     * @param string $method
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerJobs")]
    public function testTheFieldsAreReadInCrontabOrder(string $method, string $expected): void {
        $cronJob = $this->attribute($method);

        $this->assertNotNull($cronJob);
        $this->assertSame($expected, implode(" ", [
            $cronJob->minute,
            $cronJob->hour,
            $cronJob->dayOfMonth,
            $cronJob->month,
            $cronJob->dayOfWeek,
        ]));
    }

    /**
     * One written positionally and one with its arguments named
     * @return array<string,array{string,string}>
     */
    public static function providerJobs(): array {
        return [
            "every night" => [ "everyNight", "0 3 * * *" ],
            "on weekdays" => [ "onWeekdays", "*/15 * * * 1-5" ],
        ];
    }

    /**
     * Each field of the attribute on the given method
     * @param string $method
     * @param string $field
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerFields")]
    public function testEachFieldIsReadOnItsOwn(string $method, string $field, string $expected): void {
        $cronJob = $this->attribute($method);

        $this->assertNotNull($cronJob);
        $this->assertSame($expected, $cronJob->$field);
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public static function providerFields(): array {
        return [
            "the minute"  => [ "everyNight", "minute", "0" ],
            "the hour"    => [ "everyNight", "hour", "3" ],
            "the day"     => [ "everyNight", "dayOfMonth", "*" ],
            "the month"   => [ "everyNight", "month", "*" ],
            "the weekday" => [ "everyNight", "dayOfWeek", "*" ],
            "a step"      => [ "onWeekdays", "minute", "*/15" ],
            "a range"     => [ "onWeekdays", "dayOfWeek", "1-5" ],
        ];
    }

    public function testAMethodWithoutItHasNothingToRead(): void {
        $this->assertNull($this->attribute("notAJob"));
    }

    public function testItIsBuiltTheSameWayOutsideAnAttribute(): void {
        $cronJob = new CronJob("30", "4", "1", "*", "0");

        $this->assertSame("30", $cronJob->minute);
        $this->assertSame("4", $cronJob->hour);
        $this->assertSame("1", $cronJob->dayOfMonth);
        $this->assertSame("*", $cronJob->month);
        $this->assertSame("0", $cronJob->dayOfWeek);
    }

    public function testItOnlyGoesOnAMethod(): void {
        // A class or a property would be read by nothing, so php refuses it
        $attributes = (new ReflectionClass(CronJob::class))->getAttributes(Attribute::class);

        $this->assertArrayHasKey(0, $attributes);
        $this->assertSame(Attribute::TARGET_METHOD, $attributes[0]->newInstance()->flags);
    }

    public function testEveryFieldIsRequired(): void {
        // None of the five has a default, so a partial line cannot be written
        $constructor = new ReflectionMethod(CronJob::class, "__construct");

        $this->assertSame(5, $constructor->getNumberOfParameters());
        $this->assertSame(5, $constructor->getNumberOfRequiredParameters());
    }
}
