<?php
namespace {{namespace}};

use {{namespace}}\{{columnClass}};{{#imports}}
use {{.}};{{/imports}}

use Framework\Database\Query\Operator;
use Framework\Database\Query\SchemaQuery;{{#queries}}
use Framework\Database\Where\{{.}};{{/queries}}

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
     */
    public function __construct() {
        parent::__construct();

        {{#properties}}
        $this->{{propName}} = new {{type}}($this->query, "{{value}}");
        {{/properties}}
    }

    /**
     * Adds a where expression
     * @param {{columnClass}} $column
     * @param Operator $operator
     * @param list<int|string>|int|string $value
     * @param bool $caseSensitive Optional.
     * @param bool|null $condition Optional.
     * @return {{queryClass}}
     */
    public function where(
        {{columnClass}} $column,
        Operator $operator,
        array|int|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): {{queryClass}} {
        if ($column !== {{columnClass}}::None) {
            $this->query->where($column->name(), $operator, $value, $caseSensitive, $condition);
        }
        return $this;
    }

    /**
     * Adds a Search expression
     * @param list<{{columnClass}}> $column
     * @param mixed $value
     * @param Operator $operator Optional.
     * @param bool $caseInsensitive Optional.
     * @param bool $splitValue Optional.
     * @param string $splitText Optional.
     * @param bool $matchAny Optional.
     * @return {{queryClass}}
     */
    public function search(
        array $column,
        mixed $value,
        Operator $operator = Operator::Like,
        bool $caseInsensitive = true,
        bool $splitValue = false,
        string $splitText = " ",
        bool $matchAny = false,
    ): {{queryClass}} {
        $columns = [];
        foreach ($column as $col) {
            $columns[] = $col->name();
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
        $subQuery->whereExp("{$subQuery->tableName}.{$this->idDbName} = {$this->tableName}.{$this->idDbName}");
        $this->query->whereExp("EXISTS (
            SELECT 1 FROM {$subQuery->tableName}
            " . $subQuery->query->get() . "
        )", ...$subQuery->query->getParams());
        return $this;
    }

    /**
     * Adds a Not Exists expression
     * @param SchemaQuery $subQuery
     * @return {{queryClass}}
     */
    public function notExists(SchemaQuery $subQuery): {{queryClass}} {
        $subQuery->whereExp("{$subQuery->tableName}.{$this->idDbName} = {$this->tableName}.{$this->idDbName}");
        $this->query->whereExp("NOT EXISTS (
            SELECT 1 FROM {$subQuery->tableName}
            " . $subQuery->query->get() . "
        )", ...$subQuery->query->getParams());
        return $this;
    }
}
