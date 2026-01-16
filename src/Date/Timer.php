<?php
namespace Framework\Date;

use Framework\Utils\Numbers;

/**
 * The Timer
 */
class Timer {

    private float $startTime = 0.0;
    private float $endTime   = 0.0;


    /**
     * Starts the Timer
     */
    public function __construct() {
        $this->startTime = microtime(true);
    }

    /**
     * Ends the Timer
     * @return Timer
     */
    public function end(): Timer {
        if ($this->endTime === 0.0) {
            $this->endTime = microtime(true);
        }
        return $this;
    }


    /**
     * Gets the elapsed time in seconds
     * @return float
     */
    public function getElapsedSeconds(): float {
        $this->end();
        return Numbers::round($this->endTime - $this->startTime, 2);
    }

    /**
     * Gets the elapsed time in seconds
     * @return integer
     */
    public function getElapsedSecondsInt(): int {
        $this->end();
        return Numbers::roundInt($this->endTime - $this->startTime);
    }

    /**
     * Gets the elapsed time in minutes
     * @return float
     */
    public function getElapsedMinutes(): float {
        $this->end();
        $seconds = $this->endTime - $this->startTime;
        return Numbers::round($seconds / 60, 2);
    }

    /**
     * Gets the elapsed time as text
     * @return string
     */
    public function getElapsedText(): string {
        $seconds = $this->getElapsedSeconds();
        $minutes = $this->getElapsedMinutes();

        if ($minutes >= 1.0) {
            return "{$minutes} m ({$seconds} s)";
        }
        return "{$seconds} s";
    }
}
