<?php
namespace Framework\Auth;

use Framework\Database\Query;
use Framework\Utils\Server;
use Framework\Schema\CredentialSpamSchema;

/**
 * The Auth Spam
 */
class Spam extends CredentialSpamSchema {

    /**
     * Protection against multiple submits in a few seconds
     * @return boolean
     */
    public static function protect(): bool {
        $ip = Server::getIP();

        // Delete old entries
        self::removeEntity(Query::create("time", "<", time() - 1)->add("ip", "=", $ip));
        self::removeEntity(Query::create("time", "<", time() - 3));

        // Check if there is still an entry for the given ip
        if (self::entityExists(Query::create("ip", "=", $ip))) {
            return true;
        }

        // Add a new entry
        self::replaceEntity(
            ip:   $ip,
            time: time(),
        );
        return false;
    }

    /**
     * Reset the spam protections
     * @return boolean
     */
    public static function reset(): bool {
        $ip    = Server::getIP();
        $query = Query::create("ip", "=", $ip);
        return self::removeEntity($query);
    }
}
