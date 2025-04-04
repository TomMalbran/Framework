<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * Several Utils functions
 */
class Utils {

    /**
     * Returns true if the given Full Name is valid
     * @param string $fullName
     * @return boolean
     */
    public static function isValidFullName(string $fullName): bool {
        $nameParts = Strings::split(trim($fullName), " ");
        return count($nameParts) > 1;
    }

    /**
     * Returns true if the given Email is valid
     * @param string $email
     * @return boolean
     */
    public static function isValidEmail(string $email): bool {
        $result = filter_var($email, FILTER_VALIDATE_EMAIL);
        return !empty($result);
    }

    /**
     * Returns true if the given Password is valid
     * @param string  $password
     * @param string  $checkSets Optional.
     * @param integer $minLength Optional.
     * @return boolean
     */
    public static function isValidPassword(string $password, string $checkSets = "ad", int $minLength = 6): bool {
        if (Strings::length($password) < $minLength) {
            return false;
        }
        if (Strings::contains($checkSets, "a") && !Strings::match($password, "#[a-zA-Z]+#")) {
            return false;
        }
        if (Strings::contains($checkSets, "l") && !Strings::match($password, "#[a-z]+#")) {
            return false;
        }
        if (Strings::contains($checkSets, "u") && !Strings::match($password, "#[A-Z]+#")) {
            return false;
        }
        if (Strings::contains($checkSets, "d") && !Strings::match($password, "#[0-9]+#")) {
            return false;
        }
        return true;
    }

    /**
     * Returns true if the given Domain is valid
     * @param string $domain
     * @return boolean
     */
    public static function isValidDomain(string $domain): bool {
        return Strings::match($domain, '/^([a-z]+\.)?([a-z0-9ñ]([-a-z0-9ñ]*[a-z0-9ñ])?)\.[a-z]{2,5}(\.[a-z]{2})?$/i');
    }

    /**
     * Returns true if the given Username is valid
     * @param string $username
     * @return boolean
     */
    public static function isValidUsername(string $username): bool {
        return Strings::match($username, '/^[a-z0-9]([-a-z0-9]*[a-z0-9])?$/i');
    }

    /**
     * Returns true if the given Color is valid
     * @param string $color
     * @return boolean
     */
    public static function isValidColor(string $color): bool {
        return Strings::match($color, '/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/');
    }

    /**
     * Returns true if the given CUIT is valid
     * @param string $value
     * @return boolean
     */
    public static function isValidCUIT(string $value): bool {
        $cuit = (string)self::cuitToNumber($value);
        if (Strings::length($cuit) !== 11) {
            return false;
        }

        // The last number is the verifier
        $verify = (int)$cuit[10];
        $mult   = [ 5, 4, 3, 2, 7, 6, 5, 4, 3, 2 ];
        $total  = 0;

        // Multiply each number by the multiplier (except the last one)
		for ($i = 0; $i < count($mult); $i++) {
            $total += (int)$cuit[$i] * $mult[$i];
		}

        // Calculate the left over and value
        $mod = $total % 11;
        if ($mod === 0) {
            return $verify === 0;
        }
        if ($mod === 1) {
            return $verify === 9;
        }
        return $verify === 11 - $mod;
    }

    /**
     * Returns true if the given DNI is valid
     * @param string $value
     * @return boolean
     */
    public static function isValidDNI(string $value): bool {
        $dni    = self::dniToNumber($value);
        $length = Strings::length($dni);
        if ($length < 6 || $length > 9) {
            return false;
        }
        return is_numeric($dni);
    }

    /**
     * Returns true if the given Phone is valid
     * @param string $value
     * @return boolean
     */
    public static function isValidPhone(string $value): bool {
        $phone = self::phoneToNumber($value);
        return is_numeric($phone);
    }



    /**
     * Parses a Full Name into a First and Last Name
     * @param string $fullName
     * @return array{string,string}
     */
    public static function parseName(string $fullName): array {
        $nameParts = Strings::split(trim($fullName), " ");
        if (count($nameParts) > 1) {
            $lastName  = array_pop($nameParts);
            $firstName = Strings::join($nameParts, " ");
        } else {
            $firstName = $fullName;
            $lastName  = "";
        }
        return [ $firstName, $lastName ];
    }

    /**
     * Parses the given CUIT if it has 11 chars
     * @param string $value
     * @return string
     */
    public static function parseCUIT(string $value): string {
        if (Strings::length($value) === 11) {
            return Strings::replacePattern($value, "/^(\d{2})(\d{8})(\d{1})$/", "$1-$2-$3");
        }
        return $value;
    }

    /**
     * Removes spaces and dots in the CUIT
     * @param string $value
     * @return string
     */
    public static function cuitToNumber(string $value): string {
        return Strings::toNumber($value);
    }

    /**
     * Removes spaces and dots in the DNI
     * @param string $value
     * @return string
     */
    public static function dniToNumber(string $value): string {
        return Strings::toNumber($value);
    }

