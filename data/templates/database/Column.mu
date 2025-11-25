<?php
namespace {{namespace}};

use Framework\Utils\Strings;

/**
 * The {{name}} Column
 */
enum {{name}}Column : string {

    case None = "";

{{#columns}}
    {{#addSpace}}

    {{/addSpace}}
    case {{name}} = "{{value}}";
{{/columns}}



    /**
     * Creates a {{name}} Column from a String
     * @param string $value
     * @return {{name}}Column
     */
    public static function fromValue(string $value): {{name}}Column {
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }
        return self::None;
    }

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
