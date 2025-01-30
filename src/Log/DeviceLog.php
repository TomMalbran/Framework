<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Database\Factory;
use Framework\Database\Schema;
use Framework\Database\Query;
use Framework\Database\Model;
use Framework\Utils\DateTime;
use Framework\Utils\Server;

/**
 * The Devices Log
 */
class DeviceLog {

    /**
     * Loads the Device Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("LogDevice");
    }



    /**
     * Returns an Device Log with the given ID
     * @param integer $logID
     * @return Model
     */
    public static function getOne(int $logID): Model {
        return self::schema()->getOne($logID);
    }

    /**
     * Returns true if the given Device Log exists
     * @param integer $logID
     * @return boolean
     */
    public static function exists(int $logID): bool {
        return self::schema()->exists($logID);
    }



    /**
     * Returns the List Query
     * @param Request $request
     * @return Query
     */
    private static function createQuery(Request $request): Query {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([ "playerID", "userAgent" ], $search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }

    /**
     * Returns all the Device Log items
     * @param Request $request
     * @return array{}[]
     */
    public static function getAll(Request $request): array {
        $query = self::createQuery($request);
        return self::schema()->getAll($query, $request);
    }

    /**
     * Returns the total amount of Device Log items
     * @param Request $request
     * @return integer
     */
    public static function getTotal(Request $request): int {
        $query = self::createQuery($request);
        return self::schema()->getTotal($query);
    }



    /**
     * Logs the given Action
     * @param integer $credentialID
     * @param string  $playerID
     * @return boolean
     */
    public static function added(int $credentialID, string $playerID): bool {
        if (!self::schema()->tableExists()) {
            return false;
        }
        return self::schema()->create([
            "CREDENTIAL_ID" => $credentialID,
            "userAgent"     => Server::getUserAgent(),
            "playerID"      => $playerID,
            "wasAdded"      => 1,
        ]);
    }

    /**
     * Removes a Device
     * @param integer $credentialID
     * @param string  $playerID
     * @return boolean
     */
    public static function removed(int $credentialID, string $playerID): bool {
        if (!self::schema()->tableExists()) {
            return false;
        }
        return self::schema()->create([
            "CREDENTIAL_ID" => $credentialID,
            "userAgent"     => Server::getUserAgent(),
            "playerID"      => $playerID,
            "wasAdded"      => 1,
        ]);
    }

    /**
     * Deletes the items older than 90 days
     * @param integer $days Optional.
     * @return boolean
     */
    public static function deleteOld(int $days = 90): bool {
        if (!self::schema()->tableExists()) {
            return false;
        }

        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::schema()->remove($query);
    }
}
