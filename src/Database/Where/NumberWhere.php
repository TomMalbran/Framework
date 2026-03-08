<?php
namespace Framework\Database\Where;

use Framework\Database\Query\Operator;
use Framework\Database\Where\BaseWhere;

/**
 * The Number Where
 */
class NumberWhere extends BaseWhere {

    /**
     * Adds a Compare condition
     * @param Operator      $operator
     * @param list<int>|int $value
     * @param bool|null     $condition Optional.
     * @return void
     */
    public function compare(Operator $operator, array|int $value, ?bool $condition = null): void {
        $this->query->where(
            column:    $this->column,
            operator:  $operator,
            value:     $value,
            condition: $condition,
        );
    }

    /**
     * Adds a Compare If condition
     * @param Operator      $operator
     * @param list<int>|int $value
     * @param bool|null     $condition Optional.
     * @return void
     */
    public function compareIf(Operator $operator, array|int $value, ?bool $condition = null): void {
        $this->query->whereIf(
            column:    $this->column,
            operator:  $operator,
            value:     $value,
            condition: $condition,
        );
    }



    /**
     * Adds an Equal condition
     * @param int $value
     * @return void
     */
    public function equal(int $value): void {
        $this->compare(Operator::Equal, $value);
    }

    /**
     * Adds an Equal If condition
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalIf(int $value, ?bool $condition = null): void {
        $this->compareIf(Operator::Equal, $value, $condition);
    }

    /**
     * Adds a Not Equal condition
     * @param int $value
     * @return void
     */
    public function notEqual(int $value): void {
        $this->compare(Operator::NotEqual, $value);
    }

    /**
     * Adds a Not Equal If condition
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function notEqualIf(int $value, ?bool $condition = null): void {
        $this->compareIf(Operator::NotEqual, $value, $condition);
    }



    /**
     * Adds a Greater Than condition
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function greaterThan(int $value, ?bool $condition = null): void {
        $this->compare(Operator::GreaterThan, $value, $condition);
    }

    /**
     * Adds a Greater or Equal condition
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function greaterOrEqual(int $value, ?bool $condition = null): void {
        $this->compare(Operator::GreaterOrEqual, $value, $condition);
    }

    /**
     * Adds a Less Than condition
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function lessThan(int $value, ?bool $condition = null): void {
        $this->compare(Operator::LessThan, $value, $condition);
    }

    /**
     * Adds a Less or Equal condition
     * @param int       $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function lessOrEqual(int $value, ?bool $condition = null): void {
        $this->compare(Operator::LessOrEqual, $value, $condition);
    }



    /**
     * Adds an In condition
     * @param list<int> $values
     * @param bool|null $condition Optional.
     * @return void
     */
    public function in(array $values, ?bool $condition = null): void {
        if (count($values) > 0) {
            $this->compare(Operator::In, $values, $condition);
        }
    }

    /**
     * Adds a Not In condition
     * @param list<int> $values
     * @param bool|null $condition Optional.
     * @return void
     */
    public function notIn(array $values, ?bool $condition = null): void {
        if (count($values) > 0) {
            $this->compare(Operator::NotIn, $values, $condition);
        }
    }
}
