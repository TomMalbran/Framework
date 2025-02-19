<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Auth\Auth;
use Framework\Database\Query;
use Framework\Log\SessionLog;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Schema\LogActionSchema;

/**
 * The Actions Log
 */
class ActionLog extends LogActionSchema {

    /**
     * Returns the List Query
     * @param Request              $request
     * @param array<string,string> $mappings Optional.
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
     * @param Request              $request
     * @param array<string,string> $mappings Optional.
     * @return array{}[]
     */
    public static function getAll(Request $request, array $mappings = []): array {
        $query = self::createQuery($request, $mappings);
        $query->orderBy("log_session.createdTime", false);
        $query->orderBy("log_session.SESSION_ID", false);
        $query->orderBy("log_action.createdTime", false);

        $list      = self::getEntityList($query, $request);
        $result    = [];
        $lastIndex = -1;

        foreach ($list as $elem) {
            if ($lastIndex < 0 || $result[$lastIndex]["sessionID"] != $elem->sessionID) {
                $result[] = [
                    "sessionID"      => $elem->sessionID,
                    "credentialID"   => $elem->credentialID,
                    "credentialName" => $elem->credentialName,
                    "ip"             => $elem->sessionIp,
                    "userAgent"      => $elem->sessionUserAgent,
                    "createdTime"    => $elem->sessionCreatedTime,
                    "actions"        => [],
                    "isLast"         => false,
                ];
                $lastIndex += 1;
            }
            $result[$lastIndex]["actions"][] = [
                "module"      => $elem->module,
                "action"      => $elem->action,
                "dataID"      => !empty($elem->dataID) ? JSON::decodeAsArray($elem->dataID) : "",
                "createdTime" => $elem->createdTime,
            ];
        }

        if ($lastIndex >= 0) {
            $result[$lastIndex]["isLast"] = true;
        }
        return $result;
    }

    /**
     * Returns the total amount of Actions Log items
     * @param Request              $request
     * @param array<string,string> $mappings Optional.
     * @return integer
     */
    public static function getAmount(Request $request, array $mappings = []): int {
        $query = self::createQuery($request, $mappings);
        return self::getEntityTotal($query);
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
     * Starts a Log Session
     * @param boolean $destroy Optional.
     * @return boolean
     */
    public static function startSession(bool $destroy = false): bool {
        $credentialID = self::getCredentialID();
        $sessionID    = SessionLog::getID($credentialID);

        if ($destroy && !empty($sessionID)) {
            SessionLog::end($sessionID);
        }
        if ($destroy || empty($sessionID)) {
            SessionLog::start($credentialID);
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
        $sessionID    = SessionLog::getID($credentialID);
        if (empty($sessionID)) {
            return false;
        }
        return SessionLog::end($sessionID);
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
        $sessionID    = SessionLog::getID($credentialID);
        if (empty($sessionID)) {
            return false;
        }

        $dataID = Arrays::toArray($dataID);
        foreach ($dataID as $index => $value) {
            $dataID[$index] = Numbers::isValid($value) ? (int)$value : $value;
        }

        self::createEntity(
            sessionID:    $sessionID,
            credentialID: $credentialID,
            userID:       Auth::getUserID(),
            module:       $module,
            action:       $action,
            dataID:       JSON::encode($dataID),
        );
        return true;
    }



    /**
     * Deletes the items older than some days
     * @return boolean
     */
    public static function deleteOld(): bool {
        $days  = Config::getActionLogDeleteDays();
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        self::removeEntity($query);
        return SessionLog::deleteOld();
    }
}
