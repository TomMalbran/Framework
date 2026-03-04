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
    public function equal(bool $value, ?bool $condition = null): void {
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
    public function isAny(): void {
        $this->query->add($this->column, QueryOperator::GreaterOrEqual, 0);
    }

    /**
     * Generates a True Query
     * @return void
     */
    public function isTrue(): void {
        $this->equal(value: true);
    }

    /**
     * Generates a True Query
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalTrueIf(?bool $condition = null): void {
        $this->equal(value: true, condition: $condition);
    }

    /**
     * Generates a False Query
     * @return void
     */
    public function isFalse(): void {
        $this->equal(value: false);
    }

    /**
     * Generates a False If Query
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalFalseIf(?bool $condition = null): void {
        $this->equal(value: false, condition: $condition);
    }
}
