<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Auth\Auth;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;
use Framework\Utils\Server;

/**
 * The Actions Log
 */
class ActionLog {

    /**
     * Loads the IDs Schemas
     * @return Schema
     */
    public static function idSchema(): Schema {
        return Factory::getSchema("logIDs");
    }

    /**
     * Loads the Sessions Schemas
     * @return Schema
     */
    public static function sessionSchema(): Schema {
        return Factory::getSchema("logSessions");
    }

    /**
     * Loads the Actions Schemas
     * @return Schema
     */
    public static function actionSchema(): Schema {
        return Factory::getSchema("logActions");
    }



    /**
     * Returns the List Query
     * @param Request $request
     * @param array{} $mappings Optional.
     * @return Query
     */
    private static function createQuery(Request $request, array $mappings = []): Query {
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = new Query();
        $query->addIf("CREDENTIAL_ID", "=", $request->credentialID);
        foreach ($mappings as $key => $value) {
            $query->addIf($value, "=", $request->get($key));
        }

        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }

    /**
     * Returns all the Actions Log items
     * @param Request $request
     * @param array{} $mappings Optional.
     * @return array{}[]
     */
    public static function getAll(Request $request, array $mappings = []): array {
        $query = self::createQuery($request, $mappings);
        $query->orderBy("createdTime", false);
        $query->paginate($request->getInt("page"), $request->getInt("amount"));
        return self::request($query);
    }

    /**
     * Returns the total amount of Actions Log items
     * @param Request $request
     * @param array{} $mappings Optional.
     * @return integer
     */
    public static function getTotal(Request $request, array $mappings = []): int {
        $query = self::createQuery($request, $mappings);
        return self::sessionSchema()->getTotal($query);
    }

    /**
     * Returns the Actions Log using the given Query
     * @param Query $query
     * @return array{}[]
     */
    private static function request(Query $query): array {
        $sessionIDs   = self::sessionSchema()->getColumn($query, "SESSION_ID");
        $querySession = Query::create("SESSION_ID", "IN", $sessionIDs)->orderBy("createdTime", false);
        $queryActs    = Query::create("SESSION_ID", "IN", $sessionIDs)->orderBy("createdTime", true);
        $actions      = [];
        $result       = [];

        if (empty($sessionIDs)) {
            return [];
        }

        $request = self::actionSchema()->getMap($queryActs);
        foreach ($request as $row) {
            if (empty($actions[$row["sessionID"]])) {
                $actions[$row["sessionID"]] = [];
            }
            $actions[$row["sessionID"]][] = [
                "module"      => $row["module"],
                "action"      => $row["action"],
                "dataID"      => !empty($row["dataID"]) ? JSON::decode($row["dataID"]) : "",
                "createdTime" => $row["createdTime"],
            ];
        }

        $request = self::sessionSchema()->getMap($querySession);
        foreach ($request as $row) {
            $result[] = [
                "sessionID"      => $row["sessionID"],
                "credentialID"   => $row["credentialID"],
                "credentialName" => $row["credentialName"],
                "ip"             => $row["ip"],
                "userAgent"      => $row["userAgent"],
                "actions"        => !empty($actions[$row["sessionID"]]) ? $actions[$row["sessionID"]] : [],
                "createdTime"    => $row["createdTime"],
            ];
        }

        return $result;
    }



    /**
     * Starts a Log Session
     * @param integer $credentialID
     * @param boolean $destroy      Optional.
     * @return boolean
     */
    public static function startSession(int $credentialID, bool $destroy = false): bool {
        $sessionID = self::getSessionID();

        if ($destroy || empty($sessionID)) {
            $sessionID = self::sessionSchema()->create([
                "CREDENTIAL_ID" => $credentialID,
                "USER_ID"       => Auth::getUserID(),
                "ip"            => Server::getIP(),
                "userAgent"     => Server::getUserAgent(),
            ]);
            self::setSessionID($sessionID);
            return true;
        }
        return false;
    }

    /**
     * Ends the Log Session
     * @return boolean
     */
    public static function endSession(): bool {
        return self::setSessionID();
    }



    /**
     * Logs the given Action
     * @param string        $module
     * @param string        $action
     * @param mixed|integer $dataID       Optional.
     * @param integer       $credentialID Optional.
     * @return boolean
     */
    public static function add(string $module, string $action, mixed $dataID = 0, int $credentialID = 0): bool {
        $sessionID = self::getSessionID($credentialID);
        if (empty($sessionID)) {
            return false;
        }

        $dataID = Arrays::toArray($dataID);
        foreach ($dataID as $index => $value) {
            $dataID[$index] = (int)$value;
        }

        self::actionSchema()->create([
            "SESSION_ID"    => $sessionID,
            "CREDENTIAL_ID" => Auth::getID(),
            "USER_ID"       => Auth::getUserID(),
            "module"        => $module,
            "action"        => $action,
            "dataID"        => JSON::encode($dataID),
        ]);
        return true;
    }

    /**
     * Returns the Session ID for the current Credential
     * @param integer $credentialID Optional.
     * @return integer
     */
    public static function getSessionID(int $credentialID = 0): int {
        $id    = !empty($credentialID) ? $credentialID : Auth::getID();
        $query = Query::create("CREDENTIAL_ID", "=", $id);
        return (int)self::idSchema()->getValue($query, "SESSION_ID");
    }

    /**
     * Sets the given Session ID for the current Credential
     * @param integer $sessionID Optional.
     * @return boolean
     */
    public static function setSessionID(int $sessionID = 0): bool {
        $result = self::idSchema()->replace([
            "CREDENTIAL_ID" => Auth::getID(),
            "SESSION_ID"    => $sessionID,
        ]);
        return $result > 0;
    }



    /**
     * Deletes the items older than 90 days
     * @param integer $days Optional.
     * @return boolean
     */
    public static function deleteOld(int $days = 90): bool {
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        self::sessionSchema()->remove($query);
        self::actionSchema()->remove($query);
        return true;
    }
}
