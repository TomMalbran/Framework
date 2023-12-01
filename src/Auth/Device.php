<?php
namespace Framework\Auth;

use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Utils\Server;

/**
 * The Credential Devices
 */
class Device {

    /**
     * Loads the Reset Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("devices");
    }



    /**
     * Returns all the Devices
     * @return array{}[]
     */
    public static function getAll(): array {
        return self::schema()->getAll();
    }

    /**
     * Returns all the Devices for the given Credential
     * @param integer[]|integer $credentialID
     * @return string[]
     */
    public static function getAllForCredential(array|int $credentialID): array {
        if (empty($credentialID)) {
            return [];
        }
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        return self::schema()->getColumn($query, "playerID");
    }



    /**
     * Adds a Device
     * @param integer $credentialID
     * @param string  $playerID
     * @return boolean
     */
    public static function add(int $credentialID, string $playerID): bool {
        return self::schema()->replace([
            "CREDENTIAL_ID" => $credentialID,
            "userAgent"     => Server::getUserAgent(),
            "playerID"      => $playerID,
        ]);
    }

    /**
     * Removes a Device
     * @param integer $credentialID
     * @param string  $playerID
     * @return boolean
     */
    public static function remove(int $credentialID, string $playerID): bool {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        $query->add("playerID", "=", $playerID);
        return self::schema()->remove($query);
    }
}
