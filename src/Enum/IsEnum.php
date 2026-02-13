<?php
namespace Framework\Enum;

use Framework\Request;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

use BackedEnum;

/**
 * The Enum trait
 */
trait IsEnum {

    /**
     * Creates an Enum from a String
     * @param self|string $value
     * @param self        $default Optional.
     * @return self
     */
    public static function fromValue(self|string $value, self $default = self::None): self {
        if ($value instanceof self) {
            return $value;
        }
        foreach (self::cases() as $case) {
            if (Strings::isEqual($case->toString(), $value)) {
                return $case;
            }
        }
        return $default;
    }

    /**
     * Creates an Enum from a Request
     * @param Request $request
     * @param string  $key
     * @param self    $default Optional.
     * @return self
     */
    public static function fromRequest(Request $request, string $key, self $default = self::None): self {
        $value = $request->getString($key);
        return self::fromValue($value, $default);
    }

    /**
     * Creates a list of Enums from the given Strings
     * @param mixed $value
     * @return self[]
     */
    public static function fromList(mixed $value): array {
        if ($value === null) {
            return [];
        }

        $result = [];
        if ($value instanceof self) {
            $result[] = $value;
        } elseif (is_array($value)) {
            $values = Arrays::toStrings($value);
            foreach ($values as $val) {
                $result[] = self::fromValue($val);
            }
        } else {
            $value = Strings::toString($value);
            if ($value !== "") {
                $result[] = self::fromValue($value);
            }
        }
        return $result;
    }

    /**
     * Checks if the given value is valid
     * @param self|string $value
     * @return bool
     */
    public static function isValid(self|string $value): bool {
        return self::fromValue($value) !== self::None;
    }



    /**
     * Returns all the Enum cases except None
     * @phpstan-return list<self>
     * @return self[]
     */
    public static function getAll(): array {
        $result = [];
        foreach (self::cases() as $case) {
            if ($case !== self::None) {
                $result[] = $case;
            }
        }
        return $result;
    }

    /**
     * Returns all the Enum cases as Strings
     * @phpstan-return list<string>
     * @return string[]
     */
    public static function getNames(): array {
        $result = [];
        foreach (self::cases() as $case) {
            if ($case !== self::None) {
                $result[] = $case->toString();
            }
        }
        return $result;
    }

    /**
     * Checks if the given value is contained in the list of values
     * @param self[] $values
     * @param self   $value
     * @return bool
     */
    public static function contains(array $values, self $value): bool {
        foreach ($values as $item) {
            if ($item === $value) {
                return true;
            }
        }
        return false;
    }



    /**
     * Converts the Enum into a string
     * @return string
     */
    public function toString(): string {
        if ($this === self::None) {
            return "";
        }
        if ($this instanceof BackedEnum) {
            return Strings::toString($this->value);
        }
        return Strings::toString($this->name);
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        return $this->toString();
    }
}
