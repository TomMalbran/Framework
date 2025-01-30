<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Auth\Auth;
use Framework\Database\Factory;
use Framework\Database\Schema;
use Framework\Database\Query;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
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
        return Factory::getSchema("LogIdentifier");
    }

    /**
     * Loads the Sessions Schemas
     * @return Schema
     */
    public static function sessionSchema(): Schema {
        return Factory::getSchema("LogSession");
    }

    /**
     * Loads the Actions Schemas
     * @return Schema
     */
    public static function actionSchema(): Schema {
        return Factory::getSchema("LogAction");
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
        $query->orderBy("log_session.createdTime", false);
        $query->orderBy("log_session.SESSION_ID", false);
        $query->orderBy("log_action.createdTime", false);
        $query->paginate($request->getInt("page"), $request->getInt("amount"));

        $request   = self::actionSchema()->getAll($query);
        $result    = [];
        $lastIndex = -1;

        foreach ($request as $row) {
            if ($lastIndex < 0 || $result[$lastIndex]["sessionID"] != $row["sessionID"]) {
                $result[] = [
                    "sessionID"      => $row["sessionID"],
                    "credentialID"   => $row["credentialID"],
                    "credentialName" => $row["credentialName"],
                    "ip"             => $row["sessionIp"],
                    "userAgent"      => $row["sessionUserAgent"],
                    "createdTime"    => $row["sessionCreatedTime"],
                    "actions"        => [],
                    "isLast"         => false,
                ];
                $lastIndex += 1;
            }
            $result[$lastIndex]["actions"][] = [
                "module"      => $row["module"],
                "action"      => $row["action"],
                "dataID"      => !empty($row["dataID"]) ? JSON::decode($row["dataID"]) : "",
                "createdTime" => $row["createdTime"],
            ];
        }

        if ($lastIndex >= 0) {
            $result[$lastIndex]["isLast"] = true;
        }
        return $result;
    }

    /**
     * Returns the total amount of Actions Log items
     * @param Request $request
     * @param array{} $mappings Optional.
     * @return integer
     */
    public static function getTotal(Request $request, array $mappings = []): int {
        $query = self::createQuery($request, $mappings);
        return self::actionSchema()->getTotal($query);
    }



    /**
     * Returns the Credential ID for the current User
     * @param integer $credentialID Optional.
     * @return integer
     */
    private static function getCredentialID(int $credentialID = 0): int {
        if (!empty($credentialID)) {
            return $credentialID;
        }
        if (Auth::isLoggedAsUser()) {
            return Auth::getAdminID();
        }
        return Auth::getID();
    }

    /**
     * Returns the Session ID for the current Credential
     * @param integer $credentialID
     * @return integer
     */
    private static function getSessionID(int $credentialID): int {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        return (int)self::idSchema()->getValue($query, "SESSION_ID");
    }

    /**
     * Sets the given Session ID for the current Credential
     * @param integer $credentialID
     * @param integer $sessionID
     * @return boolean
     */
    private static function setSessionID(int $credentialID, int $sessionID): bool {
        $result = self::idSchema()->replace([
            "CREDENTIAL_ID" => $credentialID,
            "SESSION_ID"    => $sessionID,
        ]);
        return $result > 0;
    }



    /**
     * Starts a Log Session
     * @param boolean $destroy Optional.
     * @return boolean
     */
    public static function startSession(bool $destroy = false): bool {
        $credentialID = self::getCredentialID();
        $sessionID    = self::getSessionID($credentialID);

        if ($destroy || empty($sessionID)) {
            $sessionID = self::sessionSchema()->create([
                "CREDENTIAL_ID" => $credentialID,
                "USER_ID"       => Auth::getUserID(),
                "ip"            => Server::getIP(),
                "userAgent"     => Server::getUserAgent(),
            ]);
            self::setSessionID($credentialID, $sessionID);
            return true;
        }
        return false;
    }

    /**
     * Ends the Log Session
     * @return boolean
     */
    public static function endSession(): bool {
        $credentialID = self::getCredentialID();
        return self::setSessionID($credentialID, 0);
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
        $credentialID = self::getCredentialID($credentialID);
        $sessionID    = self::getSessionID($credentialID);
        if (empty($sessionID)) {
            return false;
        }

        $dataID = Arrays::toArray($dataID);
        foreach ($dataID as $index => $value) {
            $dataID[$index] = Numbers::isValid($value) ? (int)$value : $value;
        }

        self::actionSchema()->create([
            "SESSION_ID"    => $sessionID,
            "CREDENTIAL_ID" => $credentialID,
            "USER_ID"       => Auth::getUserID(),
            "module"        => $module,
            "action"        => $action,
            "dataID"        => JSON::encode($dataID),
        ]);
        return true;
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
