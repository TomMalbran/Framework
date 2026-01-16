<?php
namespace {{namespace}};

use {{namespace}}\{{column}};{{#imports}}
use {{.}};{{/imports}}

use Framework\Database\Query\Query;
use Framework\Database\Query\QueryOperator;
use Framework\Database\Query\SchemaQuery;
use Framework\Database\Query\BooleanQuery;
use Framework\Database\Query\NumberQuery;
use Framework\Database\Query\StringQuery;

/**
 * The {{name}} Query
 */
class {{query}} extends SchemaQuery {

    public string $tableName = "{{tableName}}";
    public string $idDbName  = "{{idDbName}}";


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
        $this->{{constName}} = new {{queryType}}($this->query, "{{value}}");
        {{/properties}}
    }

    /**
     * Adds an expression
     * @param {{column}} $column
     * @param QueryOperator $operator
     * @param mixed[]|integer|string $value
     * @param boolean $caseSensitive Optional.
     * @param boolean|null $condition Optional.
     * @return {{query}}
     */
    public function add(
        {{column}} $column,
        QueryOperator $operator,
        array|int|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): {{query}} {
        if ($column !== {{column}}::None) {
            $this->query->add($column->value, $operator, $value, $caseSensitive, $condition);
        }
        return $this;
    }

    /**
     * Adds a Search expression
     * @param {{column}}[] $column
     * @param mixed $value
     * @param QueryOperator $operator Optional.
     * @param boolean $caseInsensitive Optional.
     * @param boolean $splitValue Optional.
     * @param string $splitText Optional.
     * @param boolean $matchAny Optional.
     * @return {{query}}
     */
    public function search(
        array $column,
        mixed $value,
        QueryOperator $operator = QueryOperator::Like,
        bool $caseInsensitive = true,
        bool $splitValue = false,
        string $splitText = " ",
        bool $matchAny = false,
    ): {{query}} {
        $columns = [];
        foreach ($column as $col) {
            $columns[] = $col->value;
        }
        $this->query->search($columns, $value, $operator, $caseInsensitive, $splitValue, $splitText, $matchAny);
        return $this;
    }

    /**
     * Adds an Exists expression
     * @param SchemaQuery $subQuery
     * @return {{query}}
     */
    public function exists(SchemaQuery $subQuery): {{query}} {
        $subQuery->addExp("{$subQuery->tableName}.{$this->idDbName} = {$this->tableName}.{$this->idDbName}");
        $this->query->addExp("EXISTS (
            SELECT 1 FROM {$subQuery->tableName}
            " . $subQuery->query->get() . "
        )", ...$subQuery->query->params);
        return $this;
    }

    /**
     * Adds an Not Exists expression
     * @param SchemaQuery $subQuery
     * @return {{query}}
     */
    public function notExists(SchemaQuery $subQuery): {{query}} {
        $subQuery->addExp("{$subQuery->tableName}.{$this->idDbName} = {$this->tableName}.{$this->idDbName}");
        $this->query->addExp("NOT EXISTS (
            SELECT 1 FROM {$subQuery->tableName}
            " . $subQuery->query->get() . "
        )", ...$subQuery->query->params);
        return $this;
    }
{{#statuses}}


    /**
     * Adds a {{name}} Equals condition
     * @param {{status}} ...$statuses
     * @return {{query}}
     */
    public function {{name}}Equal({{status}} ...$statuses): {{query}} {
        $values = {{status}}::toNames($statuses);
        $this->query->add("{{value}}", QueryOperator::Equal, $values);
        return $this;
    }

    /**
     * Adds a {{name}} Not Equals condition
     * @param {{status}} ...$statuses
     * @return {{query}}
     */
    public function {{name}}NotEqual({{status}} ...$statuses): {{query}} {
        $values = {{status}}::toNames($statuses);
        $this->query->add("{{value}}", QueryOperator::NotEqual, $values);
        return $this;
    }
{{/statuses}}
}
