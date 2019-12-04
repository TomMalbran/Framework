<?php
namespace Framework\Utils;

use Framework\Utils\Strings;

/**
 * Several Utils functions
 */
class Utils {
    
    /**
     * Rounds the given number to the given decimals
     * @param integer $number
     * @param integer $decimals
     * @return integer
     */
    public static function roundNumber($number, $decimals) {
        if (is_numeric($number)) {
            $padding = pow(10, $decimals);
            return ceil($number * $padding) / $padding;
        }
        return 0;
    }
    
    /**
     * Returns the given number as an integer using the given decimals
     * @param integer $number
     * @param integer $decimals
     * @return integer
     */
    public static function toInt($number, $decimals) {
        if (is_numeric($number)) {
            $padding = pow(10, $decimals);
            return round($number * $padding);
        }
        return 0;
    }
    
    /**
     * Returns the given number as a float using the given decimals
     * @param integer $number
     * @param integer $decimals
     * @return integer
     */
    public static function toFloat($number, $decimals) {
        $padding = pow(10, $decimals);
        return $number / $padding;
    }
    
    /**
     * Returns a number from the given value
     * @param mixed $value
     * @return integer
     */
    public static function toNumber($value) {
        return !empty($value) ? $value : 0;
    }
    
    /**
     * Returns a percent from the given values
     * @param integer $number
     * @param integer $total
     * @param integer $decimals Optional.
     * @return integer
     */
    public static function toPercent($number, $total, $decimals = 0) {
        return $total == 0 ? 0 : self::roundNumber($number * 100 / $total, $decimals);
    }
    
    /**
     * Returns a division from the given values
     * @param integer $numerator
     * @param integer $divisor
     * @param integer $decimals  Optional.
     * @return integer
     */
    public static function toDivision($numerator, $divisor, $decimals = 0) {
        return $divisor == 0 ? 0 : self::roundNumber($numerator / $divisor, $decimals);
    }
    
    /**
     * Maps a value that is in the from range to the to range
     * @param integer $value
     * @param integer $fromLow
     * @param integer $fromHigh
     * @param integer $toLow
     * @param integer $toHigh
     * @return integer
     */
    public static function mapValue($value, $fromLow, $fromHigh, $toLow, $toHigh) {
        $fromRange = $fromHigh - $fromLow;
        $toRange   = $toHigh - $toLow;
        if ($fromRange == 0) {
            return $toLow;
        }
        $scaleFactor = $toRange / $fromRange;

        // Re-zero the value within the from range
        $tmpValue = $value - $fromLow;
        // Rescale the value to the to range
        $tmpValue *= $scaleFactor;
        // Re-zero back to the to range
        return $tmpValue + $toLow;
    }
    
    
    
    /**
     * Rounds the given price to 2 decimals
     * @param integer $price
     * @return integer
     */
    public static function roundCents($price) {
        return self::roundNumber($price, 2);
    }
    
    /**
     * Returns the given price in Cents
     * @param integer $price
     * @return integer
     */
    public static function toCents($price) {
        return self::toInt($price, 2);
    }
    
    /**
     * Returns the given price in Dollars
     * @param integer $price
     * @return integer
     */
    public static function fromCents($price) {
        return self::toFloat($price, 2);
    }

    /**
     * Returns a price using the right format
     * @param integer $price
     * @param integer $decimals Optional.
     * @return string
     */
    public static function formatPrice($price, $decimals = 2) {
        $price = floatval($price);
        if (!empty($price)) {
            return number_format($price, $decimals, ",", "");
        }
        return "";
    }

    /**
     * Returns a price using the right format
     * @param integer $cents
     * @param integer $decimals Optional.
     * @return string
     */
    public static function formatCents($cents, $decimals = 2) {
        $price = self::fromCents($cents);
        return self::formatPrice($price, $decimals);
    }

