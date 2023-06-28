<?php
namespace Framework\Route;

use Framework\Framework;
use Framework\Route\Router;
use Framework\Utils\Strings;

/**
 * The Dispatcher Service
 */
class Dispatcher {

    /** @var array{}[] */
    private static array $data   = [];
    private static bool  $loaded = false;


    /**
     * Loads the Dispatcher Data
     * @return boolean
     */
    private static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        self::$loaded = true;
        self::$data   = Framework::loadData(Framework::DispatchData);
        return true;
    }



    /**
     * Dispatches the event
     * @param string $event
     * @param mixed  ...$params
     * @return boolean
     */
    private static function dispatch(string $event, mixed ...$params): bool {
        self::load();
        if (!self::$data[$event]) {
            return false;
        }

        foreach (self::$data[$event] as $function) {
            if (Strings::contains($function, "::")) {
                [ $module, $method ] = Strings::split($function, "::");
                $isStatic = true;
            } else {
                [ $module, $method ] = Strings::split($function, "->");
                $isStatic = false;
            }
            Router::execute($isStatic, $module, $method, ...$params);
        }
        return true;
    }

    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $function, array $arguments) {
        return self::dispatch($function, ...$arguments);
    }
}
