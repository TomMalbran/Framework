<?php
namespace Framework\Database\Query;

use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\QueryOperator;

/**
 * The Boolean Query
 */
class BooleanQuery extends BaseQuery {

    /**
     * Generates an Equal Query
     * @param bool      $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equal(bool $value, ?bool $condition = null) {
        $this->query->add(
            $this->column,
            QueryOperator::Equal,
            (int)$value,
            condition: $condition,
        );
    }



    /**
     * Generates an Any Query
     * @return void
     */
    public function isAny() {
        $this->query->add($this->column, QueryOperator::GreaterOrEqual, 0);
    }

    /**
     * Generates a True Query
     * @return void
     */
    public function isTrue() {
        $this->query->add($this->column, QueryOperator::Equal, 1);
    }

    /**
     * Generates a False Query
     * @return void
     */
    public function isFalse() {
        $this->query->add($this->column, QueryOperator::Equal, 0);
    }
}
