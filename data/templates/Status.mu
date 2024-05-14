<?php
namespace {{namespace}}System;

use Framework\NLS\NLS;
use Framework\Utils\Arrays;
use Framework\Utils\Select;

/**
 * The Status
 */
class Status {
{{#statuses}}
{{#addSpace}}

    // {{group}}: {{statuses}}
{{/addSpace}}
    const {{constant}} = "{{name}}";
{{/statuses}}



    /**
     * Returns true if the Name of the given Status
     * @param string $value
     * @param string $isoCode Optional.
     * @return string
     */
    public static function getName(string $value, string $isoCode = ""): string {
        return NLS::getIndex("SELECT_STATUS", $value, $isoCode);
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
        foreach ([ {{values}} ] as $status) {
            $name     = self::getName($status, $isoCode);
            $result[] = new Select($status, $name);
        }
        return $result;
    }
{{/groups}}
}
