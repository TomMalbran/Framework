<?php
namespace Framework\Log;

use Framework\Framework;
use Framework\Request;
use Framework\Auth\Auth;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;

/**
 * The Query Log
 */
class QueryLog {

    /**
     * Loads the Query Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("logQueries");
    }



    /**
     * Returns an Query Log item with the given ID
     * @param integer $logID
     * @return Model
     */
    public static function getOne(int $logID): Model {
        return self::schema()->getOne($logID);
    }

    /**
     * Returns true if the given Query Log item exists
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
    protected static function createQuery(Request $request): Query {
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([ "expression" ], $request->search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }

    /**
     * Returns all the Query Log items
     * @param Request $request
     * @return array{}[]
     */
    public static function getAll(Request $request): array {
        $query = self::createQuery($request);
        return self::schema()->getAll($query, $request);
    }

    /**
     * Returns the total amount of Query Log items
     * @param Request $request
     * @return integer
     */
    public static function getTotal(Request $request): int {
        $query = self::createQuery($request);
        return self::schema()->getTotal($query);
    }



    /**
     * Creates or edits a Query
     * @param integer $time
     * @param string  $expression
     * @param array{} $params
     * @return boolean
     */
    public static function createOrEdit(int $time, string $expression, array $params): bool {
        if (!self::schema()->tableExists()) {
            return false;
        }

        $bindKeys = [];
        foreach ($params as $key) {
            $bindKeys[] = '/[?]/';
        }
        $expression  = preg_replace("/ +/", " ", $expression);
        $expression  = preg_replace($bindKeys, $params, $expression, 1);
        $elapsedTime = (int)floor($time);

        $query = Query::create("expression", "=", $expression);
        if (self::schema()->getTotal($query) > 0) {
            $query->orderBy("updatedTime", false)->limit(1);
            self::schema()->edit($query, [
                "amount"      => Query::inc(1),
                "elapsedTime" => Query::greatest("elapsedTime", $elapsedTime),
                "totalTime"   => Query::inc($elapsedTime),
                "updatedTime" => time(),
                "updatedUser" => Auth::getID(),
            ]);
        } else {
            self::schema()->create([
                "expression"  => $expression,
                "environment" => Framework::getEnvironment(),
                "elapsedTime" => $elapsedTime,
                "totalTime"   => $elapsedTime,
                "amount"      => 1,
                "isResolved"  => 0,
                "updatedTime" => time(),
                "createdTime" => time(),
                "createdUser" => Auth::getID(),
                "updatedUser" => Auth::getID(),
            ]);
        }
        return true;
    }

    /**
     * Marks the given Query(s) as Resolved
     * @param integer[]|integer $logID
     * @return boolean
     */
    public static function markResolved(array|int $logID): bool {
        $logIDs = Arrays::toArray($logID);
        $query  = Query::create("LOG_ID", "IN", $logIDs);
        self::schema()->edit($query, [
            "isResolved" => 1,
        ]);
        return true;
    }

    /**
     * Deletes the given Query(s)
     * @param integer[]|integer $logID
     * @return boolean
     */
    public static function delete(array|int $logID): bool {
        $logIDs = Arrays::toArray($logID);
        $query  = Query::create("LOG_ID", "IN", $logIDs);
        return self::schema()->remove($query);
    }

    /**
     * Deletes the items older than 15 days
     * @param integer $days Optional.
     * @return boolean
     */
    public static function deleteOld(int $days = 90): bool {
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::schema()->remove($query);
    }
}
