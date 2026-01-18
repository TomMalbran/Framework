<?php
namespace Framework\Log;

use Framework\Application;
use Framework\Request;
use Framework\Discovery\Package;
use Framework\Database\Type\Assign;
use Framework\Log\Schema\LogErrorSchema;
use Framework\Log\Schema\LogErrorColumn;
use Framework\Log\Schema\LogErrorQuery;
use Framework\System\Config;
use Framework\Date\DateTime;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

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
     * @return bool
     */
    public static function init(): bool {
        if (self::$loaded) {
            return false;
        }

        self::$loaded    = true;
        self::$framePath = Package::getBasePath();
        self::$basePath  = Application::getIndexPath();

        register_shutdown_function("\\Framework\\Log\\ErrorLog::shutdown");
        set_error_handler("\\Framework\\Log\\ErrorLog::handler");
        return true;
    }



    /**
     * Creates the List Query
     * @param Request $request
     * @return LogErrorQuery
     */
    #[\Override]
    protected static function createListQuery(Request $request): LogErrorQuery {
        $search     = $request->getString("search");
        $fromTime   = $request->toDayStartHour("fromDate", "fromHour");
        $toTime     = $request->toDayEndHour("toDate", "toHour");
        $isResolved = $request->getString("isResolved");

        $query = new LogErrorQuery();
        $query->search([
            LogErrorColumn::Description,
            LogErrorColumn::File,
        ], $search);

        $query->createdTime->greaterThan($fromTime, $fromTime > 0);
        $query->createdTime->lessThan($toTime, $toTime > 0);

        if ($isResolved === "yes") {
            $query->isResolved->isTrue();
        } elseif ($isResolved === "no") {
            $query->isResolved->isFalse();
        }
        return $query;
    }

    /**
     * Marks the given Error(s) as Resolved
     * @param int[]|int $logID
     * @return bool
     */
    public static function markResolved(array|int $logID): bool {
        $query = new LogErrorQuery();
        $query->logID->in(Arrays::toInts($logID));
        return self::editEntity($query, isResolved: true);
    }

    /**
     * Deletes the given Error(s)
     * @param int[]|int $logID
     * @return bool
     */
    public static function delete(array|int $logID): bool {
        $query = new LogErrorQuery();
        $query->logID->in(Arrays::toInts($logID));
        return self::removeEntity($query);
    }

    /**
     * Deletes the items older than some days
     * @return bool
     */
    public static function deleteOld(): bool {
        $days  = Config::getErrorLogDeleteDays();
        $time  = DateTime::getLastXDays($days);

        $query = new LogErrorQuery();
        $query->createdTime->lessThan($time);
        return self::removeEntity($query);
    }



    /**
     * Handles the PHP Shutdown
     * @return bool
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
     * @param int    $errorCode
     * @param string $description
     * @param string $filePath    Optional.
     * @param int    $line        Optional.
     * @return bool
     */
    public static function handler(int $errorCode, string $description, string $filePath = "", int $line = 0): bool {
        if (!self::hasPrimaryKey()) {
            return false;
        }

        [ $errorText, $errorLevel ] = self::parseErrorCode($errorCode);

        $filePath    = self::getFilePath($filePath);
        $description = self::getDescription($description);
        [ $description, $backtrace ] = self::getBacktrace($description);

        $query = new LogErrorQuery();
        $query->errorCode->equal($errorCode);
        $query->file->equalIf($filePath);
        $query->line->equalIf($line);
        $query->description->equal($description);
        $query->backtrace->equal($backtrace);

        if (self::getEntityTotal($query) > 0) {
            $query->updatedTime->orderByDesc()->limit(1);
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
                environment: Application::getEnvironment(),
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
                $query = new LogErrorQuery();
                $query->updatedTime->orderByAsc();
                $query->limit($total - self::$maxLog);

                self::removeEntity($query);
            }
        }
        return true;
    }

    /**
     * Maps an Error Code into an Error word, and log location.
     * @param int $errorCode
     * @return array{string,int}
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
     * @return array{string,string}
     */
    private static function getBacktrace(string $description): array {
        if (Strings::contains($description, "Stack trace")) {
            $desParts    = Strings::split($description, "Stack trace:\n");
            $description = $desParts[0] ?? $description;
            $stacktrace  = $desParts[1] ?? "";

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
            $item       = $trace[$i] ?? [];
            $function   = $item["function"] ?? "";

            $backtrace .= "#{$index}- ";
            $backtrace .= (isset($item["file"]) ? self::getFilePath($item["file"]) : "<unknown file>") . " ";
            $backtrace .= "(". ($item["line"] ?? "<unknown line>") . ") ";
            $backtrace .= " -> {$function}()" . "\n";
            $index     += 1;
        }
        return [ $description, $backtrace ];
    }
}
