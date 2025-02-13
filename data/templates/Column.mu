<?php
namespace {{namespace}}Schema;

use Framework\Utils\Strings;

/**
 * The {{name}} Column
 */
enum {{name}}Column : string {

{{#columns}}
    {{#addSpace}}

    {{/addSpace}}
    case {{name}} = "{{value}}";
{{/columns}}



    /**
     * Get the key of the column
     * @return string
     */
    public function key(): string {
        return Strings::lowerCaseFirst($this->name);
    }

    /**
     * Get the name of the column without the table
     * @return string
     */
    public function base(): string {
        return Strings::substringAfter($this->value, ".");
    }

    /**
     * Get the name of the column without the table
     * @param self[]|self|null $values
     * @return string[]
     */
    public static function toKeys(array|self|null $values): array {
        if (is_null($values)) {
            return [];
        }
        if ($values instanceof static) {
            return [ $values->key() ];
        }
        return array_map(fn($value) => $value->key(), $values);
    }
}
