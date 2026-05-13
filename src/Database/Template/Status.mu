<?php
namespace {{namespace}};

use Framework\IO\Select;
use Framework\IO\Value\EnumValue;
use Framework\Intl\NLS;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The {{name}} Status
 */
enum {{statusClass}} implements Enum, JsonSerializable {
    use IsEnum;

    case None;

{{#statuses}}
    case {{name}};
{{/statuses}}



    /**
     * Creates a list of Names from the given Statuses
     * @param list<{{statusClass}}> $values
     * @return list<string>
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
     * @param {{statusClass}}|EnumValue|string $value
     * @param string $isoCode Optional.
     * @return string
     */
    public static function getName({{statusClass}}|EnumValue|string $value, string $isoCode = ""): string {
        return NLS::getIndex("SELECT_STATUS", $value, $isoCode);
    }

    /**
     * Returns the Color of the given Status
     * @param {{statusClass}}|EnumValue|string $value
     * @return string
     */
    public static function getColor({{statusClass}}|EnumValue|string $value): string {
        return match (self::fromValue($value)) {
        {{#statuses}}
            self::{{name}} => "{{color}}",
        {{/statuses}}
            default => "gray",
        };
    }

    /**
     * Returns true if the given value is valid
     * @param {{statusClass}}|EnumValue|string $value
     * @return bool
     */
    public static function isValid({{statusClass}}|EnumValue|string $value): bool {
        $status = self::fromValue($value);
        return self::contains([ {{values}} ], $status);
    }



    /**
     * Returns a Select of Statuses
     * @param string $isoCode Optional.
     * @return list<Select>
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
     * Returns a Select of Statuses including hidden ones
     * @param string $isoCode Optional.
     * @return list<Select>
     */
    public static function getFullSelect(string $isoCode = ""): array {
        $result = [];
        foreach (self::getAll() as $status) {
            $result[] = new Select(
                $status->name,
                self::getName($status, $isoCode),
                [ "color" => self::getColor($status) ]
            );
        }
        return $result;
    }
}
