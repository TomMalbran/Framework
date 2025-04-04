<?php
// spell-checker: ignore MSIE
namespace Framework\Utils;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Server Utils
 */
class Server {

    /**
     * Returns true if running on Localhost
     * @param string[] $whitelist
     * @return boolean
     */
    public static function isLocalHost(array $whitelist = [ "127.0.0.1", "::1" ]): bool {
        return Arrays::contains($whitelist, $_SERVER["REMOTE_ADDR"]);
    }

    /**
     * Returns true if the Host starts with the given prefix
     * @param string $prefix
     * @return boolean
     */
    public static function hostStartsWith(string $prefix): bool {
        $host = Strings::toString($_SERVER["HTTP_HOST"]);
        return Strings::startsWith($host, $prefix);
    }



    /**
     * Returns the Origin Url
     * @param boolean $useForwarded
     * @return string
     */
    public static function getUrl(bool $useForwarded = false): string {
        if (empty($_SERVER["HTTP_HOST"])) {
            return "";
        }

        $ssl         = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on";
        $serverProto = Strings::toString($_SERVER["SERVER_PROTOCOL"]);
        $serverProto = Strings::toLowerCase($serverProto);
        $http        = Strings::substringBefore($serverProto, "/");
        $protocol    = $http . ($ssl ? "s" : "");

        $port        = Strings::toString($_SERVER["SERVER_PORT"]);
        $port        = (!$ssl && $port === "80") || ($ssl && $port === "443") ? "" : ":$port";

        $serverName  = Strings::toString($_SERVER["SERVER_NAME"]);
        $forwardHost = isset($_SERVER["HTTP_X_FORWARDED_HOST"]) ? Strings::toString($_SERVER["HTTP_X_FORWARDED_HOST"]) : "";
        $httpHost    = Strings::toString($_SERVER["HTTP_HOST"]);
        $host        = $useForwarded && $forwardHost !== "" ? $forwardHost : $httpHost;
        $host        = $host !== "" ? $host : $serverName . $port;

        return "$protocol://$host";
    }

    /**
     * Returns the Full Url
     * @param boolean $useForwarded
     * @return string
     */
    public static function getFullUrl(bool $useForwarded = false): string {
        return self::getUrl($useForwarded) . Strings::toString($_SERVER["REQUEST_URI"]);
    }

    /**
     * Returns the user IP
     * @return string
     */
    public static function getIP(): string {
        if ($_SERVER) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $ip = getenv("HTTP_CLIENT_IP");
            } else {
                $ip = getenv("REMOTE_ADDR");
            }
        }
        return Strings::toString($ip);
    }

    /**
     * Returns the User Agent
     * @return string
     */
    public static function getUserAgent(): string {
        return Strings::toString($_SERVER["HTTP_USER_AGENT"]);
    }

    /**
     * Returns the Platform from an User Agent
     * @param string $userAgent
     * @return string
     */
    public static function getPlatform(string $userAgent): string {
        $result = [];

        if (Strings::contains($userAgent, "Macintosh")) {
            $result[] = "MacOS";
        } elseif (Strings::contains($userAgent, "Windows")) {
            $result[] = "Windows";
        } elseif (Strings::contains($userAgent, "iPhone")) {
            $result[] = "iPhone";
        } elseif (Strings::contains($userAgent, "iPad")) {
            $result[] = "iPad";
        } elseif (Strings::contains($userAgent, "Android")) {
            $result[] = "Android";
        }

        if (Strings::contains($userAgent, "Firefox")) {
            $result[] = "FireFox";
        } elseif (Strings::contains($userAgent, "MSIE") || Strings::contains($userAgent, "Trident")) {
            $result[] = "IE";
        } elseif (Strings::contains($userAgent, "Chrome")) {
            $result[] = "Chrome";
        } elseif (Strings::contains($userAgent, "Safari")) {
            $result[] = "Safari";
        } elseif (Strings::contains($userAgent, "AIR")) {
            $result[] = "Air";
        } elseif (Strings::contains($userAgent, "Fluid")) {
            $result[] = "Fluid";
        }

        if (empty($result)) {
            $result[] = "Unknown";
        }
        return Strings::join($result, " ");
    }
}
