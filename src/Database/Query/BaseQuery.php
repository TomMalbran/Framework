<?php
namespace Framework\Database\Query;

use Framework\Database\Query\Query;

/**
 * The Base Query
 */
class BaseQuery {

    protected Query  $query;
    protected string $column;



    /**
     * Creates a new BooleanQuery instance
     * @param Query  $query
     * @param string $column
     */
    public function __construct(Query $query, string $column) {
        $this->query  = $query;
        $this->column = $column;
    }



    /**
     * Adds an Order By Ascending
     * @return void
     */
    public function orderByAsc() {
        $this->query->orderBy($this->column, true);
    }

    /**
     * Adds an Order By Descending
     * @return void
     */
    public function orderByDesc() {
        $this->query->orderBy($this->column, false);
    }

    /**
     * Adds a Group By
     * @return void
     */
    public function groupBy() {
        $this->query->groupBy($this->column);
    }
}
