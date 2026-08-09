<?php
namespace Tests\Analysis\Fixture\Enum;

/** A plain enum, with no IsEnum trait, so the None case is not asked of it */
enum Weekday: string {
    case Monday = "mon";
    case Friday = "fri";

    /** An enum without the trait has only the php methods, so it may use them */
    public static function count(): int {
        return count(self::cases());
    }
}
