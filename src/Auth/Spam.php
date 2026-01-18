<?php
namespace Framework\Auth;

use Framework\Auth\Schema\CredentialSpamSchema;
use Framework\Auth\Schema\CredentialSpamQuery;
use Framework\Utils\Server;

/**
 * The Auth Spam
 */
class Spam extends CredentialSpamSchema {

    /**
     * Protection against multiple submits in a few seconds
     * @return bool
     */
    public static function protect(): bool {
        $ip = Server::getIP();

        // Delete old entries
        $query = new CredentialSpamQuery();
        $query->time->lessThan(time() - 1);
        $query->ip->equal($ip);
        self::removeEntity($query);

        $query = new CredentialSpamQuery();
        $query->time->lessThan(time() - 3);
        self::removeEntity($query);

        // Check if there is still an entry for the given ip
        if (self::hasIp($ip)) {
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
     * Returns true if the given IP has an entry
     * @param string $ip
     * @return bool
     */
    private static function hasIp(string $ip): bool {
        $query = new CredentialSpamQuery();
        $query->ip->equal($ip);
        return self::entityExists($query);
    }



    /**
     * Reset the spam protections
     * @return bool
     */
    public static function reset(): bool {
        $query = new CredentialSpamQuery();
        $query->ip->equal(Server::getIP());
        return self::removeEntity($query);
    }
}
