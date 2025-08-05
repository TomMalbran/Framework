<?php
namespace Framework\Log;

use Framework\Auth\Auth;
use Framework\Log\Schema\LogSessionSchema;
use Framework\Log\Schema\LogSessionQuery;
use Framework\System\Config;
use Framework\Utils\DateTime;
use Framework\Utils\Server;

/**
 * The Sessions Log
 */
class SessionLog extends LogSessionSchema {

    /**
     * Returns the Session ID for the current Credential
     * @param integer $credentialID
     * @return integer
     */
    public static function getID(int $credentialID): int {
        $query = new LogSessionQuery();
        $query->credentialID->equal($credentialID);
        $query->isOpen->isTrue();
        return self::getSessionID($query);
    }



    /**
     * Starts a new Session Log
     * @param integer $credentialID
     * @return integer
     */
    public static function start(int $credentialID): int {
        return self::createEntity(
            credentialID: $credentialID,
            currentUser:  Auth::getUserID(),
            ip:           Server::getIP(),
            userAgent:    Server::getUserAgent(),
            isOpen:       true,
        );
    }

    /**
     * Ends a Session Log
     * @param integer $sessionID
     * @return boolean
     */
    public static function end(int $sessionID): bool {
        return self::editEntity($sessionID, isOpen: false);
    }

    /**
     * Deletes the items older than 90 days
     * @return boolean
     */
    public static function deleteOld(): bool {
        $days  = Config::getActionLogDeleteDays();
        $time  = DateTime::getLastXDays($days);

        $query = new LogSessionQuery();
        $query->createdTime->lessThan($time);
        return self::removeEntity($query);
    }
}
