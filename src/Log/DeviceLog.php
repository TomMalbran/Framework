<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Log\Schema\LogDeviceSchema;
use Framework\Log\Schema\LogDeviceColumn;
use Framework\Log\Schema\LogDeviceQuery;
use Framework\System\Config;
use Framework\Date\DateTime;
use Framework\Utils\Server;

/**
 * The Devices Log
 */
class DeviceLog extends LogDeviceSchema {

    /**
     * Creates the List Query
     * @param Request $request
     * @return LogDeviceQuery
     */
    protected static function createListQuery(Request $request): LogDeviceQuery {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = new LogDeviceQuery();
        $query->search([
            LogDeviceColumn::PlayerID,
            LogDeviceColumn::UserAgent,
        ], $search);

        $query->createdTime->greaterThan($fromTime, $fromTime > 0);
        $query->createdTime->lessThan($toTime, $toTime > 0);
        return $query;
    }



    /**
     * Logs the given Action
     * @param integer $credentialID
     * @param string  $playerID
     * @return integer
     */
    public static function added(int $credentialID, string $playerID): int {
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
     * @return integer
     */
    public static function removed(int $credentialID, string $playerID): int {
        return self::createEntity(
            credentialID: $credentialID,
            userAgent:    Server::getUserAgent(),
            playerID:     $playerID,
            wasAdded:     false,
        );
    }

    /**
     * Deletes the items older than 90 days
     * @return boolean
     */
    public static function deleteOld(): bool {
        $days  = Config::getDeviceLogDeleteDays();
        $time  = DateTime::getLastXDays($days);

        $query = new LogDeviceQuery();
        $query->createdTime->lessThan($time);
        return self::removeEntity($query);
    }
}
