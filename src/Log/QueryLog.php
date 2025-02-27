<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Discovery\Discovery;
use Framework\Auth\Auth;
use Framework\Database\Assign;
use Framework\Database\Query;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\Strings;
use Framework\Schema\LogQuerySchema;

/**
 * The Query Log
 */
class QueryLog extends LogQuerySchema {

    /**
     * Creates the List Query
     * @param Request $request
     * @return Query
     */
    protected static function createListQuery(Request $request): Query {
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([ "expression" ], $request->search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }



    /**
     * Creates or edits a Query
     * @param float   $time
     * @param string  $expression
     * @param mixed[] $params
     * @return boolean
     */
    public static function createOrEdit(float $time, string $expression, array $params): bool {
        $elapsedTime = (int)floor($time);
        $expression  = Strings::replacePattern($expression, "/ +/", " ");
        foreach ($params as $param) {
            $value      = Strings::isString($param) ? "'$param'" : $param;
            $expression = Strings::replacePattern($expression, "/[?]/", $value, 1);
        }

        $query = Query::create("expression", "=", $expression);
        if (self::getEntityTotal($query) > 0) {
            $query->orderBy("updatedTime", false)->limit(1);
            self::editEntity(
                $query,
                amount:      Assign::increase(1),
                elapsedTime: Assign::greatest($elapsedTime),
                totalTime:   Assign::increase($elapsedTime),
                isResolved:  false,
                updatedTime: time(),
                updatedUser: Auth::getID(),
            );
        } else {
            self::createEntity(
                expression:  $expression,
                environment: Discovery::getEnvironment(),
                elapsedTime: $elapsedTime,
                totalTime:   $elapsedTime,
                amount:      1,
                isResolved:  false,
                updatedTime: time(),
                updatedUser: Auth::getID(),
            );
        }
        return true;
    }

    /**
     * Marks the given Query(s) as Resolved
     * @param integer[]|integer $logID
     * @return boolean
     */
    public static function markResolved(array|int $logID): bool {
        $logIDs = Arrays::toInts($logID);
        $query  = Query::create("LOG_ID", "IN", $logIDs);
        return self::editEntity($query, isResolved: true);
    }

    /**
     * Deletes the given Query(s)
     * @param integer[]|integer $logID
     * @return boolean
     */
    public static function delete(array|int $logID): bool {
        $logIDs = Arrays::toInts($logID);
        $query  = Query::create("LOG_ID", "IN", $logIDs);
        return self::removeEntity($query);
    }

    /**
     * Deletes the items older than some days
     * @return boolean
     */
    public static function deleteOld(): bool {
        $days  = Config::getQueryLogDeleteDays();
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::removeEntity($query);
    }
}
