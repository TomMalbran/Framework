<?php
namespace Tests\Discovery\Fixture;

use Framework\Discovery\Attr\CronJob;

/**
 * The methods the CronJob tests read their attributes off
 *
 * Nothing in the Framework reads the attribute yet, so what these pin down is
 * the shape whatever ends up reading it would go by.
 */
class Jobs {

    /**
     * Every night at three
     * @return void
     */
    #[CronJob("0", "3", "*", "*", "*")]
    public static function everyNight(): void {
    }

    /**
     * Every quarter of an hour, from monday to friday
     * @return void
     */
    #[CronJob(
        minute: "*/15",
        hour: "*",
        dayOfMonth: "*",
        month: "*",
        dayOfWeek: "1-5",
    )]
    public static function onWeekdays(): void {
    }

    /**
     * A method the attribute was not put on
     * @return void
     */
    public static function notAJob(): void {
    }
}