    /**
     * Removes spaces, dashes and parenthesis in the Phone
     * @param string $value
     * @return string
     */
    public static function phoneToNumber(string $value): string {
        return Strings::toNumber($value);
    }

    /**
     * Generates a username from a domain
     * @param string $domain
     * @param string $email  Optional.
     * @return string
     */
    public static function generateUsername(string $domain, string $email = ""): string {
        $parts  = Strings::split($domain, ".");
        $result = Strings::replace($parts[0], [ "-", "ñ" ], [ "", "n" ]);
        $result = Strings::substring($result, 0, 8);

        if (!empty($email) && is_numeric($result[0])) {
            $result = Strings::substring($email[0] . $result, 0, 8);
        }
        return $result;
    }

    /**
     * Generates a domain from an email
     * @param string $email
     * @return string
     */
    public static function generateDomain(string $email): string {
        /** spell-checker: disable */
        $domains = [
            /* Default domains included */
            "aol.com", "att.net", "comcast.net", "facebook.com", "fb.com", "gmail.com", "gmx.com", "googlemail.com",
            "google.com", "hotmail.com", "hotmail.co.uk", "hotmail.es", "mac.com", "me.com", "mail.com", "msn.com",
            "live.com", "sbcglobal.net", "verizon.net", "yahoo.com", "yahoo.co.uk",

            /* Other global domains */
            "email.com", "fastmail.fm", "games.com" /* AOL */, "gmx.net", "hush.com", "hushmail.com", "icloud.com",
            "iname.com", "inbox.com", "lavabit.com", "love.com" /* AOL */, "outlook.com", "pobox.com", "protonmail.com",
            "rocketmail.com" /* Yahoo */, "safe-mail.net", "wow.com" /* AOL */, "ygm.com" /* AOL */,
            "ymail.com" /* Yahoo */, "zoho.com", "yandex.com",

            /* Argentinian ISP domains */
            "hotmail.com.ar", "live.com.ar", "yahoo.com.ar", "fibertel.com.ar", "speedy.com.ar", "arnet.com.ar",
            "ciudad.com.ar", "flash.com.ar", "outlook.com.ar",

            /* Other countries */
            "yahoo.es", "yahoo.com.br", "outlook.es", "hotmail.es",

            /* Special domain */
            "unbounce.com", "webadictos.net", "telefonica.com", "prueba.com", "tester.com", "demo.com",
        ];
        /** spell-checker: enable */

        if (self::isValidEmail($email)) {
            $domain = Strings::substringAfter($email, "@");
            $domain = Strings::toLowerCase($domain);
            if (!Arrays::contains($domains, $domain)) {
                return $domain;
            }
        }
        return "";
    }

    /**
     * Returns the extension of the given domain (without the dot)
     * @param string $domain
     * @return string
     */
    public static function getDomainExtension(string $domain): string {
        return Strings::substringAfter($domain, ".");
    }

    /**
     * Parses a Domain to try and return something like "domain.com"
     * @param string $domain
     * @return string
     */
    public static function parseDomain(string $domain): string {
        $domain = Strings::toLowerCase($domain);
        if (!Strings::startsWith($domain, "http://")) {
            $domain = "http://$domain";
        }
        $host = self::getHost($domain);

        if ($host) {
            return Strings::replace($host, "www.", "");
        }
        return "";
    }

    /**
     * Returns the host of the given Url
     * @param string $url
     * @return string
     */
    public static function getHost(string $url): string {
        if (empty($url)) {
            return "";
        }
        $result = parse_url($url, PHP_URL_HOST);
        return Strings::toString($result);
    }

    /**
     * Returns true if the given domain or www.domain is delegated
     * @param string $domain
     * @param string $serverIP Optional.
     * @return boolean
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
     * @return boolean
     */
    public static function verifyDelegation(string $domain, string $serverIP = ""): bool {
        $host = gethostbyname($domain);
        if (!empty($serverIP)) {
            return !empty($host) && $host === $serverIP;
        }
        return !empty($host) && $host !== $domain;
    }



    /**
     * Returns true if is a valid Url
     * @param string $url
     * @return boolean
     */
    public static function isValidUrl(string $url): bool {
        return Strings::startsWith($url, "http://", "https://");
    }

    /**
     * Encodes the given Url
     * @param string $url
     * @return string
     */
    public static function encodeUrl(string $url): string {
        return Strings::replaceCallback($url, "/[\ \"<>`\\x{0080}-\\x{FFFF}]+/u", function (array $match) {
            $value = Strings::toString($match[0]);
            return rawurlencode($value);
        });
    }

    /**
     * Returns an Avatar url
     * @param string $url
     * @param string $email
     * @return string
     */
    public static function getAvatarUrl(string $url, string $email): string {
        if (!empty($url)) {
            return $url;
        }
        $username = md5($email);
        return "https://gravatar.com/avatar/$username?default=mp";
    }

    /**
     * Returns a WhatsApp url
     * @param string $whatsApp
     * @return string
     */
    public static function getWhatsAppUrl(string $whatsApp): string {
        return "https://wa.me/$whatsApp";
    }
}
