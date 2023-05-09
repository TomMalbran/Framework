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
     * Dispatches a Created event
     * @param string  $keyName
     * @param integer $elemID
     * @param integer $parentID Optional.
     * @return boolean
     */
    public static function created(string $keyName, int $elemID, int $parentID = 0): bool {
        return self::dispatch("created", $keyName, $elemID, $parentID);
    }

    /**
     * Dispatches a Edited event
     * @param string  $keyName
     * @param integer $elemID
     * @param integer $parentID Optional.
     * @return boolean
     */
    public static function edited(string $keyName, int $elemID, int $parentID = 0): bool {
        return self::dispatch("edited", $keyName, $elemID, $parentID);
    }

    /**
     * Dispatches a Deleted event
     * @param string  $keyName
     * @param integer $elemID
     * @param integer $parentID Optional.
     * @return boolean
     */
    public static function deleted(string $keyName, int $elemID, int $parentID = 0): bool {
        return self::dispatch("deleted", $keyName, $elemID, $parentID);
    }



    /**
     * Dispatches the event
     * @param string  $event
     * @param string  $keyName
     * @param integer $elemID
     * @param integer $parentID Optional.
     * @return boolean
     */
    private static function dispatch(string $event, string $keyName, int $elemID, int $parentID = 0): bool {
        self::load();
        if (!self::$data[$event]) {
            return false;
        }
        if (!self::$data[$event][$keyName]) {
            return false;
        }

        foreach (self::$data[$event][$keyName] as $function) {
            if (Strings::contains($function, "::")) {
                [ $module, $method ] = Strings::split($function, "::");
                $isStatic = true;
            } else {
                [ $module, $method ] = Strings::split($function, "->");
                $isStatic = false;
            }
            Router::execute($isStatic, $module, $method, $elemID, $keyName, $parentID);
        }
        return true;
    }
}
