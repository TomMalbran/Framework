<?php
namespace Framework\Utils;

use Framework\Utils\Strings;

/**
 * Several Utils functions
 */
class Utils {

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
     * Returns true if the given Color is valid
     * @param string $color
     * @return boolean
     */
    public static function isValidColor(string $color): bool {
        return Strings::match($color, '/#([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?\b/');
    }



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
     * Returns true if the given Username is valid
     * @param string $username
     * @return boolean
     */
    public static function isValidUsername(string $username): bool {
        return Strings::match($username, '/^[a-z0-9]([-a-z0-9]*[a-z0-9])?$/i');
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

        if ($email !== "" && is_numeric($result[0])) {
            $result = Strings::substring($email[0] . $result, 0, 8);
        }
        return $result;
    }



    /**
     * Returns true if the given Email is valid
     * @param string $email
     * @return boolean
     */
    public static function isValidEmail(string $email): bool {
        $result = filter_var($email, FILTER_VALIDATE_EMAIL);
        return $result !== false;
    }

    /**
     * Extracts the first Email from the given text
     * @param string $text
     * @return string
     */
    public static function extractEmail(string $text): string {
        preg_match_all("/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $text, $matches);
        $result = $matches[0][0] ?? "";
        return Strings::toString($result);
    }

    /**
     * Returns the given Email hiding most of the name and showing the domain
     * @param string $email
     * @return string
     */
    public static function hideEmail(string $email): string {
        if ($email === "" || !self::isValidEmail($email)) {
            return "";
        }

        $domain  = self::getEmailDomain($email);
        $name    = Strings::substringBefore($email, "@");
        $length  = Strings::length($name);
        $nameLen = $length > 3 ? 3 : 1;
        $hidden  = Strings::substring($name, 0, $nameLen) . Strings::repeat("*", $length - $nameLen);
        return "$hidden@$domain";
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
     * Removes spaces, dashes and parenthesis in the Phone
     * @param string $value
     * @return string
     */
    public static function phoneToNumber(string $value): string {
        return Strings::toNumber($value);
    }

    /**
     * Returns the given Phone hiding most of the numbers
     * @param string $phone
     * @return string
     */
    public static function hidePhone(string $phone): string {
        if ($phone === "" || !self::isValidPhone($phone)) {
            return "";
        }

        $length = Strings::length($phone);
        if ($length < 3) {
           return $length === 1 ? "*" : "*" . Strings::substring($phone,  - 1);
        }

        $middle     = "";
        $partSize   = (int)floor($length / 3);
        $middleSize = $length - ($partSize * 2);

        for ($i = 0; $i < $middleSize; $i++) {
            $middle .= "*";
        }
        return Strings::substring($phone, 0, $partSize) . $middle . Strings::substring($phone, -$partSize);
    }



    /**
     * Returns true if the given CUIT is valid
     * @param string $value
     * @return boolean
     */
    public static function isValidCUIT(string $value): bool {
        $cuit = self::cuitToNumber($value);
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
     * Removes spaces and dots in the DNI
     * @param string $value
     * @return string
     */
    public static function dniToNumber(string $value): string {
        return Strings::toNumber($value);
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
     * Extracts the Domain from the given email
     * @param string $email
     * @return string
     */
    public static function getEmailDomain(string $email): string {
        if (!self::isValidEmail($email)) {
            return "";
        }

        $result = Strings::substringAfter($email, "@");
        $result = Strings::toLowerCase($result);
        return $result;
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

        if ($host !== "") {
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
        if ($url === "") {
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
        if ($serverIP !== "") {
            return $host !== "" && $host === $serverIP;
        }
        return $host !== "" && $host !== $domain;
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
        if ($url !== "") {
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
