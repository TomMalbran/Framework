<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Auth\Auth;
use Framework\Log\SessionLog;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Schema\LogActionSchema;
use Framework\Schema\LogActionQuery;

/**
 * The Actions Log
 */
class ActionLog extends LogActionSchema {

    /**
     * Returns the List Query
     * @param Request              $request
     * @param array<string,string> $mappings Optional.
     * @return LogActionQuery
     */
    private static function createQuery(Request $request, array $mappings = []): LogActionQuery {
        $credentialID = $request->getInt("credentialID");
        $fromTime     = $request->toDayStart("fromDate");
        $toTime       = $request->toDayEnd("toDate");

        $query = new LogActionQuery();
        foreach ($mappings as $key => $value) {
            $query->query->addIf($value, "=", $request->getString($key));
        }

        $query->credentialID->equalIf($credentialID);
        $query->createdTime->greaterThan($fromTime, $fromTime > 0);
        $query->createdTime->lessThan($toTime, $toTime > 0);
        return $query;
    }

    /**
     * Returns all the Actions Log items
     * @param Request              $request
     * @param array<string,string> $mappings Optional.
     * @return array<string,mixed>[]
     */
    public static function getAll(Request $request, array $mappings = []): array {
        $query = self::createQuery($request, $mappings);
        $query->sessionCreatedTime->orderByDesc();
        $query->sessionID->orderByDesc();
        $query->createdTime->orderByDesc();

        $list      = self::getEntityList($query, $request);
        $result    = [];
        $lastIndex = -1;

        foreach ($list as $elem) {
            if ($lastIndex < 0 || $result[$lastIndex]["sessionID"] !== $elem->sessionID) {
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
            $dataID[$index] = Numbers::isValid($value) ? Numbers::toInt($value) : $value;
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

        $query = new LogActionQuery();
        $query->createdTime->lessThan($time);
        self::removeEntity($query);
        return SessionLog::deleteOld();
    }
}
