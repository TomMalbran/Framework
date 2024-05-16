<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;
use Framework\Utils\Select;

use ReflectionClass;

/**
 * The Base Enum
 */
class Enum {

    /** @var array<string,array<string,string>> Stores existing constants in a static cache per object */
    protected static array $cache = [];

    /**
     * Loads the Data from the Cache
     * @return array<string,string>
     */
    public static function load(): array {
        $class = get_called_class();
        if (empty(self::$cache[$class])) {
            $reflection = new ReflectionClass($class);
            self::$cache[$class] = $reflection->getConstants();
        }
        return self::$cache[$class];
    }



    /**
     * Check if is valid enum value
     * @param mixed $value
     * @return boolean
     */
    public static function isValid(mixed $value): bool {
        $constants = self::load();
        return Arrays::contains($constants, $value);
    }

    /**
     * Returns a single Value
     * @param mixed $value
     * @return mixed
     */
    public static function getOne(mixed $value): mixed {
        $constants = self::load();
        return $constants[$value];
    }

    /**
     * Returns all the Keys
     * @return mixed[]
     */
    public static function getAll(): array {
        $constants = self::load();
        return $constants;
    }

    /**
     * Returns all the Values
     * @return mixed[]
     */
    public static function getValues(): array {
        $constants = self::load();
        return array_values($constants);
    }

    /**
     * Creates a Select for the Enum
     * @return Select[]
     */
    public static function getSelect(): array {
        $constants = self::load();
        return Select::createFromMap($constants);
    }
}
