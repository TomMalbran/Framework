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

    private static $loaded    = false;
    private static $schema    = null;
    private static $framePath = "";
    private static $basePath  = "";
    private static $maxLog    = 1000;


    /**
     * Initializes the Log
     * @return void
     */
    public static function init() {
        self::$framePath = Framework::getPath("src", "", true);
        self::$basePath  = Framework::getPath(Framework::SourceDir);
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
     * Returns an Error with the given Code
     * @param integer $logID
     * @return Model
     */
    public static function get($logID) {
        return self::getSchema()->getByID($logID);
    }
    
    /**
     * Returns true if the given Error exists
     * @param integer $logID
     * @return boolean
     */
    public static function exists($logID) {
        return self::getSchema()->exists($logID);
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
        $description = str_replace([ "'", "`" ], "", $description);

        if (strstr($file, self::$framePath) !== FALSE) {
            $fileName = "Framework/" . str_replace(self::$framePath . "/", "", $file);
        } else {
            $fileName = str_replace(self::$basePath . "/", "", $file);
        }

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
                "amount"      => 1,
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

    /**
     * Map an error code into an Error word, and log location.
     * @param integer $code
     * @return array
     */
    public static function mapErrorCode($code) {
        $error = "";
        $level = 0;

        switch ($code) {
            case E_PARSE:
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $error = "Fatal Error";
                $level = LOG_ERR;
                break;
            case E_WARNING:
            case E_USER_WARNING:
            case E_COMPILE_WARNING:
            case E_RECOVERABLE_ERROR:
                $error = "Warning";
                $level = LOG_WARNING;
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $error = "Notice";
                $log   = LOG_NOTICE;
                break;
            case E_STRICT:
                $error = "Strict";
                $level = LOG_NOTICE;
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $error = "Deprecated";
                $level = LOG_NOTICE;
                break;
            default:
                break;
        }
        return [ $error, $level ];
    }
}
