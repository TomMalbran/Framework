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
     * @return Query
     */
    public function orderByAsc(): Query {
        return $this->query->orderBy($this->column, true);
    }

    /**
     * Adds an Order By Descending
     * @return Query
     */
    public function orderByDesc(): Query {
        return $this->query->orderBy($this->column, false);
    }

    /**
     * Adds a Group By
     * @return Query
     */
    public function groupBy(): Query {
        return $this->query->groupBy($this->column);
    }
}
