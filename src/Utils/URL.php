<?php
namespace Framework\Utils;

use Framework\Utils\JSON;
use Framework\Utils\Strings;

/**
 * The URL Utils
 */
class URL {

    /**
     * Returns true if is a valid URL
     * @param string $url
     * @return bool
     */
    public static function isValid(string $url): bool {
        return Strings::startsWith($url, "http://", "https://");
    }

    /**
     * Returns the host of the given URL
     * @param string $url
     * @return string
     */
    public static function getHost(string $url): string {
        if ($url === "") {
            return "";
        }

        $result = parse_url($url, PHP_URL_HOST);
        return Strings::toString($result);
    }



    /**
     * Returns true if the given Domain is valid
     * @param string $domain
     * @return bool
     */
    public static function isValidDomain(string $domain): bool {
        return Strings::match($domain, '/^([a-z]+\.)?([a-z0-9ñ]([-a-z0-9ñ]*[a-z0-9ñ])?)\.[a-z]{2,5}(\.[a-z]{2})?$/i');
    }

    /**
     * Returns the Domain of the given URL
     * @param string $url
     * @return string
     */
    public static function getDomain(string $url): string {
        $domain = Strings::toLowerCase($url);
        if (!Strings::startsWith($domain, "http://", "https://")) {
            $domain = "http://$domain";
        }

        $host = self::getHost($domain);
        if ($host !== "") {
            return Strings::replace($host, "www.", "");
        }
        return "";
    }

    /**
     * Returns the extension of the given domain (without the dot)
     * @param string $url
     * @return string
     */
    public static function getDomainExtension(string $url): string {
        $domain = self::getDomain($url);
        return Strings::substringAfter($domain, ".");
    }

    /**
     * Returns true if the given domain or www.domain is delegated
     * @param string $domain
     * @param string $serverIP Optional.
     * @return bool
     */
    public static function isDelegated(string $domain, string $serverIP = ""): bool {
        if (self::verifyDelegation($domain, $serverIP)) {
            return true;
        }
        return self::verifyDelegation("www.$domain", $serverIP);
    }

    /**
     * Returns true if the given domain is delegated
     * @param string $domain
     * @param string $serverIP Optional.
     * @return bool
     */
    public static function verifyDelegation(string $domain, string $serverIP = ""): bool {
        $host = gethostbyname($domain);
        if ($serverIP !== "") {
            return $host !== "" && $host === $serverIP;
        }
        return $host !== "" && $host !== $domain;
    }



    /**
     * Returns true if the given string is a valid slug
     * @param string $string
     * @return bool
     */
    public static function isValidSlug(string $string): bool {
        return Strings::match($string, '/^[a-z0-9\-]+$/');
    }

    /**
     * Returns a Slug from the given string
     * @param string $string
     * @return string
     */
    public static function toSlug(string $string): string {
        $result = Strings::toString($string);
        $result = Strings::sanitize($result, lowercase: true, anal: true);
        $result = Strings::replace($result, "---", "-");
        $result = Strings::replace($result, "--", "-");
        return $result;
    }



    /**
     * Encodes the given URL
     * @param string $url
     * @return string
     */
    public static function encode(string $url): string {
        return Strings::replaceCallback($url, "/[\ \"<>`\\x{0080}-\\x{FFFF}]+/u", function (array $match) {
            $value = Strings::toString($match[0] ?? "");
            return rawurlencode($value);
        });
    }

    /**
     * Encodes the URL spaces
     * @param string $url
     * @return string
     */
    public static function encodeSpaces(string $url): string {
        return str_replace(" ", "%20", $url);
    }

    /**
     * Decodes the URL spaces
     * @param string $url
     * @return string
     */
    public static function decodeSpaces(string $url): string {
        return str_replace("%20", " ", $url);
    }



    /**
     * Adds Params to a URL
     * @param string                   $url
     * @param array<string,mixed>|null $params Optional.
     * @return string
     */
    public static function addParams(string $url, ?array $params = null): string {
        $parsedParams = self::parseParams($params);
        if ($parsedParams !== "") {
            $prefix = Strings::contains($url, "?") ? "&" : "?";
            $url   .= $prefix . $parsedParams;
        }
        return $url;
    }

    /**
     * Parses the URL Params
     * @param array<string,mixed>|null $params Optional.
     * @return string
     */
    public static function parseParams(?array $params = null): string {
        if ($params === null || count($params) === 0) {
            return "";
        }

        $content = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $content[] = "$key=" . urlencode(JSON::encode($value));
            } elseif (is_bool($value)) {
                $content[] = "$key=" . ($value ? "true" : "false");
            } elseif ($value !== null) {
                $content[] = "$key=" . urlencode(Strings::toString($value));
            }
        }

        return Strings::join($content, "&");
    }

    /**
     * Replaces the URLs in an HTML String
     * @param string $string
     * @param string $url
     * @return string
     */
    public static function replaceInHtml(string $string, string $url): string {
        $regex = '/<(img|audio|video)([^>]*?)src=["\']((?!https?:\/\/|\/\/|data:)[^"\']+)["\']([^>]*?)>/i';
        return Strings::replaceCallback($string, $regex, function (array $matches) use ($url) {
            $tag          = Strings::toString($matches[1] ?? "");
            $beforeSrc    = Strings::toString($matches[2] ?? "");
            $relativePath = Strings::toString($matches[3] ?? "");
            $afterSrc     = Strings::toString($matches[4] ?? "");
            $absolutePath = "$url/{$relativePath}";
            return "<$tag{$beforeSrc}src=\"$absolutePath\"{$afterSrc}>";
        });
    }
}
