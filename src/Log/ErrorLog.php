<?php
namespace Framework\Log;

use Framework\Request;
use Framework\Discovery\Discovery;
use Framework\Database\Assign;
use Framework\Database\Query;
use Framework\System\Config;
use Framework\Utils\Arrays;
use Framework\Utils\DateTime;
use Framework\Utils\Strings;
use Framework\Schema\LogErrorSchema;

/**
 * The Errors Log
 */
class ErrorLog extends LogErrorSchema {

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
        self::$framePath = Discovery::getBasePath(true);
        self::$basePath  = Discovery::getBasePath(false);

        register_shutdown_function("\\Framework\\Log\\ErrorLog::shutdown");
        set_error_handler("\\Framework\\Log\\ErrorLog::handler");
        return true;
    }



    /**
     * Creates the List Query
     * @param Request $request
     * @return Query
     */
    protected static function createListQuery(Request $request): Query {
        $search   = $request->getString("search");
        $fromTime = $request->toDayStart("fromDate");
        $toTime   = $request->toDayEnd("toDate");

        $query = Query::createSearch([ "description", "file" ], $search);
        $query->addIf("createdTime", ">", $fromTime);
        $query->addIf("createdTime", "<", $toTime);
        return $query;
    }

    /**
     * Marks the given Error(s) as Resolved
     * @param integer[]|integer $logID
     * @return boolean
     */
    public static function markResolved(array|int $logID): bool {
        $logIDs = Arrays::toInts($logID);
        $query  = Query::create("LOG_ID", "IN", $logIDs);
        return self::editEntity($query, isResolved: true);
    }

    /**
     * Deletes the given Error(s)
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
        $days  = Config::getErrorLogDeleteDays();
        $time  = DateTime::getLastXDays($days);
        $query = Query::create("createdTime", "<", $time);
        return self::removeEntity($query);
    }



    /**
     * Handles the PHP Shutdown
     * @return boolean
     */
    public static function shutdown(): bool {
        $error = error_get_last();
        if ($error === null) {
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
     * @param integer $errorCode
     * @param string  $description
     * @param string  $filePath    Optional.
     * @param integer $line        Optional.
     * @return boolean
     */
    public static function handler(int $errorCode, string $description, string $filePath = "", int $line = 0): bool {
        if (!self::hasPrimaryKey()) {
            return false;
        }

        [ $errorText, $errorLevel ] = self::parseErrorCode($errorCode);

        $filePath    = self::getFilePath($filePath);
        $description = self::getDescription($description);
        [ $description, $backtrace ] = self::getBacktrace($description);

        $query = Query::create("errorCode", "=", $errorCode);
        $query->addIf("file", "=", $filePath);
        $query->addIf("line", "=", $line);
        $query->add("description", "=", $description);
        $query->add("backtrace",   "=", $backtrace);

        if (self::getEntityTotal($query) > 0) {
            $query->orderBy("updatedTime", false)->limit(1);
            self::editEntity(
                $query,
                amount:      Assign::increase(1),
                isResolved:  false,
                updatedTime: time(),
            );
        } else {
            self::createEntity(
                errorCode:   $errorCode,
                errorText:   $errorText,
                errorLevel:  $errorLevel,
                environment: Discovery::getEnvironment(),
                file:        $filePath,
                line:        $line,
                description: $description,
                backtrace:   $backtrace,
                amount:      1,
                isResolved:  false,
                updatedTime: time(),
            );

            $total = self::getEntityTotal();
            if ($total > self::$maxLog) {
                $query = Query::createOrderBy("updatedTime", false);
                $query->limit($total - self::$maxLog);
                self::removeEntity($query);
            }
        }
        return true;
    }

    /**
     * Maps an Error Code into an Error word, and log location.
     * @param integer $errorCode
     * @return array{}
     */
    private static function parseErrorCode(int $errorCode): array {
        $errorText  = "";
        $errorLevel = 0;

        switch ($errorCode) {
        case E_PARSE:
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            $errorText  = "Fatal Error";
            $errorLevel = LOG_ERR;
            break;
        case E_WARNING:
        case E_USER_WARNING:
        case E_COMPILE_WARNING:
        case E_RECOVERABLE_ERROR:
            $errorText  = "Warning";
            $errorLevel = LOG_WARNING;
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $errorText  = "Notice";
            $errorLevel = LOG_NOTICE;
            break;
        case E_STRICT:
            $errorText  = "Strict";
            $errorLevel = LOG_NOTICE;
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $errorText  = "Deprecated";
            $errorLevel = LOG_NOTICE;
            break;
        default:
            break;
        }
        return [ $errorText, $errorLevel ];
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
