<?php
namespace Framework\Database\Query;

use Framework\Request;
use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\QueryOperator;
use Framework\Date\Date;
use Framework\Date\Period;

/**
 * The Date Query
 */
class DateQuery extends BaseQuery {

    /**
     * Generates a Compare Query
     * @param QueryOperator $operator
     * @param Date          $date
     * @param bool|null     $condition Optional.
     * @return void
     */
    public function compare(QueryOperator $operator, Date $date, ?bool $condition = null): void {
        if (!$date->isEmpty()) {
            $this->query->where(
                $this->column,
                $operator,
                $date->toTime(),
                condition: $condition,
            );
        }
    }

    /**
     * Generates a Compare If Query
     * @param QueryOperator $operator
     * @param Date          $date
     * @param bool|null     $condition Optional.
     * @return void
     */
    public function compareIf(QueryOperator $operator, Date $date, ?bool $condition = null): void {
        $this->query->whereIf(
            $this->column,
            $operator,
            $date->toTime(),
            $condition,
        );
    }



    /**
     * Generates an Is Empty Query
     * @return void
     */
    public function isEmpty(): void {
        $this->query->where($this->column, QueryOperator::Equal, 0);
    }

    /**
     * Generates an Is Not Empty Query
     * @return void
     */
    public function isNotEmpty(): void {
        $this->query->where($this->column, QueryOperator::NotEqual, 0);
    }

    /**
     * Generates an Equal Query
     * @param Date $date
     * @return void
     */
    public function equal(Date $date): void {
        $this->compare(QueryOperator::Equal, $date);
    }

    /**
     * Generates an Equal If Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalIf(Date $date, ?bool $condition = null): void {
        $this->compareIf(QueryOperator::Equal, $date, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param Date $date
     * @return void
     */
    public function notEqual(Date $date): void {
        $this->compare(QueryOperator::NotEqual, $date);
    }

    /**
     * Generates a Not Equal If Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function notEqualIf(Date $date, ?bool $condition = null): void {
        $this->compareIf(QueryOperator::NotEqual, $date, $condition);
    }



    /**
     * Generates a Greater Than Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function greaterThan(Date $date, ?bool $condition = null): void {
        $this->compare(QueryOperator::GreaterThan, $date, $condition);
    }

    /**
     * Generates a Greater or Equal Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function greaterOrEqual(Date $date, ?bool $condition = null): void {
        $this->compare(QueryOperator::GreaterOrEqual, $date, $condition);
    }

    /**
     * Generates a Less Than Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function lessThan(Date $date, ?bool $condition = null): void {
        $this->compare(QueryOperator::LessThan, $date, $condition);
    }

    /**
     * Generates a Less or Equal Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function lessOrEqual(Date $date, ?bool $condition = null): void {
        $this->compare(QueryOperator::LessOrEqual, $date, $condition);
    }



    /**
     * Uses the Period to add a Between expression
     * @param Period|Request $period
     * @param string         $prefix Optional.
     * @return void
     */
    public function inPeriod(Period|Request $period, string $prefix = ""): void {
        if ($period instanceof Request) {
            $period = new Period($period, $prefix);
        }

        $this->greaterOrEqual($period->fromTime);
        $this->lessOrEqual($period->toTime);
    }
}
