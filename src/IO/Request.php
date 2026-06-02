<?php
namespace Framework\IO;

use Framework\Date\Date;
use Framework\Date\Type\DateType;
use Framework\Enum\Enum;
use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use JsonSerializable;

/**
 * The Request Wrapper
 * @implements IteratorAggregate<string,mixed>
 */
class Request implements IteratorAggregate, JsonSerializable {

    /** @var array<string,mixed> */
    private array $request;


    /**
     * Creates a new Request instance
     * @param array<string,mixed> $request     Optional.
     * @param bool                $withRequest Optional.
     */
    public function __construct(
        array $request = [],
        bool $withRequest = false,
    ) {
        if ($withRequest) {
            $this->request = Arrays::toStringMixedMap($_REQUEST);
        } else {
            $this->request = $request;
        }
    }



    /**
     * Returns true if the request is empty
     * @return bool
     */
    public function isEmpty(): bool {
        return count($this->request) === 0;
    }

    /**
     * Returns true if the request is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return count($this->request) !== 0;
    }

    /**
     * Returns true if the given key exists in the request
     * @param Enum|string $key
     * @return bool
     */
    public function has(Enum|string $key): bool {
        $key = Strings::toString($key);
        return isset($this->request[$key]);
    }

    /**
     * Returns true if the key exits and has a value in the request
     * @param Enum|string $key Optional.
     * @return bool
     */
    public function hasValue(Enum|string $key): bool {
        $key = Strings::toString($key);
        return !Arrays::isEmpty($this->request, $key);
    }



    /**
     * Sets the given key on the request data with the given value
     * @param Enum|string  $key
     * @param mixed|string $value Optional.
     * @return Request
     */
    public function set(Enum|string $key, mixed $value = ""): Request {
        $key = Strings::toString($key);
        $this->request[$key] = $value;
        return $this;
    }

    /**
     * Removes the request data at the given key
     * @param string $key
     * @return Request
     */
    public function remove(string $key): Request {
        if ($this->has($key)) {
            unset($this->request[$key]);
        }
        return $this;
    }



    /**
     * Returns the request data at the given key or the default
     * @param string       $key
     * @param mixed|string $default Optional.
     * @return mixed
     */
    public function get(string $key, mixed $default = ""): mixed {
        if (isset($this->request[$key])) {
            return $this->request[$key];
        }
        return $default;
    }

    /**
     * Returns the value at the given key as a boolean
     * @param string $key
     * @return bool
     */
    public function getBool(string $key): bool {
        if (isset($this->request[$key])) {
            $value = $this->request[$key];
            return $value === "true" ||
                $value === true ||
                $value === "1" ||
                $value === 1;
        }
        return false;
    }

    /**
     * Returns the request data at the given key or the default
     * @param string $key
     * @param int    $default Optional.
     * @return int
     */
    public function getInt(string $key, int $default = 0): int {
        if (isset($this->request[$key])) {
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
        if (isset($this->request[$key])) {
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
        if (isset($this->request[$key])) {
            return Strings::normalized($this->request[$key]);
        }
        return $default;
    }

    /**
     * Returns the given string as a time at the given moment of the day
     * @param string   $dateKey
     * @param string   $hourKey     Optional.
     * @param DateType $dateType    Optional.
     * @param bool     $useTimeZone Optional.
     * @return Date
     */
    public function getDate(
        string $dateKey,
        string $hourKey = "",
        DateType $dateType = DateType::Start,
        bool $useTimeZone = true,
    ): Date {
        if (!$this->hasValue($dateKey)) {
            return Date::empty();
        }

        $date = Date::create(
            date: $this->getString($dateKey),
            hour: $this->getString($hourKey),
        );
        if ($hourKey === "" || !$this->hasValue($hourKey)) {
            $date = $date->toDayMoment($dateType);
        }
        return $date->toServerTime($useTimeZone);
    }

    /**
     * Returns the request file at the given key
     * @param string $key
     * @return File
     */
    public function getFile(string $key): File {
        return File::fromRequest($key, $this->getString($key));
    }



    /**
     * Returns the request data at the given key as a Dictionary
     * @param string $key
     * @return Dictionary
     */
    public function getDict(string $key): Dictionary {
        return new Dictionary($this->get($key, []));
    }

    /**
     * Returns the request data at the given key from a JSON Array
     * @param string $key
     * @return array<int|string,mixed>
     */
    public function getJSONArray(string $key): array {
        return JSON::decodeAsArray($this->get($key, "[]"));
    }

    /**
     * Returns the request data at the given key as an array of integers
     * @param string $key
     * @param bool   $withoutEmpty Optional.
     * @return list<int>
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
     * @return list<string>
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
     * Returns the Request data
     * @return array<string,mixed>
     */
    public function toArray(): array {
        return $this->request;
    }

    /**
     * Returns the Request data as a Dictionary
     * @return Dictionary
     */
    public function toDictionary(): Dictionary {
        return new Dictionary($this->request);
    }

    /**
     * Implements the Iterator Aggregate Interface
     * @return ArrayIterator<string,mixed>
     */
    #[\Override]
    public function getIterator(): Traversable {
        return new ArrayIterator($this->request);
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->request;
    }
}
