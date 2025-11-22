<?php
namespace Framework\Database\Query;

use Framework\Request;
use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\Query;
use Framework\Database\Query\QueryOperator;
use Framework\Date\Period;

/**
 * The Number Query
 */
class NumberQuery extends BaseQuery {

    /**
     * Generates a Compare Query
     * @param QueryOperator     $operator
     * @param integer[]|integer $value
     * @param boolean|null      $condition Optional.
     * @return Query
     */
    public function compare(QueryOperator $operator, array|int $value, ?bool $condition = null): Query {
        return $this->query->add(
            $this->column,
            $operator,
            $value,
            condition: $condition,
        );
    }

    /**
     * Generates a Compare If Query
     * @param QueryOperator     $operator
     * @param integer[]|integer $value
     * @param boolean|null      $condition Optional.
     * @return Query
     */
    public function compareIf(QueryOperator $operator, array|int $value, ?bool $condition = null): Query {
        return $this->query->addIf(
            $this->column,
            $operator,
            $value,
            $condition,
        );
    }



    /**
     * Generates an Equal Query
     * @param integer $value
     * @return Query
     */
    public function equal(int $value): Query {
        return $this->compare(QueryOperator::Equal, $value);
    }

    /**
     * Generates an Equal If Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function equalIf(int $value, ?bool $condition = null): Query {
        return $this->compareIf(QueryOperator::Equal, $value, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param integer $value
     * @return Query
     */
    public function notEqual(int $value): Query {
        return $this->compare(QueryOperator::NotEqual, $value);
    }

    /**
     * Generates a Not Equal If Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notEqualIf(int $value, ?bool $condition = null): Query {
        return $this->compareIf(QueryOperator::NotEqual, $value, $condition);
    }



    /**
     * Generates a Greater Than Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function greaterThan(int $value, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::GreaterThan, $value, $condition);
    }

    /**
     * Generates a Greater or Equal Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function greaterOrEqual(int $value, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::GreaterOrEqual, $value, $condition);
    }

    /**
     * Generates a Less Than Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function lessThan(int $value, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::LessThan, $value, $condition);
    }

    /**
     * Generates a Less or Equal Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function lessOrEqual(int $value, ?bool $condition = null): Query {
        return $this->compare(QueryOperator::LessOrEqual, $value, $condition);
    }



    /**
     * Generates an In Query
     * @param integer[]    $values
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function in(array $values, ?bool $condition = null): Query {
        if (count($values) === 0) {
            return $this->query;
        }
        return $this->compare(QueryOperator::In, $values, $condition);
    }

    /**
     * Generates a Not In Query
     * @param integer[]    $values
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notIn(array $values, ?bool $condition = null): Query {
        if (count($values) === 0) {
            return $this->query;
        }
        return $this->compare(QueryOperator::NotIn, $values, $condition);
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

        if ($period->fromTime > 0) {
            $this->compare(QueryOperator::GreaterOrEqual, $period->fromTime);
        }
        if ($period->toTime > 0) {
            $this->compare(QueryOperator::LessOrEqual, $period->toTime);
        }
        return $this->query;
    }
}
