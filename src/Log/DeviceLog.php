<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Database\Query;
use Framework\Utils\DateTime;
use Framework\Utils\Server;
use Framework\Schema\LogDeviceSchema;

/**
 * The Devices Log
 */
class DeviceLog extends LogDeviceSchema {

    /**
     * Creates the List Query
     * @param Request $request
     * @return Query
     */
    protected static function createListQuery(Request $request): Query {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([ "playerID", "userAgent" ], $search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }



    /**
     * Logs the given Action
     * @param integer $credentialID
     * @param string  $playerID
     * @return boolean
     */
    public static function added(int $credentialID, string $playerID): bool {
        return self::createEntity(
            credentialID: $credentialID,
            userAgent:    Server::getUserAgent(),
            playerID:     $playerID,
            wasAdded:     true,
        );
    }

    /**
     * Removes a Device
     * @param integer $credentialID
     * @param string  $playerID
     * @return boolean
     */
    public static function removed(int $credentialID, string $playerID): bool {
        return self::createEntity(
            credentialID: $credentialID,
            userAgent:    Server::getUserAgent(),
            playerID:     $playerID,
            wasAdded:     false,
        );
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
