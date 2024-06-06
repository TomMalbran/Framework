<?php
namespace Framework\Log;

use Framework\Framework;
use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Query;
use Framework\Schema\Model;
use Framework\Utils\Arrays;
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
     * Returns an Error Log item with the given ID
     * @param integer $logID
     * @return Model
     */
    public static function getOne(int $logID): Model {
        return self::schema()->getOne($logID);
    }

    /**
     * Returns true if the given Error Log item exists
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
     * Marks the given Error(s) as Resolved
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
     * Deletes the given Error(s)
     * @param integer[]|integer $logID
     * @return boolean
     */
    public static function delete(array|int $logID): bool {
        $logIDs = Arrays::toArray($logID);
        $query  = Query::create("LOG_ID", "IN", $logIDs);
        return self::schema()->remove($query);
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
        if (is_null($error)) {
            return false;
        }
        return self::handler(
            $error["type"],
            $error["message"],
            $error["file"],
            $error["line"],
        );
    }

    /**
     * Handles the PHP Error
     * @param integer $code
     * @param string  $description
     * @param string  $filePath    Optional.
     * @param integer $line        Optional.
     * @return boolean
     */
    public static function handler(int $code, string $description, string $filePath = "", int $line = 0): bool {
        [ $error, $level ] = self::getErrorCode($code);

        $environment = self::getEnvironment();
        $filePath    = self::getFilePath($filePath);
        $description = self::getDescription($description);
        [ $description, $backtrace ] = self::getBacktrace($description);

        $query = Query::create("code", "=", $code);
        $query->addIf("file", "=", $filePath);
        $query->addIf("line", "=", $line);
        $query->add("description", "=", $description);
        $query->add("backtrace",   "=", $backtrace);

        $schema = self::schema();
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
                "environment" => $environment,
                "file"        => $filePath,
                "line"        => $line,
                "description" => $description,
                "backtrace"   => $backtrace,
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
     * Maps an error code into an Error word, and log location.
     * @param integer $code
     * @return array{}
     */
    private static function getErrorCode(int $code): array {
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
            $level = LOG_NOTICE;
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

    /**
     * Returns the Environment
     * @return string
     */
    private static function getEnvironment(): string {
        if (Strings::contains(self::$basePath, "public_html")) {
            $environment = Strings::substringAfter(self::$basePath, "domains/");
            $environment = Strings::substringBefore($environment, "/public_html");
            return $environment;
        }
        return "localhost";
    }

    /**
     * Returns the File Path
     * @param string $filePath
     * @return string
     */
    private static function getFilePath(string $filePath): string {
        if (Strings::contains($filePath, self::$framePath)) {
            return Strings::replace($filePath, self::$framePath . "/", "framework/");
        }
        return Strings::replace($filePath, self::$basePath . "/", "");
    }

    /**
     * Parses and returns the Description
     * @param string $description
     * @return string
     */
    private static function getDescription(string $description): string {
        $description = Strings::replace($description, [ "'", "`" ], "");
        $description = Strings::replace($description, self::$framePath . "/", "Framework/");
        $description = Strings::replace($description, self::$basePath . "/", "");
        return $description;
    }

    /**
     * Parses and returns the Backtrace
     * @param string $description
     * @return string[]
     */
    private static function getBacktrace(string $description): array {
        if (Strings::contains($description, "Stack trace")) {
            [ $description, $stacktrace ] = Strings::split($description, "Stack trace:\n");

            $trace     = Strings::split($stacktrace, "\n");
            $trace     = Arrays::reverse($trace);
            $backtrace = "";
            $index     = 1;
            foreach ($trace as $item) {
                if (Strings::startsWith($item, "#") && !Strings::contains($item, "{main}")) {
                    $backtrace .= "#{$index}- " . Strings::substringAfter($item, " ", true) . "\n";
                    $index     += 1;
                }
            }
            return [ $description, $backtrace ];
        }

        $trace     = debug_backtrace();
        $backtrace = "";
        $index     = 1;
        for ($i = count($trace) - 1; $i >= 2; $i--) {
            $item       = $trace[$i];
            $backtrace .= "#{$index}- ";
            $backtrace .= (!empty($item["file"]) ? self::getFilePath($item["file"]) : "<unknown file>") . " ";
            $backtrace .= "(". ($item["line"] ?? "<unknown line>") . ") ";
            $backtrace .= " -> {$item["function"]}()" . "\n";
            $index     += 1;
        }
        return [ $description, $backtrace ];
    }
}
