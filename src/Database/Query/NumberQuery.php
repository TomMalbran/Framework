<?php
namespace Framework\Database\Query;

use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\QueryOperator;

/**
 * The Number Query
 */
class NumberQuery extends BaseQuery {

    /**
     * Generates a Compare Query
     * @param QueryOperator $operator
     * @param int[]|int     $value
     * @param bool|null     $condition Optional.
     * @return void
     */
    public function compare(QueryOperator $operator, array|int $value, ?bool $condition = null): void {
        $this->query->add(
            $this->column,
            $operator,
            $value,
            condition: $condition,
        );
    }

    /**
     * Generates a Compare If Query
     * @param QueryOperator $operator
     * @param int[]|int     $value
     * @param bool|null     $condition Optional.
     * @return void
     */
    public function compareIf(QueryOperator $operator, array|int $value, ?bool $condition = null): void {
        $this->query->addIf(
            $this->column,
            $operator,
            $value,
            $condition,
        );
    }



    /**
     * Generates an Equal Query
     * @param int $value
     * @return void
     */
    public function equal(int $value): void {
        $this->compare(QueryOperator::Equal, $value);
    }

    /**
     * Generates an Equal If Query
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalIf(int $value, ?bool $condition = null): void {
        $this->compareIf(QueryOperator::Equal, $value, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param int $value
     * @return void
     */
    public function notEqual(int $value): void {
        $this->compare(QueryOperator::NotEqual, $value);
    }

    /**
     * Generates a Not Equal If Query
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function notEqualIf(int $value, ?bool $condition = null): void {
        $this->compareIf(QueryOperator::NotEqual, $value, $condition);
    }



    /**
     * Generates a Greater Than Query
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function greaterThan(int $value, ?bool $condition = null): void {
        $this->compare(QueryOperator::GreaterThan, $value, $condition);
    }

    /**
     * Generates a Greater or Equal Query
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function greaterOrEqual(int $value, ?bool $condition = null): void {
        $this->compare(QueryOperator::GreaterOrEqual, $value, $condition);
    }

    /**
     * Generates a Less Than Query
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function lessThan(int $value, ?bool $condition = null): void {
        $this->compare(QueryOperator::LessThan, $value, $condition);
    }

    /**
     * Generates a Less or Equal Query
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function lessOrEqual(int $value, ?bool $condition = null): void {
        $this->compare(QueryOperator::LessOrEqual, $value, $condition);
    }



    /**
     * Generates an In Query
     * @param int[]     $values
     * @param bool|null $condition Optional.
     * @return void
     */
    public function in(array $values, ?bool $condition = null): void {
        if (count($values) > 0) {
            $this->compare(QueryOperator::In, $values, $condition);
        }
    }

    /**
     * Generates a Not In Query
     * @param int[]     $values
     * @param bool|null $condition Optional.
     * @return void
     */
    public function notIn(array $values, ?bool $condition = null): void {
        if (count($values) > 0) {
            $this->compare(QueryOperator::NotIn, $values, $condition);
        }
    }
}
