<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Schema\Model;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;
use Framework\Utils\Server;

/**
 * The Provider Log
 */
class ProviderLog {

    /**
     * Loads the Provider Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("logProviders");
    }



    /**
     * Returns an Provider Log item with the given ID
     * @param integer $logID
     * @return Model
     */
    public static function getOne(int $logID): Model {
        return self::schema()->getOne($logID);
    }

    /**
     * Returns true if the given Provider Log item exists
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

        $query = Query::createSearch([ "provider", "action", "request", "response" ], $request->search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }

    /**
     * Returns all the Provider Log items
     * @param Request $request
     * @return array{}[]
     */
    public static function getAll(Request $request): array {
        $query = self::createQuery($request);
        return self::schema()->getAll($query, $request);
    }

    /**
     * Returns the total amount of Error Log items
     * @param Request $request
     * @return integer
     */
    public static function getTotal(Request $request): int {
        $query = self::createQuery($request);
        return self::schema()->getTotal($query);
    }



    /**
     * Adds a Hook
     * @param string  $provider
     * @param string  $action
     * @param object  $data
     * @param boolean $isError  Optional.
     * @return integer
     */
    public static function addHook(string $provider, string $action, object $data, bool $isError = false): int {
        return self::schema()->create([
            "provider" => $provider,
            "action"   => $action,
            "url"      => Server::getFullUrl(),
            "request"  => JSON::encode($data),
            "response" => "{}",
            "isError"  => $isError ? 1 : 0,
        ]);
    }

    /**
     * Adds a Request
     * @param string  $provider
     * @param string  $action
     * @param string  $url
     * @param array{} $request
     * @param array{} $response Optional.
     * @param boolean $isError  Optional.
     * @return integer
     */
    public static function addRequest(string $provider, string $action, string $url, array $request, array $response = [], bool $isError = false): int {
        return self::schema()->create([
            "provider" => $provider,
            "action"   => $action,
            "url"      => $url,
            "request"  => JSON::encode($request),
            "response" => JSON::encode($response),
            "isError"  => $isError ? 1 : 0,
        ]);
    }

    /**
     * Adds a Response to a Log entry
     * @param integer $logID
     * @param mixed   $response
     * @return boolean
     */
    public static function setResponse(int $logID, mixed $response): bool {
        return self::schema()->edit($logID, [
            "response" => JSON::encode($response),
        ]);
    }

    /**
     * Deletes the items older than 15 days
     * @param integer $days Optional.
     * @return boolean
     */
    public static function deleteOld(int $days = 15): bool {
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::schema()->remove($query);
    }
}
