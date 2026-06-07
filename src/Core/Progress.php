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
     * @return void
     */
    private static function set(int $value): void {
        $credentialID = Auth::getID();
        Credential::setProgress($credentialID, $value);
    }



    /**
     * Starts the Progress
     * @return void
     */
    public static function start(): void {
        // Force PHP to stop executing if the client disconnects
        ignore_user_abort(enable: false);
        ob_end_clean();

        self::set(0);
    }

    /**
     * Updates the Progress
     * @param int $value
     * @return void
     */
    public static function update(int $value): void {
        // Force PHP to send a tiny echo or heartbeat to the web server.
        // Without this flush, PHP won't realize the client socket is dead!
        echo " ";
        flush();

        // Check if the browser canceled the request and if so, stop the script.
        if (connection_aborted() === 1) {
            exit;
        }

        self::set($value);
    }

    /**
     * Increments the Progress
     * @param int $value Optional.
     * @return void
     */
    public static function increment(int $value = 1): void {
        $currentValue = self::get();
        self::update($currentValue + $value);
    }

    /**
     * Ends the Progress
     * @return void
     */
    public static function end(): void {
        self::set(0);
    }
}
