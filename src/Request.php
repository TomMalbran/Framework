<?php
namespace Framework;

use Framework\File\File;
use Framework\File\FileType;
use Framework\File\Image;
use Framework\Date\DateTime;
use Framework\Date\DateType;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;
use Framework\Utils\Utils;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use JsonSerializable;
use CURLFile;

/**
 * The Request Wrapper
 * @implements IteratorAggregate<string,mixed>
 */
class Request implements IteratorAggregate, JsonSerializable {

    /** @var array<string,mixed> */
    private array $request;

    /** @var array<string,mixed> */
    private array $files;


    /**
     * Creates a new Request instance
     * @param array<string,mixed> $request     Optional.
     * @param boolean             $withRequest Optional.
     * @param boolean             $withFiles   Optional.
     */
    public function __construct(array $request = [], bool $withRequest = false, bool $withFiles = false) {
        if ($withRequest) {
            $this->request = Arrays::toStringMixedMap($_REQUEST);
        } else {
            $this->request = $request;
        }

        if ($withFiles) {
            $this->files = Arrays::toStringMixedMap($_FILES);
        }
    }



    /**
     * Returns the request data at the given key or the default
     * @param string       $key
     * @param mixed|string $default Optional.
     * @return mixed
     */
    public function get(string $key, mixed $default = ""): mixed {
        return $this->exists($key) ? $this->request[$key] : $default;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string $key
     * @param mixed  $default Optional.
     * @return mixed
     */
    public function getOr(string $key, mixed $default): mixed {
        return $this->has($key) ? $this->request[$key] : $default;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getInt(string $key, int $default = 0): int {
        if ($this->exists($key)) {
            return Numbers::toInt($this->request[$key]);
        }
        return $default;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string $key
     * @param float  $default Optional.
     * @return float
     */
    public function getFloat(string $key, float $default = 0): float {
        if ($this->exists($key)) {
            return Numbers::toFloat($this->request[$key]);
        }
        return $default;
    }

    /**
     * Returns the request data at the given key as a trimmed string or the default
     * @param string $key
     * @param string $default Optional.
     * @return string
     */
    public function getString(string $key, string $default = ""): string {
        if ($this->exists($key)) {
            return trim(Strings::toString($this->request[$key]));
        }
        return $default;
    }

    /**
     * Returns the request data at the given key as an array and removing the empty entries
     * @param string $key
     * @return mixed[]
     */
    public function getArray(string $key): array {
        $value = $this->get($key, []);
        if (!is_array($value)) {
            return [];
        }
        return Arrays::removeEmpty($value);
    }

    /**
     * Returns the request data at the given key as a Dictionary
     * @param string $key
     * @return Dictionary
     */
    public function getDictionary(string $key): Dictionary {
        return JSON::decodeAsDictionary($this->get($key, "[]"));
    }

    /**
     * Returns the request data at the given key from a JSON Array
     * @param string $key
     * @return array<string|integer,mixed>
     */
    public function getJSONArray(string $key): array {
        return JSON::decodeAsArray($this->get($key, "[]"));
    }

    /**
     * Returns the request data at the given key as an array of integers
     * @param string  $key
     * @param boolean $withoutEmpty Optional.
     * @return int[]
     */
    public function getInts(string $key, bool $withoutEmpty = true): array {
        $value  = $this->get($key, "");
        $result = [];
        if (JSON::isValid($value)) {
            $result = JSON::decodeAsArray($value);
        } elseif (is_string($value)) {
            $result = Strings::split($value, ",");
        } elseif (is_array($value)) {
            $result = $value;
        }
        return Arrays::toInts($result, withoutEmpty: $withoutEmpty);
    }

    /**
     * Returns the request data at the given key as an array of Strings
     * @param string $key
     * @return string[]
     */
    public function getStrings(string $key): array {
        $value  = $this->get($key, "");
        $result = [];
        if (JSON::isValid($value)) {
            $result = JSON::decodeAsArray($value);
        } elseif (is_string($value)) {
            $result = Strings::split($value, ",");
        } elseif (is_array($value)) {
            $result = $value;
        }
        return Arrays::toStrings($result, withoutEmpty: true);
    }

    /**
     * Returns the request data at the given key as a map of Strings
     * @param string $key
     * @return array<string,string>
     */
    public function getStringsMap(string $key): array {
        $value = $this->getJSONArray($key);
        return Arrays::toStringsMap($value);
    }

    /**
     * Returns the request data at the given key as a map of String-Integer
     * @param string $key
     * @return array<string,integer>
     */
    public function getStringIntMap(string $key): array {
        $value = $this->getJSONArray($key);
        return Arrays::toStringIntMap($value);
    }



    /**
     * Sets the given key on the request data with the given value
     * @param string       $key
     * @param mixed|string $value Optional.
     * @return Request
     */
    public function set(string $key, mixed $value = ""): Request {
        $this->request[$key] = $value;
        return $this;
    }

    /**
     * Removes the request data at the given key
     * @param string $key
     * @return Request
     */
    public function remove(string $key): Request {
        if ($this->exists($key)) {
            unset($this->request[$key]);
        }
        return $this;
    }



    /**
     * Returns true if the given key exists in the request data
     * @param string[]|string|null $key   Optional.
     * @param integer|null         $index Optional.
     * @return boolean
     */
    public function has(array|string|null $key = null, ?int $index = null): bool {
        if ($key === null) {
            return !Arrays::isEmpty($this->request);
        }
        if (is_array($key)) {
            foreach ($key as $keyID) {
                if (Arrays::isEmpty($this->request, $keyID)) {
                    return false;
                }
            }
            return true;
        }
        if ($index !== null) {
            return (
                isset($this->request[$key]) &&
                is_array($this->request[$key]) &&
                !Arrays::isEmpty($this->request[$key], $index)
            );
        }
        return !Arrays::isEmpty($this->request, $key);
    }

    /**
     * Returns true if the given key is set in the request data
     * @param string[]|string $key
     * @return boolean
     */
    public function exists(array|string $key): bool {
        if (is_array($key)) {
            foreach ($key as $keyID) {
                if (!isset($this->request[$keyID])) {
                    return false;
                }
            }
            return true;
        }
        return isset($this->request[$key]);
    }

    /**
     * Returns true if the given key exists and is active
     * @param string $key
     * @return boolean
     */
    public function isActive(string $key): bool {
        if (!$this->has($key)) {
            return false;
        }
        $value = $this->request[$key];
        return $value === "true" || $value === 1;
    }



    /**
     * Checks if all the given keys are not empty or set
     * @param string[] $emptyKeys
     * @param string[] $setKeys   Optional.
     * @return boolean
     */
    public function isEmpty(array $emptyKeys, array $setKeys = []): bool {
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
    public function isEmptyArray(string $key): bool {
        $array = $this->getArray($key);
        return Arrays::isEmpty($array);
    }



    /**
     * Returns true if the value at the given key is a valid String
     * @param string $key
     * @return boolean
     */
    public function isValidString(string $key): bool {
        $value = $this->getString($key);
        return $value !== "";
    }

    /**
     * Returns true if the value at the given key is a valid Number
     * @param string $key
     * @return boolean
     */
    public function isValidNumber(string $key): bool {
        $value = $this->getString($key);
        return is_numeric($value);
    }

    /**
     * Returns true if the value at given key is a Number between the min and max
     * @param string       $key
     * @param integer|null $min      Optional.
     * @param integer|null $max      Optional.
     * @param integer|null $decimals Optional.
     * @return boolean
     */
    public function isNumeric(string $key, ?int $min = 1, ?int $max = null, ?int $decimals = null): bool {
        $value = $this->getFloat($key);
        return Numbers::isValidFloat($value, $min, $max, $decimals);
    }

    /**
     * Returns true if the value at the given key is a valid Price
     * @param string       $key
     * @param integer|null $min Optional.
     * @param integer|null $max Optional.
     * @return boolean
     */
    public function isValidPrice(string $key, ?int $min = 1, ?int $max = null): bool {
        $value = $this->getFloat($key);
        return Numbers::isValidPrice($value, $min, $max);
    }

    /**
     * Returns true if the value at the given key is a Alpha-Numeric
     * @param string       $key
     * @param boolean      $withDashes Optional.
     * @param integer|null $length     Optional.
     * @return boolean
     */
    public function isAlphaNum(string $key, bool $withDashes = false, ?int $length = null): bool {
        $value = $this->getString($key);
        return Strings::isAlphaNum($value, $withDashes, $length);
    }

    /**
     * Returns true if the value at the given key is a valid Slug
     * @param string $key
     * @return boolean
     */
    public function isValidSlug(string $key): bool {
        $value = $this->getString($key);
        return Strings::isValidSlug($value);
    }

    /**
     * Returns true if the value at the given key is a valid Email
     * @param string $key
     * @return boolean
     */
    public function isValidEmail(string $key): bool {
        $value = $this->getString($key);
        return Utils::isValidEmail($value);
    }

    /**
     * Returns true if the value at the given key is a valid Password
     * @param string  $key
     * @param string  $checkSets Optional.
     * @param integer $minLength Optional.
     * @return boolean
     */
    public function isValidPassword(string $key, string $checkSets = "ad", int $minLength = 6): bool {
        $value = $this->getString($key);
        return Utils::isValidPassword($value, $checkSets, $minLength);
    }

    /**
     * Returns true if the value at the given key is a valid Domain
     * @param string $key
     * @return boolean
     */
    public function isValidDomain(string $key): bool {
        return Utils::isValidDomain($this->toDomain($key));
    }

    /**
     * Returns true if the value at the given key is a valid Username
     * @param string $key
     * @return boolean
     */
    public function isValidUsername(string $key): bool {
        $value = $this->getString($key);
        return Utils::isValidUsername($value);
    }

    /**
     * Returns true if the value at the given key is a valid Color
     * @param string $key
     * @return boolean
     */
    public function isValidColor(string $key): bool {
        $value = $this->getString($key);
        return Utils::isValidColor($value);
    }

    /**
     * Returns true if the value at the given key is a valid CUIT
     * @param string $key
     * @return boolean
     */
    public function isValidCUIT(string $key): bool {
        $value = $this->getString($key);
        return Utils::isValidCUIT($value);
    }

    /**
     * Returns true if the value at the given key is a valid DNI
     * @param string $key
     * @return boolean
     */
    public function isValidDNI(string $key): bool {
        $value = $this->getString($key);
        return Utils::isValidDNI($value);
    }

    /**
     * Returns true if the value at the given key is a valid Phone
     * @param string $key
     * @return boolean
     */
    public function isValidPhone(string $key): bool {
        $value = $this->getString($key);
        return Utils::isValidPhone($value);
    }

    /**
     * Returns true if the value at the given key is a valid Url
     * @param string $key
     * @return boolean
     */
    public function isValidUrl(string $key): bool {
        $value = $this->getString($key);
        return Utils::isValidUrl($value);
    }

    /**
     * Returns true if the value at the given key is a valid Position
     * @param string $key
     * @return boolean
     */
    public function isValidPosition(string $key): bool {
        return !$this->has($key) || $this->isNumeric($key, 0);
    }



    /**
     * Returns true if the value at the given key is a valid Date
     * @param string $key
     * @return boolean
     */
    public function isValidDate(string $key): bool {
        $value = $this->getString($key);
        return DateTime::isValidDate($value);
    }

    /**
     * Returns true if the value at the given key is a valid Hour
     * @param string         $key
     * @param integer[]|null $minutes Optional.
     * @param integer        $minHour Optional.
     * @param integer        $maxHour Optional.
     * @return boolean
     */
    public function isValidHour(string $key, ?array $minutes = null, int $minHour = 0, int $maxHour = 23): bool {
        $value = $this->getString($key);
        return DateTime::isValidHour($value, $minutes, $minHour, $maxHour);
    }

    /**
     * Returns true if the dates at the given keys are a valid Period
     * @param string $fromKey
     * @param string $toKey
     * @return boolean
     */
    public function isValidPeriod(string $fromKey, string $toKey): bool {
        if (!$this->isEmpty([ $fromKey, $toKey ])) {
            return DateTime::isValidPeriod($this->getString($fromKey), $this->getString($toKey));
        }
        return true;
    }

    /**
     * Returns true if the hours at the given keys are a valid Period
     * @param string $fromKey
     * @param string $toKey
     * @return boolean
     */
    public function isValidHourPeriod(string $fromKey, string $toKey): bool {
        if (!$this->isEmpty([ $fromKey, $toKey ])) {
            return DateTime::isValidHourPeriod($this->getString($fromKey), $this->getString($toKey));
        }
        return true;
    }

    /**
     * Returns true if the dates and hours at the given keys are a valid Period
     * @param string $fromDateKey
     * @param string $fromHourKey
     * @param string $toDateKey
     * @param string $toHourKey
     * @return boolean
     */
    public function isValidFullPeriod(
        string $fromDateKey,
        string $fromHourKey,
        string $toDateKey,
        string $toHourKey
    ): bool {
        if (!$this->isEmpty([ $fromDateKey, $fromHourKey, $toDateKey, $toHourKey ])) {
            return DateTime::isValidFullPeriod(
                $this->getString($fromDateKey),
                $this->getString($fromHourKey),
                $this->getString($toDateKey),
                $this->getString($toHourKey)
            );
        }
        return true;
    }

    /**
     * Returns true if the week day at the given keys are a valid Period
     * @param string  $key
     * @param boolean $startMonday Optional.
     * @return boolean
     */
    public function isValidWeekDay(string $key, bool $startMonday = false): bool {
        $value = $this->getInt($key);
        return DateTime::isValidWeekDay($value, $startMonday);
    }

    /**
     * Returns true if the date at the given key is in the Future
     * @param string   $key
     * @param DateType $dateType Optional.
     * @return boolean
     */
    public function isFutureDate(string $key, DateType $dateType = DateType::Middle): bool {
        $value = $this->getString($key);
        return DateTime::isFutureDate($value, $dateType);
    }



    /**
     * Converts the request data on the given key to binary
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function toBinary(string $key, int $default = 1): int {
        if (!$this->has($key)) {
            return 0;
        }
        $value = $this->get($key);
        return $value === true || $value === 1 || $value === "true" || $value === "1" ? $default : 0;
    }

    /**
     * Returns the given number as an integer using the given decimals
     * @param string  $key
     * @param integer $decimals
     * @return integer
     */
    public function toInt(string $key, int $decimals): int {
        $value = $this->getFloat($key);
        return Numbers::toInt($value, $decimals);
    }

    /**
     * Returns the given price in Cents
     * @param string $key
     * @return integer
     */
    public function toCents(string $key): int {
        $value = $this->getFloat($key);
        return Numbers::toCents($value);
    }

    /**
     * Returns the given array encoded as JSON
     * @param string $key
     * @return string
     */
    public function toJSON(string $key): string {
        $value = $this->get($key);
        if (Arrays::isEmpty($value)) {
            $value = [];
        } elseif (JSON::isValid($value)) {
            $value = JSON::decodeAsArray($value);
        } elseif (is_string($value)) {
            $value = Strings::split($value, ",");
        }
        return JSON::encode($value);
    }

    /**
     * Removes spaces and dots in the CUIT
     * @param string $key
     * @return string
     */
    public function cuitToNumber(string $key): string {
        $value = $this->getString($key);
        return Utils::cuitToNumber($value);
    }

    /**
     * Removes spaces and dots in the DNI
     * @param string $key
     * @return string
     */
    public function dniToNumber(string $key): string {
        $value = $this->getString($key);
        return Utils::dniToNumber($value);
    }

    /**
     * Removes spaces, dashes and parenthesis in the Phone
     * @param string $key
     * @return string
     */
    public function phoneToNumber(string $key): string {
        $value = $this->getString($key);
        return Utils::phoneToNumber($value);
    }

    /**
     * Parses a Domain to try and return something like "domain.com"
     * @param string $key
     * @return string
     */
    public function toDomain(string $key): string {
        $value = $this->getString($key);
        return Utils::parseDomain($value);
    }



    /**
     * Returns the given strings as a time
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @param boolean $skipEmpty   Optional.
     * @return integer
     */
    public function toTime(string $key, bool $useTimezone = true, bool $skipEmpty = false): int {
        if ($skipEmpty && !$this->has($key)) {
            return 0;
        }
        return DateTime::toTime($this->get($key), $useTimezone);
    }

    /**
     * Returns the given strings as a time
     * @param string  $dateKey
     * @param string  $hourKey
     * @param boolean $useTimezone Optional.
     * @param boolean $skipEmpty   Optional.
     * @return integer
     */
    public function toTimeHour(string $dateKey, string $hourKey, bool $useTimezone = true, bool $skipEmpty = false): int {
        if ($skipEmpty && (!$this->has($dateKey) || !$this->has($hourKey))) {
            return 0;
        }
        return DateTime::toTimeHour($this->getString($dateKey), $this->getString($hourKey), $useTimezone);
    }

    /**
     * Returns the given string as a time
     * @param string   $key
     * @param DateType $dateType    Optional.
     * @param boolean  $useTimezone Optional.
     * @return integer
     */
    public function toDay(string $key, DateType $dateType = DateType::Start, bool $useTimezone = true): int {
        $value = $this->getString($key);
        return DateTime::toDay($value, $dateType, $useTimezone);
    }

    /**
     * Returns the given string as a time of the start of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function toDayStart(string $key, bool $useTimezone = true): int {
        $value = $this->getString($key);
        return DateTime::toDayStart($value, $useTimezone);
    }

    /**
     * Returns the given string as a time of the middle of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function toDayMiddle(string $key, bool $useTimezone = true): int {
        $value = $this->getString($key);
        return DateTime::toDayMiddle($value, $useTimezone);
    }

    /**
     * Returns the given string as a time of the end of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function toDayEnd(string $key, bool $useTimezone = true): int {
        $value = $this->getString($key);
        return DateTime::toDayEnd($value, $useTimezone);
    }

    /**
     * Returns the given integer as a time of the start of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function getDayStart(string $key, bool $useTimezone = true): int {
        $value = $this->getInt($key);
        return DateTime::getDayStart($value, 0, $useTimezone);
    }

    /**
     * Returns the given integer as a time of the end of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function getDayEnd(string $key, bool $useTimezone = true): int {
        $value = $this->getInt($key);
        return DateTime::getDayEnd($value, 0, $useTimezone);
    }



    /**
     * Returns the request file at the given key
     * @param string $key
     * @return mixed
     */
    public function getFile(string $key): mixed {
        return isset($this->files[$key]) ? $this->files[$key] : null;
    }

    /**
     * Returns the request file name at the given key
     * @param string $key
     * @return string
     */
    public function getFileName(string $key): string {
        if ($this->hasFile($key) && is_array($this->files[$key])) {
            return Strings::toString($this->files[$key]["name"]);
        }
        return "";
    }

    /**
     * Returns the request file type at the given key
     * @param string $key
     * @return string
     */
    public function getFileType(string $key): string {
        if ($this->hasFile($key) && is_array($this->files[$key])) {
            return Strings::toString($this->files[$key]["type"]);
        }
        return "";
    }

    /**
     * Returns the request file temporal name at the given key
     * @param string $key
     * @return string
     */
    public function getTmpName(string $key): string {
        if ($this->hasFile($key) && is_array($this->files[$key])) {
            return Strings::toString($this->files[$key]["tmp_name"]);
        }
        return "";
    }

    /**
     * Returns the request file at the given key
     * @param string $key
     * @return CURLFile|null
     */
    public function getCurlFile(string $key): ?CURLFile {
        if ($this->hasFile($key)) {
            return curl_file_create(
                $this->getTmpName($key),
                $this->getFileType($key),
                $this->getFileName($key),
            );
        }
        return null;
    }

    /**
     * Returns true if the given key exists in the files data
     * @param string $key
     * @return boolean
     */
    public function hasFile(string $key): bool {
        return (
            isset($this->files[$key]) &&
            is_array($this->files[$key]) &&
            isset($this->files[$key]["name"])
        );
    }

    /**
     * Returns true if there was a size error in the upload
     * @param string $key
     * @return boolean
     */
    public function hasSizeError(string $key): bool {
        if ($this->hasFile($key) && is_array($this->files[$key])) {
            return isset($this->files[$key]["error"]) && $this->files[$key]["error"] === UPLOAD_ERR_INI_SIZE;
        }
        return true;
    }

    /**
     * Returns true if the file at the given key has the given extension
     * @param string          $key
     * @param string[]|string $extensions
     * @return boolean
     */
    public function hasExtension(string $key, array|string $extensions): bool {
        $fileName = $this->getString($key);
        if ($this->hasFile($key)) {
            $fileName = $this->getFileName($key);
        }
        return File::hasExtension($fileName, $extensions);
    }

    /**
     * Returns true if the file at the given key is a valid image
     * @param string $key
     * @return boolean
     */
    public function isValidImage(string $key): bool {
        if ($this->hasFile($key)) {
            return Image::isValidType($this->getTmpName($key));
        }
        return FileType::isImage($this->getString($key));
    }



    /**
     * Returns the Request data
     * @return array<string,mixed>
     */
    public function toArray(): array {
        return $this->request;
    }

    /**
     * Return the Data for var_dump
     * @return array<string,mixed>
     */
    public function __debugInfo(): array {
        return $this->request;
    }

    /**
     * Implements the Iterator Aggregate Interface
     * @return ArrayIterator<string,mixed>
     */
    public function getIterator(): Traversable {
        return new ArrayIterator($this->request);
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        return $this->request;
    }
}
