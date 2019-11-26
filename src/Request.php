<?php
namespace Framework;

use Framework\File\File;
use Framework\File\Image;
use Framework\File\FileType;
use Framework\Utils\DateTime;
use Framework\Utils\JSON;
use Framework\Utils\Utils;
use ArrayAccess;

/**
 * The Request Wrapper
 */
class Request implements ArrayAccess {
    
    private $request;
    private $files;
    
    
    /**
     * Creates a new Request instance
     * @param array $request Optional.
     */
    public function __construct(array $request = null) {
        $this->request = $request ?: $_REQUEST;
        $this->files   = $_FILES;
    }
    
    
    
    /**
     * Returns the request data at the given key
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        return $this->get($key);
    }
    
    /**
     * Sets the given key on the request data with the given value
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function __set($key, $value) {
        $this->set($key, $value);
    }
    
    /**
     * Returns true if the given key is set in the request data
     * @param string $key
     * @return boolean
     */
    public function __isset($key) {
        return $this->exists($key);
    }
    
    
    
    /**
     * Returns the request data at the given key or the default
     * @param string $key
     * @param mixed  $default Optional.
     * @return mixed
     */
    public function get($key, $default = "") {
        return isset($this->request[$key]) ? $this->request[$key] : $default;
    }
    
    /**
     * Returns the request data at the given key or the default
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getInt($key, $default = 0) {
        return isset($this->request[$key]) ? (int)$this->request[$key] : $default;
    }
    
    /**
     * Returns the request data at the given key as an array and removing the empty entries
     * @param string $key
     * @return array
     */
    public function getArray($key) {
        return Utils::removeEmpty($this->get($key, []));
    }
    
    /**
     * Returns the request data at the given key from an array or the default
     * @param string  $key
     * @param integer $index
     * @param mixed   $default Optional.
     * @return mixed
     */
    public function getFromArray($key, $index, $default = "") {
        if (isset($this->request[$key]) && isset($this->request[$key][$index])) {
            return $this->request[$key][$index];
        }
        return $default;
    }
    
    /**
     * Returns the request data at the given key as JSON
     * @param string  $key
     * @param boolean $asArray Optional.
     * @return string
     */
    public function getJSON($key, $asArray = false) {
        return JSON::decode($this->get($key, "[]"), $asArray);
    }



    /**
     * Sets the given key on the request data with the given value
     * @param string $key
     * @param mixed  $value Optional.
     * @return Request
     */
    public function set($key, $value = "") {
        $this->request[$key] = $value;
        return $this;
    }
    
    /**
     * Sets the data of the give object
     * @param array $object
     * @return Request
     */
    public function setObject(array $object) {
        foreach ($object as $key => $value) {
            $this->request[$key] = $value;
        }
        return $this;
    }

    /**
     * Removes the data at the given key
     * @param string $key
     * @return Request
     */
    public function remove($key) {
        if ($this->exists($key)) {
            unset($this->request[$key]);
        }
        return $this;
    }



    /**
     * Returns true if the given key exists in the request data
     * @param string|string[] $key   Optional.
     * @param integer         $index Optional.
     * @return boolean
     */
    public function has($key = null, $index = null) {
        if ($key === null) {
            return !empty($this->request);
        }
        if (is_array($key)) {
            foreach ($key as $keyID) {
                if (empty($this->request[$keyID])) {
                    return false;
                }
            }
            return true;
        }
        if ($index !== null) {
            return !empty($this->request[$key]) && !empty($this->request[$key][$index]);
        }
        return !empty($this->request[$key]);
    }
    
    /**
     * Returns true if the given key is set in the request data
     * @param string $key
     * @return boolean
     */
    public function exists($key) {
        return isset($this->request[$key]);
    }
    
    
    
