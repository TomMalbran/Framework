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
}
