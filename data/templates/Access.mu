<?php
namespace {{codeSpace}};

use Framework\Auth\Auth;
use Framework\NLS\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\Select;

/**
 * The Access
 */
class Access {
{{#accesses}}
{{#addSpace}}

    // {{group}}
{{/addSpace}}
    const {{constant}} = "{{name}}";
{{/accesses}}



    /**
     * Returns the Name of the given Access
     * @param string $value
     * @param string $isoCode Optional.
     * @return string
     */
    public static function getName(string $value, string $isoCode = ""): string {
        return NLS::getIndex("SELECT_ACCESS", $value, $isoCode);
    }

    /**
     * Returns the Level of the given Access
     * @param string $value
     * @return integer
     */
    public static function getLevel(string $value): int {
        return match ($value) {
        {{#accesses}}
            self::{{constant}} => {{level}},
        {{/accesses}}
            default => 0,
        };
    }



{{#accesses}}
    /**
     * Returns true if the current user is an {{name}}
     * @return boolean
     */
    public static function is{{name}}(): bool {
        return Auth::getAccessName() === self::{{name}};
    }

    /**
     * Returns true if the current user is an {{name}} or Lower
     * @return boolean
     */
    public static function is{{name}}OrLower(): bool {
        return self::getLevel(Auth::getAccessName()) <= self::getLevel(self::{{name}});
    }

    /**
     * Returns true if the current user is an {{name}} or Higher
     * @return boolean
     */
    public static function is{{name}}OrHigher(): bool {
        return self::getLevel(Auth::getAccessName()) >= self::getLevel(self::{{name}});
    }



{{/accesses}}
{{#groups}}
    /**
     * Returns true if the current user is in the group {{name}}
     * @return boolean
     */
    public static function in{{name}}s(): bool {
        return self::isValid{{name}}(Auth::getAccessName());
    }

{{/groups}}
{{#groups}}


    /**
     * Returns true if the given value is one of: {{accesses}}
     * @param string $value
     * @return boolean
     */
    public static function isValid{{name}}(string $value): bool {
        return Arrays::contains([ {{values}} ], $value);
    }

    /**
     * Returns an array with the values of: {{accesses}}
     * @return string[]
     */
    public static function get{{name}}s(): array {
        return [ {{values}} ];
    }

    /**
     * Returns a Select for the values: {{accesses}}
     * @param string $isoCode Optional.
     * @return Select[]
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
