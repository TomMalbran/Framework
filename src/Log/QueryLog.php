<?php
namespace Framework\Log;

use Framework\Application;
use Framework\IO\Request;
use Framework\Database\Query\Assign;
use Framework\Auth\Auth;
use Framework\System\Config;
use Framework\Log\Schema\LogQuerySchema;
use Framework\Log\Schema\LogQueryColumn;
use Framework\Log\Schema\LogQueryQuery;
use Framework\Date\Date;
use Framework\Utils\Arrays;
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
    #[\Override]
    protected static function createListQuery(Request $request): LogQueryQuery {
        $search     = $request->getString("search");
        $fromTime   = $request->toDayStartHour("fromDate", "fromHour");
        $toTime     = $request->toDayEndHour("toDate", "toHour");
        $isResolved = $request->getString("isResolved");

        $query = new LogQueryQuery();
        $query->search([
            LogQueryColumn::Expression,
        ], $search);

        $query->createdTime->greaterThan($fromTime);
        $query->createdTime->lessThan($toTime);

        if ($isResolved === "yes") {
            $query->isResolved->isTrue();
        } elseif ($isResolved === "no") {
            $query->isResolved->isFalse();
        }
        return $query;
    }



    /**
     * Creates or edits a Query
     * @param float       $time
     * @param string      $expression
     * @param list<mixed> $params
     * @return void
     */
    public static function createOrEdit(float $time, string $expression, array $params): void {
        $elapsedTime = (int)floor($time);
        $expression  = Strings::replacePattern($expression, "/ +/", " ");
        foreach ($params as $param) {
            $value      = is_string($param) ? "'$param'" : Strings::toString($param);
            $expression = Strings::replacePattern($expression, "/[?]/", $value, 1);
        }

        $query = new LogQueryQuery();
        $query->expression->equal($expression);

        if (self::getEntityTotal($query) > 0) {
            $query->updatedTime->orderByDesc();
            $query->limit(1);

            self::editEntity(
                $query,
                amount:      Assign::increase(1),
                elapsedTime: Assign::greatest($elapsedTime),
                totalTime:   Assign::increase($elapsedTime),
                isResolved:  false,
                updatedTime: Date::now(),
                updatedUser: Auth::getID(),
            );
        } else {
            self::createEntity(
                expression:  $expression,
                environment: Application::getEnvironment(),
                elapsedTime: $elapsedTime,
                totalTime:   $elapsedTime,
                amount:      1,
                isResolved:  false,
                updatedTime: Date::now(),
                updatedUser: Auth::getID(),
            );
        }
    }

    /**
     * Marks the given Query(s) as Resolved
     * @param list<int>|int $logID
     * @return void
     */
    public static function markResolved(array|int $logID): void {
        $query = new LogQueryQuery();
        $query->logID->in(Arrays::toInts($logID));
        self::editEntity($query, isResolved: true);
    }

    /**
     * Deletes the given Query(s)
     * @param list<int>|int $logID
     * @return void
     */
    public static function delete(array|int $logID): void {
        $query = new LogQueryQuery();
        $query->logID->in(Arrays::toInts($logID));
        self::removeEntity($query);
    }

    /**
     * Deletes the items older than some days
     * @return void
     */
    public static function deleteOld(): void {
        $days  = Config::getQueryLogDeleteDays();
        $time  = Date::now()->subtract(days: $days);

        $query = new LogQueryQuery();
        $query->createdTime->lessThan($time);
        self::removeEntity($query);
    }
}
