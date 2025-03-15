<?php
namespace {{namespace}};

use {{namespace}}\{{column}};

use Framework\Database\Query;
use Framework\Database\Query\SchemaQuery;
use Framework\Database\Query\BooleanQuery;
use Framework\Database\Query\FloatQuery;
use Framework\Database\Query\NumberQuery;
use Framework\Database\Query\StatusQuery;
use Framework\Database\Query\StringQuery;

/**
 * The {{name}} Query
 */
class {{query}} extends SchemaQuery {

{{#properties}}
    public {{propType}} ${{propName}} // {{value}}
{{/properties}}



    /**
     * Creates a new {{query}} instance
     * @param Query|null $query Optional.
     */
    public function __construct(?Query $query = null) {
        parent::__construct($query);

        {{#properties}}
        $this->{{constName}} = new {{type}}($this->query, "{{value}}");
        {{/properties}}
    }

    /**
     * Adds an expression
     * @param {{column}} $column
     * @param string $expression
     * @param mixed[]|integer|string $value
     * @param boolean $caseSensitive Optional.
     * @param boolean|null $condition Optional.
     * @return {{query}}
     */
    public function add(
        {{column}} $column,
        string $expression,
        array|int|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): {{query}} {
        $this->query->add($column->value, $expression, $value, $caseSensitive, $condition);
        return $this;
    }

    /**
     * Adds a Search expression
     * @param {{column}}[] $column
     * @param mixed $value
     * @param string $expression Optional.
     * @param boolean $caseInsensitive Optional.
     * @param boolean $splitValue Optional.
     * @param string $splitText Optional.
     * @param boolean $matchAny Optional.
     * @return {{query}}
     */
    public function search(
        array  $column,
        mixed  $value,
        string $expression = "LIKE",
        bool   $caseInsensitive = true,
        bool   $splitValue = false,
        string $splitText = " ",
        bool   $matchAny = false,
    ): {{query}} {
        $columns = [];
        foreach ($column as $col) {
            $columns[] = $col->value;
        }
        $this->query->search($columns, $value, $expression, $caseInsensitive, $splitValue, $splitText, $matchAny);
        return $this;
    }
}
