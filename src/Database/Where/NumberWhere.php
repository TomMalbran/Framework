<?php
namespace Framework\Database\Where;

use Framework\IO\Value\NumberValue;
use Framework\Database\Query\Operator;
use Framework\Database\Where\BaseWhere;

/**
 * The Number Where
 */
class NumberWhere extends BaseWhere {

    /**
     * Adds a Compare condition
     * @param Operator                  $operator
     * @param NumberValue|list<int>|int $value
     * @param bool|null                 $condition Optional.
     * @return void
     */
    public function compare(
        Operator $operator,
        NumberValue|array|int $value,
        ?bool $condition = null,
    ): void {
        $this->query->where(
            column:    $this->column,
            operator:  $operator,
            value:     $value,
            condition: $condition,
        );
    }

    /**
     * Adds a Compare If condition
     * @param Operator                  $operator
     * @param NumberValue|list<int>|int $value
     * @param bool|null                 $condition Optional.
     * @return void
     */
    public function compareIf(
        Operator $operator,
        NumberValue|array|int $value,
        ?bool $condition = null,
    ): void {
        $this->query->whereIf(
            column:    $this->column,
            operator:  $operator,
            value:     $value,
            condition: $condition,
        );
    }



    /**
     * Adds an Equal condition
     * @param NumberValue|int $value
     * @return void
     */
    public function equal(NumberValue|int $value): void {
        $this->compare(Operator::Equal, $value);
    }

    /**
     * Adds an Equal If condition
     * @param NumberValue|int $value
     * @param bool|null       $condition Optional.
     * @return void
     */
    public function equalIf(NumberValue|int $value, ?bool $condition = null): void {
        $this->compareIf(Operator::Equal, $value, $condition);
    }

    /**
     * Adds a Not Equal condition
     * @param NumberValue|int $value
     * @return void
     */
    public function notEqual(NumberValue|int $value): void {
        $this->compare(Operator::NotEqual, $value);
    }

    /**
     * Adds a Not Equal If condition
     * @param NumberValue|int $value
     * @param bool|null       $condition Optional.
     * @return void
     */
    public function notEqualIf(NumberValue|int $value, ?bool $condition = null): void {
        $this->compareIf(Operator::NotEqual, $value, $condition);
    }



    /**
     * Adds a Greater Than condition
     * @param NumberValue|int $value
     * @param bool|null       $condition Optional.
     * @return void
     */
    public function greaterThan(NumberValue|int $value, ?bool $condition = null): void {
        $this->compare(Operator::GreaterThan, $value, $condition);
    }

    /**
     * Adds a Greater or Equal condition
     * @param NumberValue|int $value
     * @param bool|null       $condition Optional.
     * @return void
     */
    public function greaterOrEqual(NumberValue|int $value, ?bool $condition = null): void {
        $this->compare(Operator::GreaterOrEqual, $value, $condition);
    }

    /**
     * Adds a Less Than condition
     * @param NumberValue|int $value
     * @param bool|null       $condition Optional.
     * @return void
     */
    public function lessThan(NumberValue|int $value, ?bool $condition = null): void {
        $this->compare(Operator::LessThan, $value, $condition);
    }

    /**
     * Adds a Less or Equal condition
     * @param NumberValue|int $value
     * @param bool|null       $condition Optional.
     * @return void
     */
    public function lessOrEqual(NumberValue|int $value, ?bool $condition = null): void {
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
