<?php
namespace Framework\Discovery;

use Attribute;

/**
 * The Priority Attribute
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Priority {

    public const Highest = 0;
    public const High    = 10;
    public const Normal  = 100;
    public const Low     = 1_000;
    public const Lowest  = 10_000;


    public int $priority;


    /**
     * The Priority Attribute
     * @param int $priority
     */
    public function __construct(int $priority = self::Normal) {
        $this->priority = $priority;
    }
}
