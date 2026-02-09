<?php
namespace {{namespace}};

use Framework\Intl\NLS;
use Framework\Database\Type\Enum;
use Framework\Database\Type\IsEnum;
use Framework\Utils\Select;

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
     * Returns a Select of Statuses
     * @param string $isoCode Optional.
     * @return Select[]
     */
    public static function getSelect(string $isoCode = ""): array {
        $result = [];
        foreach (self::cases() as $status) {
            if ($status === self::None) {
                continue;
            }
            $result[] = new Select(
                $status->name,
                self::getName($status, $isoCode),
                [ "color" => self::getColor($status) ]
            );
        }
        return $result;
    }
}
