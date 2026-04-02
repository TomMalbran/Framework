<?php
namespace {{namespace}};

use Framework\IO\Select;
use Framework\Auth\Auth;
use Framework\Intl\NLS;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\Arrays;

use JsonSerializable;

/**
 * The Access
 */
enum Access implements Enum, JsonSerializable {
    use IsEnum;

    case None;
{{#roles}}
{{#addSpace}}

    // {{group}}
{{/addSpace}}
    case {{name}};
{{/roles}}



    /**
     * Returns the Name of the given Access
     * @param Access $value
     * @param string $isoCode Optional.
     * @return string
     */
    public static function getName(Access $value, string $isoCode = ""): string {
        return NLS::getIndex("SELECT_ACCESS", $value, $isoCode);
    }

    /**
     * Returns the Level of the given Access
     * @param Access $value
     * @return int
     */
    public static function getLevel(Access $value): int {
        return match ($value) {
        {{#roles}}
            self::{{constant}} => {{level}},
        {{/roles}}
            {{default}} => 0,
        };
    }



{{#roles}}
    /**
     * Returns true if the current user is {{name}}
     * @return bool
     */
    public static function is{{name}}(): bool {
        return Auth::getAccessName() === self::{{name}};
    }

    /**
     * Returns true if the current user is {{name}} or Lower
     * @return bool
     */
    public static function is{{name}}OrLower(): bool {
        return self::getLevel(Auth::getAccessName()) <= self::getLevel(self::{{name}});
    }

    /**
     * Returns true if the current user is {{name}} or Higher
     * @return bool
     */
    public static function is{{name}}OrHigher(): bool {
        return self::getLevel(Auth::getAccessName()) >= self::getLevel(self::{{name}});
    }



{{/roles}}
{{#groups}}
    /**
     * Returns true if the current user is in the group {{name}}
     * @return bool
     */
    public static function in{{name}}s(): bool {
        return self::isValid{{name}}(Auth::getAccessName());
    }

{{/groups}}
{{#groups}}


    /**
     * Returns true if the given value is one of: {{roles}}
     * @param Access $value
     * @return bool
     */
    public static function isValid{{name}}(Access $value): bool {
        return Arrays::contains([ {{values}} ], $value);
    }

    /**
     * Returns an array with the values of: {{roles}}
     * @return list<Access>
     */
    public static function get{{name}}s(): array {
        return [ {{values}} ];
    }

    /**
     * Returns a Select for the values: {{roles}}
     * @param string $isoCode Optional.
     * @return list<Select>
     */
    public static function get{{name}}Select(string $isoCode = ""): array {
        $result = [];
        foreach ([ {{values}} ] as $value) {
            $name     = self::getName($value, $isoCode);
            $result[] = new Select($value, $name);
        }
        return $result;
    }
{{/groups}}
}