    /**
     * Returns a price string
     * @param integer $price
     * @return string
     */
    public static function toPriceString($price) {
        $millions = round($price / 1000000);
        if ($millions > 10) {
            return "${$millions}m";
        }
        $kilos = round($price / 1000);
        if ($kilos > 10) {
            return "${$kilos}k";
        }
        $price = round($price);
        return "${$price}";
    }
    
    

    /**
     * Returns true if the given value is alpha-numeric
     * @param string $value
     * @return boolean
     */
    public static function isAlphaNum($value) {
        return ctype_alnum($value);
    }
    
    /**
     * Returns true if the given value is a number and greater and/or equal to cero
     * @param mixed   $number
     * @param integer $min    Optional.
     * @param integer $max    Optional.
     * @return boolean
     */
    public static function isNumeric($number, $min = 1, $max = null) {
        return is_numeric($number) && $number >= $min && ($max != null ? $number <= $max : true);
    }
    
    /**
     * Returns true if the given price is valid
     * @param mixed   $price
     * @param integer $min   Optional.
     * @param integer $max   Optional.
     * @return boolean
     */
    public static function isValidPrice($price, $min = 1, $max = null) {
        return self::isNumeric($price * 100, $min, $max);
    }
    
    /**
     * Returns true if the given email is valid
     * @param string $email
     * @return boolean
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Returns true if the given password is valid
     * @param string  $password
     * @param string  $checkSets Optional.
     * @param integer $minLength Optional.
     * @return boolean
     */
    public static function isValidPassword($password, $checkSets = "lud", $minLength = 4) {
        if (strlen($password) < $minLength) {
            return false;
        }
        if (strpos($checkSets, "l") !== false && !preg_match("#[a-z]+#", $password)) {
            return false;
        }
        if (strpos($checkSets, "u") !== false && !preg_match("#[A-Z]+#", $password)) {
            return false;
        }
        if (strpos($checkSets, "d") !== false && !preg_match("#[0-9]+#", $password)) {
            return false;
        }
        return true;
    }
    
    /**
     * Returns true if the given domain is valid
     * @param string $domain
     * @return boolean
     */
    public static function isValidDomain($domain) {
        return preg_match('/^([a-z0-9ñ]([-a-z0-9ñ]*[a-z0-9ñ])?)\.[a-z]{2,5}(\.[a-z]{2})?$/i', $domain);
    }
    
    /**
     * Returns true if the given username is valid
     * @param string $username
     * @return boolean
     */
    public static function isValidUsername($username) {
        return preg_match('/^[a-z]+[a-z0-9]{2,11}$/i', $username);
    }
    
    /**
     * Returns true if the given name is valid
     * @param string $name
     * @return boolean
     */
    public static function isValidName($name) {
        return preg_match('/^[a-z0-9]([-a-z0-9]*[a-z0-9])?$/i', $name);
    }
    
