<?php
namespace Framework\Database\Query;

use Framework\Request;
use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\Query;
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
     * @return Query
     */
    public function compare(QueryOperator $operator, Date $date, ?bool $condition = null): Query {
        if ($date->isEmpty()) {
            return $this->query;
        }
        return $this->query->add(
            $this->column,
            $operator,
            $date->toTime(),
            condition: $condition,
        );
    }

    /**
     * Generates a Compare If Query
     * @param QueryOperator $operator
     * @param Date          $date
     * @param bool|null     $condition Optional.
     * @return Query
     */
    public function compareIf(QueryOperator $operator, Date $date, ?bool $condition = null): Query {
        return $this->query->addIf(
            $this->column,
            $operator,
            $date->toTime(),
            $condition,
        );
    }



    /**
     * Generates an Is Empty Query
     * @return Query
     */
    public function isEmpty(): Query {
        return $this->query->add($this->column, QueryOperator::Equal, 0);
    }

    /**
     * Generates an Is Not Empty Query
     * @return Query
     */
    public function isNotEmpty(): Query {
        return $this->query->add($this->column, QueryOperator::NotEqual, 0);
    }

    /**
     * Generates an Equal Query
     * @param Date $date
     * @return Query
     */
    public function equal(Date $date): Query {
        return $this->compare(QueryOperator::Equal, $date);
    }

    /**
     * Generates an Equal If Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function equalIf(Date $date, ?bool $condition = null): Query {
        return $this->compareIf(QueryOperator::Equal, $date, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param Date $date
     * @return Query
     */
    public function notEqual(Date $date): Query {
        return $this->compare(QueryOperator::NotEqual, $date);
    }

    /**
     * Generates a Not Equal If Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function notEqualIf(Date $date, ?bool $condition = null): Query {
        return $this->compareIf(QueryOperator::NotEqual, $date, $condition);
    }



    /**
     * Generates a Greater Than Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function greaterThan(Date $date, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::GreaterThan, $date, $condition);
    }

    /**
     * Generates a Greater or Equal Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function greaterOrEqual(Date $date, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::GreaterOrEqual, $date, $condition);
    }

    /**
     * Generates a Less Than Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function lessThan(Date $date, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::LessThan, $date, $condition);
    }

    /**
     * Generates a Less or Equal Query
     * @param Date      $date
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function lessOrEqual(Date $date, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::LessOrEqual, $date, $condition);
    }



    /**
     * Uses the Period to add a Between expression
     * @param Period|Request $period
     * @param string         $prefix Optional.
     * @return Query
     */
    public function inPeriod(Period|Request $period, string $prefix = ""): Query {
        if ($period instanceof Request) {
            $period = new Period($period, $prefix);
        }

        $this->greaterOrEqual($period->fromTime);
        $this->lessOrEqual($period->toTime);
        return $this->query;
    }
}
