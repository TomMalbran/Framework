<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * Several Utils functions
 */
class Utils {

    /**
     * Returns true if the given email is valid
     * @param string $email
     * @return boolean
     */
    public static function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Returns true if the given password is valid
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
     * Returns true if the given domain is valid
     * @param string $domain
     * @return boolean
     */
    public static function isValidDomain(string $domain): bool {
        return Strings::match($domain, '/^([a-z0-9ñ]([-a-z0-9ñ]*[a-z0-9ñ])?)\.[a-z]{2,5}(\.[a-z]{2})?$/i');
    }

    /**
     * Returns true if the given username is valid
     * @param string $username
     * @return boolean
     */
    public static function isValidUsername(string $username): bool {
        return Strings::match($username, '/^[a-z]+[a-z0-9]{2,11}$/i');
    }

    /**
     * Returns true if the given name is valid
     * @param string $name
     * @return boolean
     */
    public static function isValidName(string $name): bool {
        return Strings::match($name, '/^[a-z0-9]([-a-z0-9]*[a-z0-9])?$/i');
    }

    /**
     * Returns true if the given CUIT is valid
     * @param string $value
     * @return boolean
     */
    public static function isValidCUIT(string $value): bool {
        $cuit = (string)self::cuitToNumber($value);
        if (Strings::length($cuit) != 11) {
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
        if ($mod == 0) {
            return $verify == 0;
        }
        if ($mod == 1) {
            return $verify == 9;
        }
        return $verify == 11 - $mod;
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
        return is_numeric((int)$dni);
    }



    /**
     * Parses the given CUIT if it has 11 chars
     * @param string $value
     * @return string
     */
    public static function parseCUIT(string $value): string {
        if (Strings::length($value) == 11) {
            return preg_replace("/^(\d{2})(\d{8})(\d{1})$/", "$1-$2-$3", $value);
        }
        return $value;
    }

    /**
     * Removes spaces and dashes in the CUIT
     * @param string $value
     * @return string
     */
    public static function cuitToNumber(string $value): string {
        return Strings::replace($value, [ " ", "-" ], "");
    }

    /**
     * Removes spaces and dashes in the CUIT
     * @param string $value
     * @return string
     */
    public static function dniToNumber(string $value): string {
        return Strings::replace($value, [ " ", "." ], "");
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
     * Parsea a Domain to try and return something like "domain.com"
     * @param string $domain
     * @return string
     */
    public static function parseDomain(string $domain): string {
        $domain = Strings::toLowerCase($domain);
        if (!Strings::startsWith($domain, "http://")) {
            $domain = "http://$domain";
        }
        $host = parse_url($domain, PHP_URL_HOST);

        if ($host) {
            return Strings::replace($host, "www.", "");
        }
        return "";
    }

    /**
     * Returns true if the gven domain or www.domain is delegated
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
     * Returns true if the gven domain is delegated
     * @param string $domain
     * @param string $serverIP Optional.
     * @return boolean
     */
    public static function verifyDelegation(string $domain, string $serverIP = ""): bool {
        $host = gethostbyname($domain);
        if (!empty($serverIP)) {
            return !empty($host) && $host == $serverIP;
        }
        return !empty($host) && $host != $domain;
    }



    /**
     * Returns the memory in MB or GB with the units
     * @param integer $memory
     * @return string
     */
    public static function parseMemory(int $memory): string {
        return $memory < 1024 ? $memory . " MB" : ($memory / 1024) . " GB";
    }



    /**
     * Returns a WhatsApp url
     * @param string $whtasapp
     * @return string
     */
    public static function getWhatsAppUrl(string $whtasapp): string {
        return "https://wa.me/$whtasapp";
    }

    /**
     * Returns true if is a valid Zoom url
     * @param string $url
     * @return boolean
     */
    public static function isValidZoom(string $url): bool {
        return Strings::startsWith($url, "https://us02web.zoom.us/j/");
    }
}
