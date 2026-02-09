<?php
namespace Framework\Database\Type;

use Framework\Utils\Strings;
use Framework\Request;

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
            if (Strings::isEqual($case->name, $value)) {
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
     * @param string[]|self[]|self|null $values
     * @return self[]
     */
    public static function fromList(array|self|null $values): array {
        $result = [];
        if ($values instanceof self) {
            $result[] = $values;
        } elseif (is_array($values)) {
            foreach ($values as $value) {
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
     * Converts the Enum into a string
     * @return string
     */
    public function toString(): string {
        return $this->name;
    }

    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    public function jsonSerialize(): mixed {
        if ($this === self::None) {
            return "";
        }
        return $this->name;
    }
}