    /**
     * Returns true if the given CUIT is valid
     * @param string $value
     * @return boolean
     */
    public static function isValidCUIT($value) {
        $cuit = (string)self::cuitToNumber($value);
        if (strlen($cuit) != 11) {
            return false;
        }

        // The last number is the verifier
        $verify = substr($cuit, 10, 1);
        $mult   = [ 5, 4, 3, 2, 7, 6, 5, 4, 3, 2 ];
        $total  = 0;
        
        // Multiply each number by the multiplier (except the last one)
		for ($i = 0; $i < count($mult); $i++) {
            $total += (substr($cuit, $i, 1)) * $mult[$i];
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
    public static function isValidDNI($value) {
        $dni = (string)self::dniToNumber($value);
        if (strlen($dni) != 8) {
            return false;
        }
        return is_numeric((int)$dni);
    }

    
    
    /**
     * Parses the given CUIT if it has 11 chars
     * @param string $value
     * @return string
     */
    public static function parseCUIT($value) {
        if (strlen((string)$value) == 11) {
            return preg_replace("/^(\d{2})(\d{8})(\d{1})$/", "$1-$2-$3", $value);
        }
        return $value;
    }
    
    /**
     * Removes spaces and dashes in the CUIT
     * @param string $value
     * @return string
     */
    public static function cuitToNumber($value) {
        return Strings::replace($value, [ " ", "-" ], "");
    }

    /**
     * Removes spaces and dashes in the CUIT
     * @param string $value
     * @return string
     */
    public static function dniToNumber($value) {
        return Strings::replace($value, [ " ", "." ], "");
    }
    
    
    
    /**
     * Returns the Real Name for the given User
     * @param mixed  $data
     * @param string $prefix
     * @return string
     */
    public static function createRealName($data, $prefix = "") {
        $id        = self::getValue($data, "credentialID", "", $prefix);
        $firstName = self::getValue($data, "firstName",    "", $prefix);
        $lastName  = self::getValue($data, "lastName",     "", $prefix);
        $nickName  = self::getValue($data, "nickName",     "", $prefix);
        $result    = "#$id";
        
        if (!empty($firstName) && !empty($lastName)) {
            $result = "$firstName $lastName";
        }
        if (!empty($nickName)) {
            $result .= " ($nickName)";
        }
        return $result;
    }
    
    /**
     * Generates a username from a domain
     * @param string $domain
     * @param string $email  Optional.
     * @return string
     */
    public static function generateUsername($domain, $email = "") {
        $parts  = explode(".", $domain);
        $result = Strings::replace($parts[0], ["-", "ñ"], ["", "n"]);
        $result = substr($result, 0, 8);
        
        if (!empty($email) && is_numeric($result[0])) {
            $result = substr($email[0] . $result, 0, 8);
        }
        return $result;
    }
    
    /**
     * Generates a domain from an email
     * @param string $email
     * @return string
     */
    public static function generateDomain($email) {
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
            $domain = substr($email, strrpos($email, "@") + 1);
            if (!in_array(Strings::toLowerCase($domain), $domains)) {
                return $domain;
            }
        }
        return "";
    }
    
    /**
     * Generates a random password with the given options
     * @param integer $length        Optional.
     * @param string  $availableSets Optional.
     * @return string
     */
    public static function generatePassword($length = 8, $availableSets = "lud") {
        $sets   = [];
        $all    = "";
        $result = "";
        
        if (Strings::contains($availableSets, "l")) {
            $sets[] = "abcdefghjkmnpqrstuvwxyz";
        }
        if (Strings::contains($availableSets, "u")) {
            $sets[] = "ABCDEFGHJKMNPQRSTUVWXYZ";
        }
        if (Strings::contains($availableSets, "d")) {
            $sets[] = "23456789";
        }
        if (Strings::contains($availableSets, "s")) {
            $sets[] = "!@#$%&*?";
        }
        
        foreach ($sets as $set) {
            $result .= $set[array_rand(str_split($set))];
            $all    .= $set;
        }
        
        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++) {
            $result .= $all[array_rand($all)];
        }
        
        $result = str_shuffle($result);
        return $result;
    }
    
    /**
     * Returns a random string
     * @param integer $length Optional.
     * @return string
     */
    public static function createCode($length = 50) {
        return substr(md5(rand()), 0, $length);
    }

    /**
     * Grabs the given password and creates encrypts it
     * @param string $pass
     * @param string $salt Optional.
     * @return array
     */
    public static function createHash($pass, $salt = "") {
        $salt = !empty($salt) ? $salt : self::createCode();
        $hash = base64_encode(hash_hmac("sha256", $pass, $salt, true));
        return [ "password" => $hash, "salt" => $salt ];
    }
    
    
    
    /**
     * Returns the extension of the given domain
     * @param string $domain
     * @return string
     */
    public static function getDomainExtension($domain) {
        return substr($domain, strrpos($domain, "."));
    }
    
