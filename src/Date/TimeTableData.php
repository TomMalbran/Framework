<?php
namespace Framework\Date;

use Framework\Date\Date;

/**
 * The Time Table Data class
 */
class TimeTableData {

    /** @var list<string> */
    public array $days;

    /** @var list<int> */
    public array $dayNumbers;

    /** @var list<Date> */
    public array $dates;

    public string $fromHour;
    public string $toHour;
    public string $daysText;
    public string $timeText;
    public string $zone;


    /**
     * Constructor
     * @param list<string> $days       Optional.
     * @param list<int>    $dayNumbers Optional.
     * @param list<Date>   $dates      Optional.
     * @param string       $fromHour   Optional.
     * @param string       $toHour     Optional.
     * @param string       $daysText   Optional.
     * @param string       $timeText   Optional.
     * @param string       $zone       Optional.
     */
    public function __construct(
        array $days = [],
        array $dayNumbers = [],
        array $dates = [],
        string $fromHour = "",
        string $toHour = "",
        string $daysText = "",
        string $timeText = "",
        string $zone = "",
    ) {
        $this->days       = $days;
        $this->dayNumbers = $dayNumbers;
        $this->dates      = $dates;

        $this->fromHour   = $fromHour;
        $this->toHour     = $toHour;
        $this->daysText   = $daysText;
        $this->timeText   = $timeText;
        $this->zone       = $zone;
    }
}
