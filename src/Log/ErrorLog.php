<?php
namespace Framework\Log;

use Framework\Framework;
use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Schema\Model;
use Framework\Utils\DateTime;
use Framework\Utils\Strings;

/**
 * The Errors Log
 */
class ErrorLog {

    private static bool   $loaded    = false;
    private static string $framePath = "";
    private static string $basePath  = "";
    private static int    $maxLog    = 1000;


    /**
     * Initializes the Log
     * @return boolean
     */
    public static function init(): bool {
        if (self::$loaded) {
            return false;
        }

        self::$loaded    = true;
        self::$framePath = Framework::getBasePath(true);
        self::$basePath  = Framework::getBasePath(false);

        register_shutdown_function("\\Framework\\Log\\ErrorLog::shutdown");
        set_error_handler("\\Framework\\Log\\ErrorLog::handler");
        return true;
    }

    /**
     * Loads the Error Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("logErrors");
    }



    /**
     * Returns an Error with the given ID
     * @param integer $logID
     * @return Model
     */
    public static function getOne(int $logID): Model {
        return self::schema()->getOne($logID);
    }

    /**
     * Returns true if the given Error exists
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
    private static function createQuery(Request $request): Query {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([ "description", "file" ], $search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }

    /**
     * Returns all the Error Log items
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
     * Marks an Error as Resolved
     * @param integer $logID
     * @return boolean
     */
    public static function markResolved(int $logID): bool {
        $schema = self::schema();
        if ($schema->exists($logID)) {
            $schema->edit($logID, [
                "isResolved" => 1,
            ]);
            return true;
        }
        return false;
    }

    /**
     * Deletes the items older than 90 days
     * @param integer $days Optional.
     * @return boolean
     */
    public static function deleteOld(int $days = 90): bool {
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::schema()->remove($query);
    }



    /**
     * Handles the PHP Shutdown
     * @return boolean
     */
    public static function shutdown(): bool {
        $error = error_get_last();
        if (!is_null($error)) {
            return self::handler($error["type"], $error["message"], $error["file"], $error["line"]);
        }
        return false;
    }

    /**
     * Handles the PHP Error
     * @param integer $code
     * @param string  $description
     * @param string  $file
     * @param integer $line
     * @return boolean
     */
    public static function handler(int $code, string $description, string $file = "", int $line = 0): bool {
        [ $error, $level ] = self::mapErrorCode($code);
        $schema      = self::schema();
        $description = Strings::replace($description, [ "'", "`" ], "");

        if (Strings::contains($file, self::$framePath)) {
            $fileName = Strings::replace($file, self::$framePath . "/", "Framework/");
        } else {
            $fileName = Strings::replace($file, self::$basePath . "/", "");
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
                "isResolved"  => 0,
                "updatedTime" => time(),
            ]);

            $total = $schema->getTotal();
            if ($total > self::$maxLog) {
                $query = Query::createOrderBy("updatedTime", false);
                $query->limit($total - self::$maxLog);
                $schema->remove($query);
            }
        }
        return true;
    }

    /**
     * Map an error code into an Error word, and log location.
     * @param integer $code
     * @return array{}
     */
    public static function mapErrorCode(int $code): array {
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
