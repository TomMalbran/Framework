<?php
namespace Framework\Auth;

use Framework\Log\DeviceLog;
use Framework\Database\Query;
use Framework\Utils\Server;
use Framework\Schema\CredentialDeviceSchema;
use Framework\Schema\CredentialDeviceColumn;

/**
 * The Credential Devices
 */
class Device extends CredentialDeviceSchema {

    /**
     * Checks if the Credential has at least one Device
     * @param integer $credentialID
     * @return boolean
     */
    public static function has(int $credentialID): bool {
        $devices = self::getAllForCredential($credentialID);
        return !empty($devices);
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
        return self::getEntityColumn($query, CredentialDeviceColumn::PlayerID);
    }



    /**
     * Adds a Device
     * @param integer $credentialID
     * @param string  $playerID
     * @return boolean
     */
    public static function add(int $credentialID, string $playerID): bool {
        $result = self::replaceEntity(
            credentialID: $credentialID,
            userAgent:    Server::getUserAgent(),
            playerID:     $playerID,
        );
        DeviceLog::added($credentialID, $playerID);
        return $result;
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
        $result = self::removeEntity($query);
        DeviceLog::removed($credentialID, $playerID);
        return $result;
    }
}
