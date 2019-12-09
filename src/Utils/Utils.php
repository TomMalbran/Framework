<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * Several Utils functions
 */
class Utils {
    
    /**
     * Returns true if the given value is alpha-numeric
     * @param string $value
     * @return boolean
     */
    public static function isAlphaNum(string $value): bool {
        return ctype_alnum($value);
    }
    
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
    public static function isValidPassword(string $password, string $checkSets = "lud", int $minLength = 4): bool {
        if (Strings::length($password) < $minLength) {
            return false;
        }
        if (Strings::contains($checkSets, "l") && !preg_match("#[a-z]+#", $password)) {
            return false;
        }
        if (Strings::contains($checkSets, "u") && !preg_match("#[A-Z]+#", $password)) {
            return false;
        }
        if (Strings::contains($checkSets, "d") && !preg_match("#[0-9]+#", $password)) {
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
        return preg_match('/^([a-z0-9ñ]([-a-z0-9ñ]*[a-z0-9ñ])?)\.[a-z]{2,5}(\.[a-z]{2})?$/i', $domain);
    }
    
    /**
     * Returns true if the given username is valid
     * @param string $username
     * @return boolean
     */
    public static function isValidUsername(string $username): bool {
        return preg_match('/^[a-z]+[a-z0-9]{2,11}$/i', $username);
    }
    
    /**
     * Returns true if the given name is valid
     * @param string $name
     * @return boolean
     */
    public static function isValidName(string $name): bool {
        return preg_match('/^[a-z0-9]([-a-z0-9]*[a-z0-9])?$/i', $name);
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
        $verify = Strings::substring($cuit, 10, 1);
        $mult   = [ 5, 4, 3, 2, 7, 6, 5, 4, 3, 2 ];
        $total  = 0;
        
        // Multiply each number by the multiplier (except the last one)
		for ($i = 0; $i < count($mult); $i++) {
            $total += ((int)Strings::substring($cuit, $i, 1)) * $mult[$i];
		}
        
        // Calculate the left over and value
		$mod   = $total % 11;
        $digit = (string)($mod == 0 ? 0 : $mod == 1 ? 9 : 11 - $mod);
 
        return $verify == $digit;
    }

    /**
     * Returns true if the given DNI is valid
     * @param string $value
     * @return boolean
     */
    public static function isValidDNI(string $value): bool {
        $dni = self::dniToNumber($value);
        if (Strings::length($dni) != 8) {
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
        $result = Strings::replace($parts[0], ["-", "ñ"], ["", "n"]);
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
     * Converts an array or string to a CSV string
     * @param string[]|string $value
     * @return string
     */
    public static function toCSV($value): string {
        if (is_string($value)) {
            $parts = Strings::split($value, ",");
            $parts = Arrays::removeEmpty($parts);
            return Strings::join($parts, ",");
        }
        if (is_array($value)) {
            $parts = Arrays::removeEmpty($value);
            return Strings::join($parts, ",");
        }
        return "";
    }

    /**
     * Converts an array or string to a CSV array
     * @param string[]|string $value
     * @return array
     */
    public static function fromCSV($value): array {
        if (is_string($value)) {
            return Strings::split($value, ",");
        }
        if (is_array($value)) {
            return $value;
        }
        return [];
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
     * Returns true if running on Localhost
     * @param array $whitelist
     * @return boolean
     */
    public static function isLocalHost(array $whitelist = [ "127.0.0.1", "::1" ]): bool {
        return Arrays::contains($whitelist, $_SERVER["REMOTE_ADDR"]);
    }

    /**
     * Returns true if running on a Stage host
     * @return boolean
     */
    public static function isStageHost(): bool {
        return Strings::startsWith($_SERVER["HTTP_HOST"], "dev.");
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
        return $ip;
    }
}
