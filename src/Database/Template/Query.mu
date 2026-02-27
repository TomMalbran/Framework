<?php
namespace {{namespace}};

use {{namespace}}\{{columnClass}};{{#imports}}
use {{.}};{{/imports}}

use Framework\Database\Query\Query;
use Framework\Database\Query\QueryOperator;
use Framework\Database\Query\SchemaQuery;{{#queries}}
use Framework\Database\Query\{{.}};{{/queries}}

/**
 * The {{name}} Query
 */
class {{queryClass}} extends SchemaQuery {

    public string $tableName = "{{tableName}}";
    public string $idDbName  = "{{idDbName}}";


{{#properties}}

    // {{value}}
    public {{type}} ${{name}};
{{/properties}}



    /**
     * Creates a new {{queryClass}} instance
     * @param Query|null $query Optional.
     */
    public function __construct(?Query $query = null) {
        parent::__construct($query);

        {{#properties}}
        $this->{{propName}} = new {{type}}($this->query, "{{value}}");
        {{/properties}}
    }

    /**
     * Adds an expression
     * @param {{columnClass}} $column
     * @param QueryOperator $operator
     * @param list<int|string>|int|string $value
     * @param bool $caseSensitive Optional.
     * @param bool|null $condition Optional.
     * @return {{queryClass}}
     */
    public function add(
        {{columnClass}} $column,
        QueryOperator $operator,
        array|int|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): {{queryClass}} {
        if ($column !== {{columnClass}}::None) {
            $this->query->add($column->value, $operator, $value, $caseSensitive, $condition);
        }
        return $this;
    }

    /**
     * Adds a Search expression
     * @param list<{{columnClass}}> $column
     * @param mixed $value
     * @param QueryOperator $operator Optional.
     * @param bool $caseInsensitive Optional.
     * @param bool $splitValue Optional.
     * @param string $splitText Optional.
     * @param bool $matchAny Optional.
     * @return {{queryClass}}
     */
    public function search(
        array $column,
        mixed $value,
        QueryOperator $operator = QueryOperator::Like,
        bool $caseInsensitive = true,
        bool $splitValue = false,
        string $splitText = " ",
        bool $matchAny = false,
    ): {{queryClass}} {
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
     * @return {{queryClass}}
     */
    public function exists(SchemaQuery $subQuery): {{queryClass}} {
        $subQuery->addExp("{$subQuery->tableName}.{$this->idDbName} = {$this->tableName}.{$this->idDbName}");
        $this->query->addExp("EXISTS (
            SELECT 1 FROM {$subQuery->tableName}
            " . $subQuery->query->get() . "
        )", ...$subQuery->query->params);
        return $this;
    }

    /**
     * Adds a Not Exists expression
     * @param SchemaQuery $subQuery
     * @return {{queryClass}}
     */
    public function notExists(SchemaQuery $subQuery): {{queryClass}} {
        $subQuery->addExp("{$subQuery->tableName}.{$this->idDbName} = {$this->tableName}.{$this->idDbName}");
        $this->query->addExp("NOT EXISTS (
            SELECT 1 FROM {$subQuery->tableName}
            " . $subQuery->query->get() . "
        )", ...$subQuery->query->params);
        return $this;
    }
}
