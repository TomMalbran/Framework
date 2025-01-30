<?php
namespace Framework\Auth;

use Framework\Database\Factory;
use Framework\Database\Schema;
use Framework\Database\Query;
use Framework\Utils\Server;

/**
 * The Auth Spam
 */
class Spam {

    /**
     * Loads the Spam Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("CredentialSpam");
    }



    /**
     * Protection against multiple submits in a few seconds
     * @return boolean
     */
    public static function protect(): bool {
        $schema = self::schema();
        $ip     = Server::getIP();

        // Delete old entries
        $schema->remove(Query::create("time", "<", time() - 1)->add("ip", "=", $ip));
        $schema->remove(Query::create("time", "<", time() - 3));

        // Check if there is still an entry for the given ip
        if ($schema->exists(Query::create("ip", "=", $ip))) {
            return true;
        }

        // Add a new entry
        $schema->replace([
            "ip"   => $ip,
            "time" => time(),
        ]);
        return false;
    }

    /**
     * Reset the spam protections
     * @return boolean
     */
    public static function reset(): bool {
        $ip = Server::getIP();
        return self::schema()->remove(Query::create("ip", "=", $ip));
    }
}
