<?php
namespace {{namespace}};

use Framework\Intl\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The {{name}} Status
 */
enum {{status}} {

    case None;

{{#statuses}}
    case {{name}};
{{/statuses}}



    /**
     * Creates a Status from a String
     * @param {{status}}|string $value
     * @param {{status}} $default Optional.
     * @return {{status}}
     */
    public static function fromValue({{status}}|string $value, {{status}} $default = self::None): {{status}} {
        if ($value instanceof {{status}}) {
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
     * @return {{status}}[]
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
     * @param {{status}}[] $values
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
     * @param {{status}}|string $value
     * @param string $isoCode Optional.
     * @return string
     */
    public static function getName({{status}}|string $value, string $isoCode = ""): string {
        $value = ($value instanceof {{status}}) ? $value->name : $value;
        return NLS::getIndex("SELECT_STATUS", $value, $isoCode);
    }

    /**
     * Returns the Color of the given Status
     * @param {{status}}|string $value
     * @return string
     */
    public static function getColor({{status}}|string $value): string {
        return match (self::fromValue($value)) {
        {{#statuses}}
            self::{{constant}} => "{{color}}",
        {{/statuses}}
            default => "gray",
        };
    }

    /**
     * Returns true if the given value is valid
     * @param {{status}}|string $value
     * @return bool
     */
    public static function isValid({{status}}|string $value): bool {
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


{{#statuses}}

    /**
     * Returns true if the given value is the Status {{name}}
     * @param {{status}}|string $value
     * @return bool
     */
    public static function is{{name}}({{status}}|string $value): bool {
        return self::{{name}} === self::fromValue($value);
    }
{{/statuses}}
}
