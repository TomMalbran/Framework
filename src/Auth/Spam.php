<?php
namespace Framework\Auth;

use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Utils\Server;

/**
 * The Auth Spam
 */
class Spam {

    private static $loaded = false;
    private static $schema = null;


    /**
     * Loads the Spam Schema
     * @return Schema
     */
    public static function getSchema(): Schema {
        if (!self::$loaded) {
            self::$loaded = false;
            self::$schema = Factory::getSchema("spam");
        }
        return self::$schema;
    }



    /**
     * Proection against multiple logins in a few seconds
     * @return boolean
     */
    public static function protection(): bool {
        $schema = self::getSchema();
        $ip     = Server::getIP();

        // Delete old entries
        $schema->remove(Query::create("time", "<", time() - 2)->add("ip", "=", $ip));
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
}
