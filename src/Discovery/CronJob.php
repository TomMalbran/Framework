<?php
namespace Framework\Discovery;

use Attribute;

/**
 * The CronJob Attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
class CronJob {

    public string $minute;
    public string $hour;
    public string $dayOfMonth;
    public string $month;
    public string $dayOfWeek;



    /**
     * The CronJob Attribute
     * @param string $minute
     * @param string $hour
     * @param string $dayOfMonth
     * @param string $month
     * @param string $dayOfWeek
     */
    public function __construct(
        string $minute,
        string $hour,
        string $dayOfMonth,
        string $month,
        string $dayOfWeek,
    ) {
        $this->minute     = $minute;
        $this->hour       = $hour;
        $this->dayOfMonth = $dayOfMonth;
        $this->month      = $month;
        $this->dayOfWeek  = $dayOfWeek;
    }
}
