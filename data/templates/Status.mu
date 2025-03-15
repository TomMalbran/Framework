<?php
namespace {{namespace}};

use Framework\Core\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\Select;

/**
 * The Status
 */
enum Status {

    case None;

{{#statuses}}
    case {{name}};
{{/statuses}}



    /**
     * Creates a Status from a String
     * @param Status|string $value
     * @param Status        $default Optional.
     * @return Status
     */
    public static function from(Status|string $value, Status $default = self::None): Status {
        if ($value instanceof Status) {
            return $value;
        }
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }
        return $default;
    }

    /**
     * Creates a list of Statuses from the given Strings
     * @param string[] $values
     * @return Status[]
     */
    public static function fromList(array $values): array {
        $result = [];
        foreach ($values as $value) {
            $result[] = self::from($value);
        }
        return $result;
    }

    /**
     * Returns the Name of the given Status
     * @param Status|string $value
     * @param string        $isoCode Optional.
     * @return string
     */
    public static function getName(Status|string $value, string $isoCode = ""): string {
        $value = ($value instanceof Status) ? $value->name : $value;
        return NLS::getIndex("SELECT_STATUS", $value, $isoCode);
    }

    /**
     * Returns the Color of the given Status
     * @param Status|string $value
     * @return string
     */
    public static function getColor(Status|string $value): string {
        return match (self::from($value)) {
        {{#statuses}}
            self::{{constant}} => "{{color}}",
        {{/statuses}}
            {{default}} => "gray",
        };
    }

    /**
     * Returns a Select for the given value
     * @param Status|string $value
     * @param string        $isoCode Optional.
     * @return Select
     */
    private static function getSelect(Status|string $value, string $isoCode = ""): Select {
        $status = self::from($value);
        $key    = $status->name;
        $name   = self::getName($value, $isoCode);
        $color  = self::getColor($value);
        return new Select($key, $name, [ "color" => $color ]);
    }


{{#statuses}}

    /**
     * Returns true if the given value is the Status {{name}}
     * @param Status|string $value
     * @return boolean
     */
    public static function is{{name}}(Status|string $value): bool {
        return self::{{name}} === self::from($value);
    }
{{/statuses}}


{{#groups}}

    /**
     * Returns true if the given value is one of: {{statuses}}
     * @param Status|string $value
     * @return boolean
     */
    public static function isValid{{name}}(Status|string $value): bool {
        return Arrays::contains([ {{values}} ], self::from($value));
    }

    /**
     * Returns a Select for the values: {{statuses}}
     * @param string $isoCode Optional.
     * @return Select[]
     */
    public static function get{{name}}Select(string $isoCode = ""): array {
        $result = [];
        foreach ([ {{values}} ] as $status) {
            $result[] = self::getSelect($status, $isoCode);
        }
        return $result;
    }
{{/groups}}
}
