<?php
namespace Framework;

use Framework\File\File;
use Framework\File\FileType;
use Framework\File\Image;
use Framework\Utils\Arrays;
use Framework\Utils\CSV;
use Framework\Utils\DateTime;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;
use Framework\Utils\Utils;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;
use JsonSerializable;
use CURLFile;
use AllowDynamicProperties;

/**
 * The Request Wrapper
 */
#[AllowDynamicProperties]
class Request implements IteratorAggregate, JsonSerializable {

    /** @var ArrayAccess|array<string,mixed> */
    private ArrayAccess|array $request;

    /** @var ArrayAccess|array<string,mixed> */
    private ArrayAccess|array $files;


    /**
     * Creates a new Request instance
     * @param ArrayAccess|array<string,mixed> $request Optional.
     * @param ArrayAccess|array<string,mixed> $files   Optional.
     */
    public function __construct(ArrayAccess|array $request = [], ArrayAccess|array $files = []) {
        $this->request = $request;
        $this->files   = $files;
    }



    /**
     * Returns the request data at the given key or the default
     * @param string       $key
     * @param mixed|string $default Optional.
     * @return mixed
     */
    public function get(string $key, mixed $default = ""): mixed {
        return isset($this->request[$key]) ? $this->request[$key] : $default;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string $key
     * @param mixed  $default Optional.
     * @return mixed
     */
    public function getOr(string $key, mixed $default): mixed {
        return !empty($this->request[$key]) ? $this->request[$key] : $default;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string  $key
     * @param integer $default Optional.
     * @return integer
     */
    public function getInt(string $key, int $default = 0): int {
        return isset($this->request[$key]) ? (int)$this->request[$key] : $default;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string  $key
     * @param integer $default
     * @return integer
     */
    public function getIntOr(string $key, int $default): int {
        return !empty($this->request[$key]) ? (int)$this->request[$key] : $default;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string $key
     * @param float  $default Optional.
     * @return float
     */
    public function getFloat(string $key, float $default = 0): float {
        return isset($this->request[$key]) ? (float)$this->request[$key] : $default;
    }

    /**
     * Returns the request data at the given key as a trimmed string or the default
     * @param string $key
     * @param string $default Optional.
     * @return string
     */
    public function getString(string $key, string $default = ""): string {
        return isset($this->request[$key]) ? trim((string)$this->request[$key]) : $default;
    }

    /**
     * Returns the request data at the given key as a trimmed string or the default
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getStringOr(string $key, string $default): string {
        return !empty($this->request[$key]) ? trim((string)$this->request[$key]) : $default;
    }

    /**
     * Returns the request data at the given key as an array and removing the empty entries
     * @param string $key
     * @return mixed[]
     */
    public function getArray(string $key): array {
        return Arrays::removeEmpty($this->get($key, []));
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
     * Returns the request data at the given key from a JSON Multi Array
     * @param string $key
     * @return array<string|integer,array<string,mixed>>
     */
    public function getJSONMulti(string $key): array {
        return JSON::decodeAsArray($this->get($key, "[]"));
    }

    /**
     * Returns the request data at the given key from a JSON Object
     * @param string $key
     * @return object[]
     */
    public function getJSONObject(string $key): array {
        return JSON::decodeAsObject($this->get($key, "[]"));
    }

    /**
     * Returns the request data at the given key as CSV
     * @param string $key
     * @return string[]
     */
    public function getStrings(string $key): array {
        $value  = $this->get($key, "");
        $result = [];
        if (JSON::isValid($value)) {
            $result = JSON::decodeAsArray($value);
        } else {
            $result = Strings::split($value, ",");
        }
        return Arrays::toStrings($result, withoutEmpty: true);
    }

    /**
     * Returns the request data at the given key as CSV
     * @param string $key
     * @return int[]
     */
    public function getInts(string $key): array {
        $value  = $this->get($key, "");
        $result = [];
        if (JSON::isValid($value)) {
            $result = JSON::decodeAsArray($value);
        } else {
            $result = Strings::split($value, ",");
        }
        return Arrays::toInts($result, withoutEmpty: true);
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
     * Sets the data of the give object
     * @param mixed[] $object
     * @return Request
     */
    public function setObject(array $object): Request {
        foreach ($object as $key => $value) {
            $this->request[$key] = $value;
        }
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
            return !empty($this->request);
        }
        if (Arrays::isArray($key)) {
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
     * @param string[]|string $key
     * @return boolean
     */
    public function exists(array|string $key): bool {
        if (Arrays::isArray($key)) {
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
        if (empty($this->request[$key])) {
            return false;
        }
        $value = $this->request[$key];
        return $value === "true" || $value == 1;
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
        return empty($array);
    }



    /**
     * Returns true if the given email is valid
     * @param string $key
     * @return boolean
     */
    public function isValidString(string $key): bool {
        return !empty(trim($this->get($key, "")));
    }

    /**
     * Returns true if the given value is a number and greater and/or equal to cero
     * @param string       $key
     * @param integer|null $min      Optional.
     * @param integer|null $max      Optional.
     * @param integer|null $decimals Optional.
     * @return boolean
     */
    public function isNumeric(string $key, ?int $min = 1, ?int $max = null, ?int $decimals = null): bool {
        return Numbers::isValidFloat($this->getFloat($key), $min, $max, $decimals);
    }

    /**
     * Returns true if the given price is valid
     * @param string       $key
     * @param integer|null $min Optional.
     * @param integer|null $max Optional.
     * @return boolean
     */
    public function isValidPrice(string $key, ?int $min = 1, ?int $max = null): bool {
        return Numbers::isValidPrice($this->getFloat($key), $min, $max);
    }

    /**
     * Returns true if the given value is alpha-numeric
     * @param string       $key
     * @param boolean      $withDashes Optional.
     * @param integer|null $length     Optional.
     * @return boolean
     */
    public function isAlphaNum(string $key, bool $withDashes = false, ?int $length = null): bool {
        return Strings::isAlphaNum($this->get($key, ""), $withDashes, $length);
    }

    /**
     * Returns true if the given slug is valid
     * @param string $key
     * @return boolean
     */
    public function isValidSlug(string $key): bool {
        return Strings::isValidSlug($this->get($key, ""));
    }

    /**
     * Returns true if the given email is valid
     * @param string $key
     * @return boolean
     */
    public function isValidEmail(string $key): bool {
        return Utils::isValidEmail($this->get($key));
    }

    /**
     * Returns true if the given password is valid
     * @param string  $key
     * @param string  $checkSets Optional.
     * @param integer $minLength Optional.
     * @return boolean
     */
    public function isValidPassword(string $key, string $checkSets = "ad", int $minLength = 6): bool {
        return Utils::isValidPassword($this->get($key), $checkSets, $minLength);
    }

    /**
     * Returns true if the given domain is valid
     * @param string $key
     * @return boolean
     */
    public function isValidDomain(string $key): bool {
        return Utils::isValidDomain($this->toDomain($key));
    }

    /**
     * Returns true if the given username is valid
     * @param string $key
     * @return boolean
     */
    public function isValidUsername(string $key): bool {
        return Utils::isValidUsername($this->get($key));
    }

    /**
     * Returns true if the given name is valid
     * @param string $key
     * @return boolean
     */
    public function isValidName(string $key): bool {
        return Utils::isValidName($this->get($key));
    }

    /**
     * Returns true if the given color is valid
     * @param string $key
     * @return boolean
     */
    public function isValidColor(string $key): bool {
        return Utils::isValidColor($this->get($key));
    }

    /**
     * Returns true if the given CUIT is valid
     * @param string $key
     * @return boolean
     */
    public function isValidCUIT(string $key): bool {
        return Utils::isValidCUIT($this->get($key));
    }

    /**
     * Returns true if the given DNI is valid
     * @param string $key
     * @return boolean
     */
    public function isValidDNI(string $key): bool {
        return Utils::isValidDNI($this->get($key));
    }

    /**
     * Returns true if the given Phone is valid
     * @param string $key
     * @return boolean
     */
    public function isValidPhone(string $key): bool {
        return Utils::isValidPhone($this->get($key));
    }

    /**
     * Returns true if the given Url is valid
     * @param string $key
     * @return boolean
     */
    public function isValidUrl(string $key): bool {
        return Utils::isValidUrl($this->get($key));
    }

    /**
     * Returns true if the given Position is valid
     * @param string $key
     * @return boolean
     */
    public function isValidPosition(string $key): bool {
        return !$this->has($key) || $this->isNumeric($key, 0);
    }



    /**
     * Returns true if the given date is Valid
     * @param string $key
     * @return boolean
     */
    public function isValidDate(string $key): bool {
        return DateTime::isValidDate($this->get($key));
    }

    /**
     * Returns true if the given hour is Valid
     * @param string|null    $key
     * @param integer[]|null $minutes Optional.
     * @param integer        $minHour Optional.
     * @param integer        $maxHour Optional.
     * @return boolean
     */
    public function isValidHour(?string $key, ?array $minutes = null, int $minHour = 0, int $maxHour = 23): bool {
        return DateTime::isValidHour($this->get($key), $minutes, $minHour, $maxHour);
    }

    /**
     * Returns true if the given dates are a valid period
     * @param string $fromKey
     * @param string $toKey
     * @return boolean
     */
    public function isValidPeriod(string $fromKey, string $toKey): bool {
        if (!$this->isEmpty([ $fromKey, $toKey ])) {
            return DateTime::isValidPeriod($this->get($fromKey), $this->get($toKey));
        }
        return true;
    }

    /**
     * Returns true if the given hours are a valid period
     * @param string $fromKey
     * @param string $toKey
     * @return boolean
     */
    public function isValidHourPeriod(string $fromKey, string $toKey): bool {
        if (!$this->isEmpty([ $fromKey, $toKey ])) {
            return DateTime::isValidHourPeriod($this->get($fromKey), $this->get($toKey));
        }
        return true;
    }

    /**
     * Returns true if the given hours are a valid period
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
                $this->get($fromDateKey),
                $this->get($fromHourKey),
                $this->get($toDateKey),
                $this->get($toHourKey)
            );
        }
        return true;
    }

    /**
     * Returns true if the given week day is valid
     * @param string  $key
     * @param boolean $startMonday Optional.
     * @return boolean
     */
    public function isValidWeekDay(string $key, bool $startMonday = false): bool {
        return DateTime::isValidWeekDay($this->getInt($key), $startMonday);
    }

    /**
     * Returns true if the given date is in the future
     * @param string $key
     * @param string $type Optional.
     * @return boolean
     */
    public function isFutureDate(string $key, string $type = "middle"): bool {
        return DateTime::isFutureDate($this->get($key), $type);
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
        $value = (float)$this->get($key);
        return Numbers::toInt($value, $decimals);
    }

    /**
     * Returns the given price in Cents
     * @param string $key
     * @return integer
     */
    public function toCents(string $key): int {
        $value = (float)$this->get($key);
        return Numbers::toCents($value);
    }

    /**
     * Returns the given array encoded as JSON
     * @param string $key
     * @return string
     */
    public function toJSON(string $key): string {
        $value = $this->get($key);
        if (empty($value)) {
            $value = [];
        } elseif (JSON::isValid($value)) {
            $value = JSON::decodeAsArray($value);
        } elseif (Strings::isString($value)) {
            $value = Strings::split($value, ",");
        }
        return JSON::encode($value);
    }

    /**
     * Returns the given array encoded as JSON
     * @param string $key
     * @return string
     */
    public function toCSV(string $key): string {
        return CSV::encode($this->get($key));
    }

    /**
     * Removes spaces and dots in the CUIT
     * @param string $key
     * @return string
     */
    public function cuitToNumber(string $key): string {
        return Utils::cuitToNumber($this->get($key));
    }

    /**
     * Removes spaces and dots in the DNI
     * @param string $key
     * @return string
     */
    public function dniToNumber(string $key): string {
        return Utils::dniToNumber($this->get($key));
    }

    /**
     * Removes spaces, dashes and parenthesis in the Phone
     * @param string $key
     * @return string
     */
    public function phoneToNumber(string $key): string {
        return Utils::phoneToNumber($this->get($key));
    }

    /**
     * Parses a Domain to try and return something like "domain.com"
     * @param string $key
     * @return string
     */
    public function toDomain(string $key): string {
        return Utils::parseDomain($this->get($key));
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
        return DateTime::toTimeHour($this->get($dateKey), $this->get($hourKey), $useTimezone);
    }

    /**
     * Returns the given string as a time
     * @param string  $key
     * @param string  $type        Optional.
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function toDay(string $key, string $type = "start", bool $useTimezone = true): int {
        return DateTime::toDay($this->get($key), $type, $useTimezone);
    }

    /**
     * Returns the given string as a time of the start of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function toDayStart(string $key, bool $useTimezone = true): int {
        return DateTime::toDayStart($this->get($key), $useTimezone);
    }

    /**
     * Returns the given string as a time of the middle of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function toDayMiddle(string $key, bool $useTimezone = true): int {
        return DateTime::toDayMiddle($this->get($key), $useTimezone);
    }

    /**
     * Returns the given string as a time of the end of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function toDayEnd(string $key, bool $useTimezone = true): int {
        return DateTime::toDayEnd($this->get($key), $useTimezone);
    }

    /**
     * Returns the given integer as a time of the start of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function getDayStart(string $key, bool $useTimezone = true): int {
        return DateTime::getDayStart($this->getInt($key), $useTimezone);
    }

    /**
     * Returns the given integer as a time of the end of the day
     * @param string  $key
     * @param boolean $useTimezone Optional.
     * @return integer
     */
    public function getDayEnd(string $key, bool $useTimezone = true): int {
        return DateTime::getDayEnd($this->getInt($key), $useTimezone);
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
        if ($this->hasFile($key)) {
            return $this->files[$key]["name"];
        }
        return "";
    }

    /**
     * Returns the request file temporal name at the given key
     * @param string $key
     * @return string
     */
    public function getTmpName(string $key): string {
        if ($this->hasFile($key)) {
            return $this->files[$key]["tmp_name"];
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
                $this->files[$key]["tmp_name"],
                $this->files[$key]["type"],
                $this->files[$key]["name"],
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
        return !empty($this->files[$key]) && !empty($this->files[$key]["name"]);
    }

    /**
     * Returns true if there was a size error in the upload
     * @param string $key
     * @return boolean
     */
    public function hasSizeError(string $key): bool {
        if ($this->hasFile($key)) {
            return !empty($this->files[$key]["error"]) && $this->files[$key]["error"] == UPLOAD_ERR_INI_SIZE;
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
        if ($this->hasFile($key)) {
            return File::hasExtension($_FILES[$key]["name"], $extensions);
        }
        return File::hasExtension($this->get($key), $extensions);
    }

    /**
     * Returns true if the file at the given key is a valid image
     * @param string $key
     * @return boolean
     */
    public function isValidImage(string $key): bool {
        if ($this->hasFile($key)) {
            return Image::isValidType($_FILES[$key]["tmp_name"]);
        }
        return FileType::isImage($this->get($key));
    }



    /**
     * Returns the Request data
     * @return ArrayAccess|array<string,mixed>
     */
    public function toArray(): ArrayAccess|array {
        return $this->request;
    }

    /**
     * Return the Data for var_dump
     * @return array
     */
    public function __debugInfo(): array {
        return (array)$this->request;
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
