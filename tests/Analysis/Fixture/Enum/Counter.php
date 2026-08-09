<?php
namespace Tests\Analysis\Fixture\Enum;

class Counter {

    public const STEP = 2;

    public static function next(): int {
        return self::STEP;
    }

    /** Named like the enum methods, but this is a plain class */
    public static function cases(): int {
        return 0;
    }
}
