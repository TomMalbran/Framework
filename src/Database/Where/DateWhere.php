<?php
namespace Framework\Database\Where;

use Framework\IO\Request;
use Framework\Database\Query\Op;
use Framework\Database\Where\BaseWhere;
use Framework\Database\Type\SchemaRequest;
use Framework\Date\Date;
use Framework\Date\Period;

/**
 * The Date Where
 */
class DateWhere extends BaseWhere {

    /**
     * Adds a Compare condition
     * @param Op        $operator
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function compare(
        Op $operator,
        Date $date,
        ?bool $condition = null,
    ): void {
        if ($date->isNotEmpty()) {
            $this->query->where(
                $this->column,
                $operator,
                $date->toTime(),
                condition: $condition,
            );
        }
    }

    /**
     * Adds a Compare If condition
     * @param Op        $operator
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function compareIf(
        Op $operator,
        Date $date,
        ?bool $condition = null,
    ): void {
        $this->query->whereIf(
            $this->column,
            $operator,
            $date->toTime(),
            $condition,
        );
    }



    /**
     * Adds an Is Empty condition
     * @return void
     */
    public function isEmpty(): void {
        $this->query->where($this->column, Op::Equal, 0);
    }

    /**
     * Adds an Is Not Empty condition
     * @return void
     */
    public function isNotEmpty(): void {
        $this->query->where($this->column, Op::NotEqual, 0);
    }

    /**
     * Adds an Equal condition
     * @param Date $date
     * @return void
     */
    public function equal(Date $date): void {
        $this->compare(Op::Equal, $date);
    }

    /**
     * Adds an Equal If condition
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalIf(Date $date, ?bool $condition = null): void {
        $this->compareIf(Op::Equal, $date, $condition);
    }

    /**
     * Adds a Not Equal condition
     * @param Date $date
     * @return void
     */
    public function notEqual(Date $date): void {
        $this->compare(Op::NotEqual, $date);
    }

    /**
     * Adds a Not Equal If condition
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function notEqualIf(Date $date, ?bool $condition = null): void {
        $this->compareIf(Op::NotEqual, $date, $condition);
    }



    /**
     * Adds a Greater Than condition
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function greaterThan(Date $date, ?bool $condition = null): void {
        $this->compare(Op::GreaterThan, $date, $condition);
    }

    /**
     * Adds a Greater or Equal condition
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function greaterOrEqual(Date $date, ?bool $condition = null): void {
        $this->compare(Op::GreaterOrEqual, $date, $condition);
    }

    /**
     * Adds a Less Than condition
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function lessThan(Date $date, ?bool $condition = null): void {
        $this->compare(Op::LessThan, $date, $condition);
    }

    /**
     * Adds a Less or Equal condition
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return void
     */
    public function lessOrEqual(Date $date, ?bool $condition = null): void {
        $this->compare(Op::LessOrEqual, $date, $condition);
    }



    /**
     * Uses the Period to add a Between condition
     * @param Period|SchemaRequest|Request $period
     * @param string                       $prefix Optional.
     * @return void
     */
    public function inPeriod(
        Period|SchemaRequest|Request $period,
        string $prefix = "",
    ): void {
        if ($period instanceof Request || $period instanceof SchemaRequest) {
            $period = new Period($period, $prefix);
        }

        $this->greaterOrEqual($period->fromTime);
        $this->lessOrEqual($period->toTime);
    }
}