    /**
     * Parsea a Domain to try and return something like "domain.com"
     * @param string $domain
     * @return string
     */
    public static function parseDomain($domain) {
        $domain = Strings::toLowerCase($domain);
        if (Strings::startsWith($domain, "http")) {
            $domain = "http://" . $domain;
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
    public static function isDelegated($domain, $serverIP = "") {
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
    public static function verifyDelegation($domain, $serverIP = "") {
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
    public static function isLocalHost(array $whitelist = [ "127.0.0.1", "::1" ]) {
        return in_array($_SERVER["REMOTE_ADDR"], $whitelist);
    }

    /**
     * Returns true if running on a Stage host
     * @return boolean
     */
    public static function isStageHost() {
        return Strings::startsWith($_SERVER["HTTP_HOST"], "dev.");
    }

    /**
     * Returns the user IP
     * @return string
     */
    public static function getIP() {
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
    


    /**
     * Converts a single value or an array into an array
     * @param array|mixed $array
     * @return array
     */
    public static function toArray($array) {
        return is_array($array) ? $array : [ $array ];
    }
    
    /**
     * Removes the empty entries from the given array
     * @param array $array
     * @return array
     */
    public static function removeEmpty(array $array) {
        $result = [];
        foreach ($array as $value) {
            if (!empty($value)) {
                $result[] = $value;
            }
        }
        return $result;
    }
    
    /**
     * Returns an array with values in the Base
     * @param array $base
     * @param array $array
     * @return array
     */
    public static function subArray(array $base, array $array) {
        $result = [];
        foreach ($array as $value) {
            if (in_array($value, $base)) {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Extends the first array replacing values from the second array 
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function extend(array &$array1, array &$array2) {
        $result = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = self::extend($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Creates a map using the given data
     * @param array           $data
     * @param string          $key
     * @param string|string[] $value Optional.
     * @return array
     */
    public static function createMap(array $data, $key, $value = "") {
        $result  = [];
        foreach ($data as $row) {
            $result[$row[$key]] = !empty($value) ? self::getValue($row, $value) : $row;
        }
        return $result;
    }
    
    /**
     * Creates an array using the given data
     * @param array           $data
     * @param string|string[] $value Optional.
     * @return array
     */
    public static function createArray(array $data, $value = "") {
        $result = [];
        foreach ($data as $row) {
            $result[] = !empty($value) ? self::getValue($row, $value) : $row;
        }
        return $result;
    }
    
    /**
     * Creates a select using the given data
     * @param array           $data
     * @param string          $key
     * @param string|string[] $value
     * @return array
     */
    public static function createSelect(array $data, $key, $value) {
        $result = [];
        foreach ($data as $row) {
            $result[] = [
                "key"   => $row[$key],
                "value" => self::getValue($row, $value),
            ];
        }
        return $result;
    }
    
    /**
     * Creates a select using the given data
     * @param array $data
     * @return array
     */
    public static function createSelectFromMap(array $data) {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = [
                "key"   => $key,
                "value" => $value,
            ];
        }
        return $result;
    }
    
    /**
     * Returns the key adding the prefix or not
     * @param string $key
     * @param string $prefix Optional.
     * @return string
     */
    public static function getKey($key, $prefix = "") {
        return !empty($prefix) ? $prefix . ucfirst($key) : $key;
    }

    /**
     * Returns one or multiple values as a string
     * @param mixed           $row
     * @param string|string[] $key
     * @param string          $glue   Optional.
     * @param string          $prefix Optional.
     * @return string
     */
    public static function getValue($row, $key, $glue = " - ", $prefix = "") {
        $result = "";
        if (is_array($key)) {
            $values = [];
            foreach ($key as $id) {
                $fullKey = self::getKey($id, $prefix);
                if (!empty($row[$fullKey])) {
                    $values[] = $row[$fullKey];
                }
            }
            $result = implode($glue, $values);
        } else {
            $fullKey = self::getKey($key, $prefix);
            if (!empty($row[$fullKey])) {
                $result = $row[$fullKey];
            }
        }
        return $result;
    }
}
