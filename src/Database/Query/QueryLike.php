<?php
namespace Framework\Database\Query;

use Framework\Database\Query\Query;

/**
 * The Query Like
 */
interface QueryLike {

    /**
     * Returns the Query
     * @return Query
     */
    public function getQuery(): Query;

    /**
     * Returns the Table Name
     * @return string
     */
    public function getTableName(): string;
}