    /**
     * Checks if all the given keys are not empty or set
     * @param string[] $emptyKeys
     * @param string[] $setKeys   Optional.
     * @return boolean
     */
    public function isEmpty(array $emptyKeys, array $setKeys = []) {
        foreach ($emptyKeys as $field) {
            if (!$this->has($field)) {
                return true;
            }
        }
        foreach ($setKeys as $field) {
            if (!$this->exists($field)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns true if the array is empty
     * @param string $key
     * @return boolean
     */
    public function isEmptyArray($key) {
        $array = $this->getArray($key);
        return empty($array);
    }
    
    
    
    /**
     * Returns true if the given value is alpha-numeric
     * @param string  $key
     * @param boolean $withDashes Optional.
     * @param integer $length     Optional.
     * @return boolean
     */
    public function isAlphaNum($key, $withDashes = false, $length = null) {
        return Utils::isAlphaNum($this->get($key, ""), $withDashes, $length);
    }
    
    /**
     * Returns true if the given value is a number and greater and/or equal to cero
     * @param string  $key
     * @param integer $min  Optional.
     * @param integer $max  Optional.
     * @param integer $mult Optional.
     * @return boolean
     */
    public function isNumeric($key, $min = 1, $max = null, $mult = 1) {
        return Utils::isNumeric($this->getInt($key) * $mult, $min, $max);
    }
    
    /**
     * Returns true if the given price is valid
     * @param string  $key
     * @param integer $min Optional.
     * @param integer $max Optional.
     * @return boolean
     */
    public function isValidPrice($key, $min = 1, $max = null) {
        return Utils::isNumeric($this->getInt($key) * 100, $min, $max);
    }
    
    /**
     * Returns true if the given email is valid
     * @param string $key
     * @return boolean
     */
    public function isValidEmail($key) {
        return Utils::isValidEmail($this->get($key));
    }
    
    /**
     * Returns true if the given password is valid
     * @param string $key
     * @return boolean
     */
    public function isValidPassword($key) {
        return Utils::isValidPassword($this->get($key));
    }
    
    /**
     * Returns true if the given domain is valid
     * @param string $key
     * @return boolean
     */
    public function isValidDomain($key) {
        return Utils::isValidDomain($this->toDomain($key));
    }
    
    /**
     * Returns true if the given username is valid
     * @param string $key
     * @return boolean
     */
    public function isValidUsername($key) {
        return Utils::isValidUsername($this->get($key));
    }
    
    /**
     * Returns true if the given name is valid
     * @param string $key
     * @return boolean
     */
    public function isValidName($key) {
        return Utils::isValidName($this->get($key));
    }
    
    /**
     * Returns true if the given CUIT is valid
     * @param string $key
     * @return boolean
     */
    public function isValidCUIT($key) {
        return Utils::isValidCUIT($this->get($key));
    }

    /**
     * Returns true if the given DNI is valid
     * @param string $key
     * @return boolean
     */
    public function isValidDNI($key) {
        return Utils::isValidDNI($this->get($key));
    }

    /**
     * Returns true if the given Position is valid
     * @param string $key
     * @return boolean
     */
    public function isValidPosition($key) {
        return !$this->has($key) || $this->isNumeric($key, 0);
    }
    


    /**
     * Returns true if the given date is Valid
     * @param string $key
     * @return boolean
     */
    public function isValidDate($key) {
        return DateTime::isValidDate($this->get($key));
    }
    
    /**
     * Returns true if the given hour is Valid
     * @param string   $key
     * @param string[] $minutes Optional.
     * @return boolean
     */
    public function isValidHour($key, array $minutes = null) {
        return DateTime::isValidHour($this->get($key), $minutes);
    }
    
    /**
     * Returns true if the given dates are a valid period
     * @param string $fromKey
     * @param string $toKey
     * @return boolean
     */
    public function isValidPeriod($fromKey, $toKey) {
        return DateTime::isValidPeriod($this->get($fromKey), $this->get($toKey));
    }
    
    /**
     * Returns true if the given hours are a valid period
     * @param string $fromKey
     * @param string $toKey
     * @return boolean
     */
    public function isValidHourPeriod($fromKey, $toKey) {
        return DateTime::isValidHourPeriod($this->get($fromKey), $this->get($toKey));
    }
    
    /**
     * Returns true if the given hours are a valid period
     * @param string $fromTimeDateKey
     * @param string $fromTimeHourKey
     * @param string $toTimeDateKey
     * @param string $toTimeHourKey
     * @return boolean
     */
    public function isValidFullPeriod($fromTimeDateKey, $fromTimeHourKey, $toTimeDateKey, $toTimeHourKey) {
        return DateTime::isValidFullPeriod(
            $this->get($fromTimeDateKey),
            $this->get($fromTimeHourKey),
            $this->get($toTimeDateKey),
            $this->get($toTimeHourKey)
        );
    }

    /**
     * Returns true if the given week day is valid
     * @param integer $weekDay
     * @return boolean
     */
    public static function isValidWeekDay($weekDay) {
        return DateTime::isValidWeekDay($weekDay);
    }
    

    
    /**
     * Returns the request as an array
     * @return array
     */
    public function toArray() {
        return $this->request;
    }
    
    /**
     * Returns the given array encoded as JSON
     * @param string $key
     * @return string
     */
    public function toJSON($key) {
        return JSON::encode($this->get($key, []));
    }
    
    /**
     * Converts the request data on the given key to binary
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function toBinary($key, $default = 1) {
        return $this->has($key) ? $default : 0;
    }
    
    /**
     * Returns the given number as an integer using the given decimals
     * @param string  $key
     * @param integer $decimals
     * @return integer
     */
    public function toInt($key, $decimals) {
        return Utils::toInt($this->getInt($key), $decimals);
    }
    
    /**
     * Returns the given price in Cents
     * @param string  $key
     * @param integer $index Optional.
     * @return integer
     */
    public function toCents($key, $index = null) {
        $value = $index !== null ? $this->getFromArray($key, $index, 0) : $this->getInt($key);
        return Utils::toCents($value);
    }

    /**
     * Removes spaces and dashes in the CUIT
     * @param string $key
     * @return string
     */
    public function cuitToNumber($key) {
        return Utils::cuitToNumber($this->get($key));
    }

    /**
     * Removes spaces and dashes in the DNI
     * @param string $key
     * @return string
     */
    public function dniToNumber($key) {
        return Utils::dniToNumber($this->get($key));
    }
    
    /**
     * Parsea a Domain to try and return something like "domain.com"
     * @param string $key
     * @return string
     */
    public function toDomain($key) {
        return Utils::parseDomain($this->get($key));
    }
    


    /**
     * Returns the given strings as a time
     * @param string $dateKey
     * @param string $hourKey
     * @return integer
     */
    public function toTimeHour($dateKey, $hourKey) {
        return DateTime::toTimeHour($this->get($dateKey), $this->get($hourKey));
    }
    
    /**
     * Returns the given string as a time of the start of the day
     * @param string $key
     * @return integer
     */
    public function toDayStart($key) {
        return DateTime::toDayStart($this->get($key));
    }
    
    /**
     * Returns the given string as a time of the end of the day
     * @param string $key
     * @return integer
     */
    public function toDayEnd($key) {
        return DateTime::toDayEnd($this->get($key));
    }
    


    /**
     * Returns the Array keys from the given array
     * @param string $key
     * @return array
     */
    public function getKeys($key) {
        return array_keys($this->get($key, []));
    }
    
    
    
    /**
     * Returns the request file at the given key
     * @param string $key
     * @return array
     */
    public function getFile($key) {
        return isset($this->files[$key]) ? $this->files[$key] : null;
    }
    
    /**
     * Returns the request file name at the given key
     * @param string $key
     * @return array
     */
    public function getFileName($key) {
        if ($this->hasFile($key)) {
            return $this->files[$key]["name"];
        }
        return "";
    }
    
    /**
     * Returns the request file temporal name at the given key
     * @param string $key
     * @return array
     */
    public function getTmpName($key) {
        if ($this->hasFile($key)) {
            return $this->files[$key]["tmp_name"];
        }
        return "";
    }
    
    /**
     * Returns true if the given key exists in the files data
     * @param string $key
     * @return boolean
     */
    public function hasFile($key) {
         return !empty($this->files[$key]) && !empty($this->files[$key]["name"]);
    }
    
    /**
     * Returns true if there was a size error in the upload
     * @param string $key
     * @return boolean
     */
    public function hasSizeError($key) {
        if ($this->hasFile($key)) {
            return !empty($this->files[$key]["error"]) && $this->files[$key]["error"] == UPLOAD_ERR_INI_SIZE;
        }
        return true;
    }
    
    /**
     * Returns true if the file at the given key has the given extension
     * @param string          $key
     * @param string|string[] $extensions
     * @return boolean
     */
    public function hasExtension($key, $extensions) {
        if ($this->hasFile($key)) {
            return File::hasExtension($_FILES[$key]["name"], $extensions);
        }
        return false;
    }
    
    /**
     * Returns true if the file at the given key is a valid image
     * @param string $key
     * @return boolean
     */
    public function isValidImage($key) {
        if ($this->hasFile($key)) {
            return Image::isValidType($_FILES[$key]["tmp_name"]);
        }
        return FileType::isImage($this->get($key));
    }
    
    
    
    /**
     * Implements the Array Access Interface
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key) {
        return $this->get($key);
    }
    
    /**
     * Implements the Array Access Interface
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function offsetSet($key, $value) {
        $this->set($key, $value);
    }
    
    /**
     * Implements the Array Access Interface
     * @param string $key
     * @return boolean
     */
    public function offsetExists($key) {
        return array_key_exists($key, $this->request);
    }
    
    /**
     * Implements the Array Access Interface
     * @param string $key
     * @return void
     */
    public function offsetUnset($key) {
        unset($this->request[$key]);
    }
}
