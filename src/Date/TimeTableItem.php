<?php
namespace Framework\Date;

/**
 * The Time Table Item class
 */
class TimeTableItem {

    /** @var int[] */
    public array $days;
    public string $from;
    public string $to;


    /**
     * Constructor
     * @param int[]  $days
     * @param string $from
     * @param string $to
     */
    public function __construct(
        array $days,
        string $from,
        string $to,
    ) {
        $this->days = $days;
        $this->from = $from;
        $this->to   = $to;
    }
}
