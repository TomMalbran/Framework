<?php
namespace Framework\Log;

use Framework\Framework;
use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Query;
use Framework\Utils\Utils;

/**
 * The Errors Log
 */
class ErrorLog {

    private static $loaded   = false;
    private static $schema   = null;
    private static $basePath = "";
    private static $maxLog   = 1000;


    /**
     * Initializes the Log
     * @return void
     */
    public static function init() {
        self::$basePath = Framework::getPath(Framework::SourceDir);
        set_error_handler("\\Framework\\Log\\ErrorLog::handler");
    }

    /**
     * Loads the Reset Schema
     * @return Schema
     */
    public static function getSchema() {
        if (!self::$loaded) {
            self::$loaded = false;
            self::$schema = Factory::getSchema("logErrors");
        }
        return self::$schema;
    }



    /**
     * Returns the Filter Query
     * @param Request $filters
     * @return Query
     */
    private static function getFilterQuery(Request $filters) {
        $query = new Query();
        $query->addIf("description", "LIKE", $filters->search);
        if ($filters->has("fromTime") && $filters->has("toTime")) {
            $query->betweenTimes("time", $filters->fromTime, $filters->toTime);
        }
        return $query;
    }

    /**
     * Returns all the Product Logs filtered by the given times
     * @param Request $filters
     * @param Request $sort
     * @return array
     */
    public static function filter(Request $filters, Request $sort) {
        $query = self::getFilterQuery($filters);
        $query->orderBy("updatedTime", false);
        $query->paginate($sort->page, $sort->amount);
        return self::getSchema()->getArray($query);
    }

    /**
     * Returns the Total Actions Log with the given Filters
     * @param Request $filters
     * @return integer
     */
    public static function getTotal(Request $filters) {
        $query = self::getFilterQuery($filters);
        return self::getSchema()->getTotal($query);
    }

    /**
     * Marks an Error as Resolved
     * @param integer $logID
     * @return boolean
     */
    public static function markResolved($logID) {
        $schema = self::getSchema();
        if ($schema->exists($logID)) {
            $schema->edit($logID, [
                "isResolved" => 1,
            ]);
            return true;
        }
        return false;
    }



    /**
     * Handes the PHP Error
     * @param integer $code
     * @param string  $description
     * @param string  $file
     * @param integer $line
     * @return boolean
     */
    public static function handler($code, $description, $file = "", $line = 0) {
        [ $error, $level ] = self::mapErrorCode($code);
        $schema      = self::getSchema();
        $fileName    = str_replace(self::$basePath . "/", "", $file);
        $description = str_replace([ "'", "`" ], "", $description);

        $query = Query::create("code", "=", $code);
        $query->add("description", "=", $description);
        $query->addIf("file", "=", $fileName);
        $query->addIf("line", "=", $line);
        
        if ($schema->getTotal($query) > 0) {
            $query->orderBy("updatedTime", false)->limit(1);
            $schema->edit($query, [
                "amount"      => Query::inc(1),
                "updatedTime" => time(),
            ]);
        } else {
            $schema->create([
                "code"        => $code,
                "level"       => $level,
                "error"       => $error,
                "description" => $description,
                "file"        => $fileName,
                "line"        => $line,
                "updatedTime" => time(),
            ]);

            $total = $schema->getTotal();
            if ($total > self::$maxLog) {
                $query = Query::createOrderBy("updatedTime", false);
                $query->limit($total - self::$maxLog);
                $schema->remove($query);
            }
        }
        return false;
    }
}
