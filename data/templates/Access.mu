<?php
namespace {{codeSpace}};

use Framework\Auth\Auth;
use Framework\Utils\Arrays;

/**
 * The Access
 */
class Access {
{{#levels}}
{{#addSpace}}

    // {{group}}
{{/addSpace}}
    const {{constant}} = {{level}};
{{/levels}}



{{#levels}}
    /**
     * Returns true if the current user is an {{name}}
     * @return boolean
     */
    public static function is{{name}}(): bool {
        return Auth::getAccessLevel() === self::{{name}};
    }

    /**
     * Returns true if the current user is an {{name}} or Lower
     * @return boolean
     */
    public static function is{{name}}OrLower(): bool {
        return Auth::getAccessLevel() <= self::{{name}};
    }

    /**
     * Returns true if the current user is an {{name}} or Higher
     * @return boolean
     */
    public static function is{{name}}OrHigher(): bool {
        return Auth::getAccessLevel() >= self::{{name}};
    }



{{/levels}}
{{#groups}}
    /**
     * Returns true if the current user is in the group {{name}}
     * @return boolean
     */
    public static function in{{name}}s(): bool {
        return self::isValid{{name}}(Auth::getAccessLevel());
    }

{{/groups}}
{{#groups}}


    /**
     * Returns true if the given value is one of: {{accesses}}
     * @param integer $level
     * @return boolean
     */
    public static function isValid{{name}}(int $level): bool {
        return Arrays::contains([ {{levels}} ], $level);
    }

    /**
     * Returns an array with the levels of: {{accesses}}
     * @return integer[]
     */
    public static function get{{name}}s(): array {
        return [ {{levels}} ];
    }
{{/groups}}
}
