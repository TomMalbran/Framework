<?php
namespace Framework\Database\Where;

use Framework\Database\Query\Operator;
use Framework\Database\Where\BaseWhere;

/**
 * The Boolean Where
 */
class BooleanWhere extends BaseWhere {

    /**
     * Adds an Equal condition
     * @param bool      $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equal(bool $value, ?bool $condition = null): void {
        $this->query->where(
            $this->column,
            Operator::Equal,
            (int)$value,
            condition: $condition,
        );
    }



    /**
     * Adds an Any condition
     * @return void
     */
    public function isAny(): void {
        $this->query->where($this->column, Operator::GreaterOrEqual, 0);
    }

    /**
     * Adds a True condition
     * @return void
     */
    public function isTrue(): void {
        $this->equal(value: true);
    }

    /**
     * Adds a True If condition
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalTrueIf(?bool $condition = null): void {
        $this->equal(value: true, condition: $condition);
    }

    /**
     * Adds a False condition
     * @return void
     */
    public function isFalse(): void {
        $this->equal(value: false);
    }

    /**
     * Adds a False If condition
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalFalseIf(?bool $condition = null): void {
        $this->equal(value: false, condition: $condition);
    }
}
