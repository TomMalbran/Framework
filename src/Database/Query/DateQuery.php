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
     * @param Date          $value
     * @param bool|null     $condition Optional.
     * @return Query
     */
    public function compare(QueryOperator $operator, Date $value, ?bool $condition = null): Query {
        if ($value->isEmpty()) {
            return $this->query;
        }
        return $this->query->add(
            $this->column,
            $operator,
            $value->toTime(),
            condition: $condition,
        );
    }

    /**
     * Generates a Compare If Query
     * @param QueryOperator $operator
     * @param Date          $value
     * @param bool|null     $condition Optional.
     * @return Query
     */
    public function compareIf(QueryOperator $operator, Date $value, ?bool $condition = null): Query {
        return $this->query->addIf(
            $this->column,
            $operator,
            $value->toTime(),
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
     * @param Date $value
     * @return Query
     */
    public function equal(Date $value): Query {
        return $this->compare(QueryOperator::Equal, $value);
    }

    /**
     * Generates an Equal If Query
     * @param Date      $value
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function equalIf(Date $value, ?bool $condition = null): Query {
        return $this->compareIf(QueryOperator::Equal, $value, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param Date $value
     * @return Query
     */
    public function notEqual(Date $value): Query {
        return $this->compare(QueryOperator::NotEqual, $value);
    }

    /**
     * Generates a Not Equal If Query
     * @param Date      $value
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function notEqualIf(Date $value, ?bool $condition = null): Query {
        return $this->compareIf(QueryOperator::NotEqual, $value, $condition);
    }



    /**
     * Generates a Greater Than Query
     * @param Date      $value
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function greaterThan(Date $value, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::GreaterThan, $value, $condition);
    }

    /**
     * Generates a Greater or Equal Query
     * @param Date      $value
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function greaterOrEqual(Date $value, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::GreaterOrEqual, $value, $condition);
    }

    /**
     * Generates a Less Than Query
     * @param Date      $value
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function lessThan(Date $value, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::LessThan, $value, $condition);
    }

    /**
     * Generates a Less or Equal Query
     * @param Date      $value
     * @param bool|null $condition Optional.
     * @return Query
     */
    public function lessOrEqual(Date $value, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::LessOrEqual, $value, $condition);
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
