<?php
namespace Framework\Core;

use Framework\Auth\Auth;
use Framework\Auth\Credential;

/**
 * The Progress
 */
class Progress {

    /**
     * Gets the Progress
     * @return int
     */
    public static function get(): int {
        return Auth::getCredential()->progressValue;
    }

    /**
     * Sets the Progress
     * @param int $value
     * @return bool
     */
    public static function set(int $value): bool {
        $credentialID = Auth::getID();
        return Credential::setProgress($credentialID, $value);
    }



    /**
     * Starts the Progress
     * @return bool
     */
    public static function start(): bool {
        return self::set(0);
    }

    /**
     * Increments the Progress
     * @param int $value Optional.
     * @return bool
     */
    public static function increment(int $value = 1): bool {
        $currentValue = self::get();
        return self::set($currentValue + $value);
    }

    /**
     * Ends the Progress
     * @return bool
     */
    public static function end(): bool {
        return self::start();
    }
}
