<?php
namespace {{namespace}};

use Framework\Intl\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The {{name}} Status
 */
enum {{statusClass}} implements JsonSerializable {

    case None;

{{#statuses}}
    case {{name}};
{{/statuses}}



    /**
     * Creates a Status from a String
     * @param {{statusClass}}|string $value
     * @param {{statusClass}} $default Optional.
     * @return {{statusClass}}
     */
    public static function fromValue(
        {{statusClass}}|string $value,
        {{statusClass}} $default = self::None,
    ): {{statusClass}} {
        if ($value instanceof {{statusClass}}) {
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
     * Creates a list of Statuses from the given Strings
     * @param string[] $values
     * @return {{statusClass}}[]
     */
    public static function fromList(array $values): array {
        $result = [];
        foreach ($values as $value) {
            $result[] = self::fromValue($value);
        }
        return $result;
    }

    /**
     * Creates a list of Names from the given Statuses
     * @param {{statusClass}}[] $values
     * @return string[]
     */
    public static function toNames(array $values): array {
        $result = [];
        foreach ($values as $value) {
            if ($value !== self::None) {
                $result[] = $value->name;
            }
        }
        return $result;
    }



    /**
     * Returns the Name of the given Status
     * @param {{statusClass}}|string $value
     * @param string $isoCode Optional.
     * @return string
     */
    public static function getName({{statusClass}}|string $value, string $isoCode = ""): string {
        $value = ($value instanceof {{statusClass}}) ? $value->name : $value;
        return NLS::getIndex("SELECT_STATUS", $value, $isoCode);
    }

    /**
     * Returns the Color of the given Status
     * @param {{statusClass}}|string $value
     * @return string
     */
    public static function getColor({{statusClass}}|string $value): string {
        return match (self::fromValue($value)) {
        {{#statuses}}
            self::{{constant}} => "{{color}}",
        {{/statuses}}
            default => "gray",
        };
    }

    /**
     * Returns true if the given value is valid
     * @param {{statusClass}}|string $value
     * @return bool
     */
    public static function isValid({{statusClass}}|string $value): bool {
        return Arrays::contains([ {{values}} ], self::fromValue($value));
    }

    /**
     * Returns a Select of Statuses
     * @param string $isoCode Optional.
     * @return Select[]
     */
    public static function getSelect(string $isoCode = ""): array {
        $result = [];
        foreach ([ {{values}} ] as $status) {
            $result[] = new Select(
                $status->name,
                self::getName($status, $isoCode),
                [ "color" => self::getColor($status) ]
            );
        }
        return $result;
    }



    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->name;
    }
}
