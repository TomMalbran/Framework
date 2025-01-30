<?php
namespace Framework\Log;

use Framework\Auth\Auth;
use Framework\Database\Query;
use Framework\Utils\DateTime;
use Framework\Utils\Server;
use Framework\Schema\LogSessionSchema;

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
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $query->add("isOpen", "=", 1);
        return (int)self::getEntityValue($query, "SESSION_ID");
    }



    /**
     * Starts a new Session Log
     * @param integer $credentialID
     * @return integer
     */
    public static function start(int $credentialID): int {
        return self::createEntity(
            credentialID: $credentialID,
            userID:       Auth::getUserID(),
            ip:           Server::getIP(),
            userAgent:    Server::getUserAgent(),
            isOpen:       true,
        );
    }

    /**
     * Ends a Session Log
     * @param integer $sessionID
     * @return integer
     */
    public static function end(int $sessionID): int {
        return self::editEntity($sessionID, isOpen: false);
    }

    /**
     * Deletes the items older than 90 days
     * @param integer $days Optional.
     * @return boolean
     */
    public static function deleteOld(int $days = 90): bool {
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::removeEntity($query);
    }
}
