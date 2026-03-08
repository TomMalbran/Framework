<?php
namespace Framework\Database\Where;

use Framework\Database\Query\Query;

/**
 * The Base Where
 */
class BaseWhere {

    protected Query  $query;
    protected string $column;



    /**
     * Creates a new BaseWhere instance
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
    public function orderByAsc(): void {
        $this->query->orderBy($this->column, isASC: true);
    }

    /**
     * Adds an Order By Descending
     * @return void
     */
    public function orderByDesc(): void {
        $this->query->orderBy($this->column, isASC: false);
    }

    /**
     * Adds a Group By
     * @return void
     */
    public function groupBy(): void {
        $this->query->groupBy($this->column);
    }
}
