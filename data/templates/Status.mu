<?php
namespace {{codeSpace}};

use Framework\NLS\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\Select;

/**
 * The Status
 */
class Status {

{{#statuses}}
    const {{constant}} = "{{name}}";
{{/statuses}}



    /**
     * Returns the Name of the given Status
     * @param string $value
     * @param string $isoCode Optional.
     * @return string
     */
    public static function getName(string $value, string $isoCode = ""): string {
        return NLS::getIndex("SELECT_STATUS", $value, $isoCode);
    }

    /**
     * Returns the Color of the given Status
     * @param string $value
     * @return string
     */
    public static function getColor(string $value): string {
        return match ($value) {
        {{#statuses}}
            self::{{constant}} => "{{color}}",
        {{/statuses}}
            default => "gray",
        };
    }


{{#statuses}}

    /**
     * Returns true if the given value is the Status {{name}}
     * @param string $value
     * @return boolean
     */
    public static function is{{name}}(string $value): bool {
        return self::{{name}} === $value;
    }
{{/statuses}}


{{#groups}}

    /**
     * Returns true if the given value is one of: {{statuses}}
     * @param string $value
     * @return boolean
     */
    public static function isValid{{name}}(string $value): bool {
        return Arrays::contains([ {{values}} ], $value);
    }

    /**
     * Returns a Select for the values: {{statuses}}
     * @param string $isoCode Optional.
     * @return Select[]
     */
    public static function get{{name}}Select(string $isoCode = ""): array {
        $result = [];
        foreach ([ {{values}} ] as $value) {
            $result[] = self::getSelect($value, $isoCode);
        }
        return $result;
    }
{{/groups}}


    /**
     * Returns a Select for the given value
     * @param string $value
     * @param string $isoCode Optional.
     * @return Select
     */
    private static function getSelect(string $value, string $isoCode): Select {
        $name  = self::getName($value, $isoCode);
        $color = self::getColor($value);
        return new Select($value, $name, [ "color" => $color ]);
    }
}
