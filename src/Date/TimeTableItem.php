<?php
namespace Framework\Date;

use Framework\Utils\Arrays;

/**
 * The Time Table Item class
 */
class TimeTableItem {

    public const HolidayIndex = 8;

    /** @var list<int> */
    public array $days;
    public string $from;
    public string $to;
    public bool $hasHoliday;


    /**
     * Constructor
     * @param list<int> $days
     * @param string    $from
     * @param string    $to
     */
    public function __construct(
        array $days,
        string $from,
        string $to,
    ) {
        $this->days = $days;
        $this->from = $from;
        $this->to   = $to;

        $this->hasHoliday = Arrays::contains($days, self::HolidayIndex);
    }
}
