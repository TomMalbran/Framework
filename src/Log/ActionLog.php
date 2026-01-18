<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Auth\Auth;
use Framework\Database\Query\QueryOperator;
use Framework\Log\SessionLog;
use Framework\Log\Schema\LogActionSchema;
use Framework\Log\Schema\LogActionColumn;
use Framework\Log\Schema\LogActionQuery;
use Framework\System\Config;
use Framework\Date\DateTime;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;

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
        $search       = $request->getString("search");

        $query = new LogActionQuery();
        foreach ($mappings as $key => $value) {
            $query->query->addIf($value, QueryOperator::Equal, $request->getString($key));
        }
        $query->search([
            LogActionColumn::CredentialName,
            LogActionColumn::Module,
            LogActionColumn::Action,
        ], $search);

        $query->credentialID->equalIf($credentialID);
        $query->createdTime->inPeriod($request);
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
            if ($lastIndex < 0 || (isset($result[$lastIndex]) && isset($result[$lastIndex]["sessionID"]) && $result[$lastIndex]["sessionID"] !== $elem->sessionID)) {
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
                "dataID"      => $elem->dataID !== "" ? JSON::decodeAsArray($elem->dataID) : "",
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
     * @return int
     */
    public static function getAmount(Request $request, array $mappings = []): int {
        $query = self::createQuery($request, $mappings);
        return self::getEntityTotal($query);
    }



    /**
     * Returns the Credential ID for the current User
     * @param int $credentialID Optional.
     * @return int
     */
    private static function getCredentialID(int $credentialID = 0): int {
        if ($credentialID !== 0) {
            return $credentialID;
        }
        if (Auth::isLoggedAsUser()) {
            return Auth::getAdminID();
        }
        return Auth::getID();
    }

    /**
     * Starts a Log Session
     * @param bool $destroy Optional.
     * @return bool
     */
    public static function startSession(bool $destroy = false): bool {
        $credentialID = self::getCredentialID();
        $sessionID    = SessionLog::getID($credentialID);

        if ($destroy && $sessionID !== 0) {
            SessionLog::end($sessionID);
        }
        if ($destroy || $sessionID === 0) {
            SessionLog::start($credentialID);
            return true;
        }
        return false;
    }

    /**
     * Ends the Log Session
     * @return bool
     */
    public static function endSession(): bool {
        $credentialID = self::getCredentialID();
        $sessionID    = SessionLog::getID($credentialID);
        if ($sessionID === 0) {
            return false;
        }
        return SessionLog::end($sessionID);
    }

    /**
     * Logs the given Action
     * @param string    $module
     * @param string    $action
     * @param mixed|int $dataID       Optional.
     * @param int       $credentialID Optional.
     * @return bool
     */
    public static function add(string $module, string $action, mixed $dataID = 0, int $credentialID = 0): bool {
        $credentialID = self::getCredentialID($credentialID);
        $sessionID    = SessionLog::getID($credentialID);
        if ($sessionID === 0) {
            return false;
        }

        $dataID = Arrays::toArray($dataID);
        foreach ($dataID as $index => $value) {
            $dataID[$index] = Numbers::isValid($value) ? Numbers::toInt($value) : $value;
        }

        self::createEntity(
            sessionID:    $sessionID,
            credentialID: $credentialID,
            currentUser:  Auth::getUserID(),
            module:       $module,
            action:       $action,
            dataID:       JSON::encode($dataID),
        );
        return true;
    }



    /**
     * Deletes the items older than some days
     * @return bool
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
