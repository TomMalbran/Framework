<?php
namespace {{codeSpace}};

use Framework\Auth\Auth;
use Framework\NLS\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\Select;

/**
 * The Access
 */
enum Access {

    case None;
{{#accesses}}
{{#addSpace}}

    // {{group}}
{{/addSpace}}
    case {{name}};
{{/accesses}}



    /**
     * Creates an Access from a String
     * @param Access|string $value
     * @return Access
     */
    public static function from(Access|string $value): Access {
        if ($value instanceof Access) {
            return $value;
        }
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }
        return self::None;
    }

    /**
     * Returns the Name of the given Access
     * @param Access|string $value
     * @param string        $isoCode Optional.
     * @return string
     */
    public static function getName(Access|string $value, string $isoCode = ""): string {
        $value = ($value instanceof Access) ? $value->name : $value;
        return NLS::getIndex("SELECT_ACCESS", $value, $isoCode);
    }

    /**
     * Returns the Level of the given Access
     * @param Access|string $value
     * @return integer
     */
    public static function getLevel(Access|string $value): int {
        return match (self::from($value)) {
        {{#accesses}}
            self::{{constant}} => {{level}},
        {{/accesses}}
            {{default}} => 0,
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
     * @param Access|string $value
     * @return boolean
     */
    public static function isValid{{name}}(Access|string $value): bool {
        return Arrays::contains([ {{values}} ], self::from($value));
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
            $result[] = new Select($value->name, $name);
        }
        return $result;
    }
{{/groups}}
}
