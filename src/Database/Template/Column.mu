<?php
namespace {{namespace}};

use Framework\Database\Type\Column;
use Framework\Database\Type\IsColumn;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use JsonSerializable;

/**
 * The {{name}} Column
 */
enum {{columnClass}}: string implements Column, Enum, JsonSerializable {
    use IsEnum;
    use IsColumn;

    case None = "";

{{#columns}}
    {{#addSpace}}

    {{/addSpace}}
    case {{name}} = "{{value}}";
{{/columns}}


    /**
     * Converts the column into a string
     * @return string
     */
    public function toString(): string {
        if ($this === self::None) {
            return "";
        }
        return $this->name;
    }
}
