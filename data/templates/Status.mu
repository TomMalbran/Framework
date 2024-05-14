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

{{/addSpace}}
    const {{constant}} = {{value}};
{{/statuses}}



    /**
     * Returns the Status Value from a Status Name
     * @param string $name
     * @return integer
     */
    public static function getValue(string $name): int {
        return match ($name) {
            {{#statuses}}
            self::{{constant}} => {{value}},
            {{/statuses}}
            default => 0,
        };
    }



{{#statuses}}

    /**
     * Returns true if the given value is the Status {{name}}
     * @param integer $value
     * @return boolean
     */
    public static function is{{name}}(int $value): bool {
        return self::{{name}} === $value;
    }
{{/statuses}}


{{#groups}}

    /**
     * Returns true if the given value is in the Status Group {{name}}
     * @param integer $value
     * @return boolean
     */
    public static function isValid{{name}}(int $value): bool {
        return Arrays::contains([ {{values}} ], $value);
    }

    /**
     * Returns a Select for the Status Group {{name}}
     * @param string $isoCode Optional.
     * @return Select[]
     */
    public static function get{{name}}Select(string $isoCode = ""): array {
        $result = [];
        foreach ([ {{values}} ] as $status) {
            $name     = NLS::getIndex("SELECT_STATUS", $status, $isoCode);
            $result[] = new Select($status, $name);
        }
        return $result;
    }
{{/groups}}
}
