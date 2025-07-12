<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Discovery\Discovery;
use Framework\Auth\Auth;
use Framework\Database\Assign;
use Framework\System\Config;
use Framework\Log\Schema\LogQuerySchema;
use Framework\Log\Schema\LogQueryColumn;
use Framework\Log\Schema\LogQueryQuery;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\Strings;

/**
 * The Query Log
 */
class QueryLog extends LogQuerySchema {

    /**
     * Creates the List Query
     * @param Request $request
     * @return LogQueryQuery
     */
    protected static function createListQuery(Request $request): LogQueryQuery {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = new LogQueryQuery();
        $query->search([
            LogQueryColumn::Expression,
        ], $search);

        $query->createdTime->greaterThan($fromTime, $fromTime > 0);
        $query->createdTime->lessThan($toTime, $toTime > 0);
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
            $value      = is_string($param) ? "'$param'" : Strings::toString($param);
            $expression = Strings::replacePattern($expression, "/[?]/", $value, 1);
        }

        $query = new LogQueryQuery();
        $query->expression->equal($expression);

        if (self::getEntityTotal($query) > 0) {
            $query->updatedTime->orderByDesc()->limit(1);
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
        $query = new LogQueryQuery();
        $query->logID->in(Arrays::toInts($logID));
        return self::editEntity($query, isResolved: true);
    }

    /**
     * Deletes the given Query(s)
     * @param integer[]|integer $logID
     * @return boolean
     */
    public static function delete(array|int $logID): bool {
        $query = new LogQueryQuery();
        $query->logID->in(Arrays::toInts($logID));
        return self::removeEntity($query);
    }

    /**
     * Deletes the items older than some days
     * @return boolean
     */
    public static function deleteOld(): bool {
        $days  = Config::getQueryLogDeleteDays();
        $time  = DateTime::getLastXDays($days);

        $query = new LogQueryQuery();
        $query->createdTime->lessThan($time);
        return self::removeEntity($query);
    }
}
