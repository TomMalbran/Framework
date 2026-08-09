<?php
namespace Tests\Discovery\Fixture;

/**
 * The handlers the ConsoleCommand tests point at
 *
 * They are all static, since invoke() calls them without an instance, and they
 * record what they were given rather than doing anything.
 */
class Commands {

    /** @var array<string,mixed> */
    public static array $received = [];

    public static bool $wasCalled = false;


    /**
     * Resets what the handlers recorded
     * @return void
     */
    public static function reset(): void {
        self::$received  = [];
        self::$wasCalled = false;
    }


    /**
     * A handler that takes nothing at all
     * @return void
     */
    public static function noArgs(): void {
        self::$wasCalled = true;
    }

    /**
     * A handler with one required argument and two optional ones
     * @param string $name
     * @param int    $amount Optional.
     * @param bool   $force  Optional.
     * @return void
     */
    public static function withArgs(string $name, int $amount = 0, bool $force = false): void {
        self::$wasCalled = true;
        self::$received  = [ "name" => $name, "amount" => $amount, "force" => $force ];
    }

    /**
     * A handler asking for a decimal
     * @param float $rate Optional.
     * @return void
     */
    public static function withFloat(float $rate = 0.0): void {
        self::$wasCalled = true;
        self::$received  = [ "rate" => $rate ];
    }

    /**
     * A handler whose argument is spelled in camel case
     * @param string $userName
     * @return void
     */
    public static function withCamelCase(string $userName): void {
        self::$wasCalled = true;
        self::$received  = [ "userName" => $userName ];
    }
}
