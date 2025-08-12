<?php
namespace Framework\Database\Query;

use Framework\Request;
use Framework\Database\Query;
use Framework\Database\Query\BaseQuery;
use Framework\Date\Period;

/**
 * The Number Query
 */
class NumberQuery extends BaseQuery {

    /**
     * Generates an Equal Query
     * @param integer $value
     * @return Query
     */
    public function equal(int $value): Query {
        return $this->query->add($this->column, "=", $value);
    }

    /**
     * Generates an Equal If Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function equalIf(int $value, ?bool $condition = null): Query {
        return $this->query->addIf($this->column, "=", $value, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param integer $value
     * @return Query
     */
    public function notEqual(int $value): Query {
        return $this->query->add($this->column, "<>", $value);
    }

    /**
     * Generates a Not Equal If Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notEqualIf(int $value, ?bool $condition = null): Query {
        return $this->query->addIf($this->column, "<>", $value, $condition);
    }



    /**
     * Generates a Greater Than Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function greaterThan(int $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, ">", $value, condition: $condition);
    }

    /**
     * Generates a Greater or Equal Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function greaterOrEqual(int $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, ">=", $value, condition: $condition);
    }

    /**
     * Generates a Less Than Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function lessThan(int $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, "<", $value, condition: $condition);
    }

    /**
     * Generates a Less or Equal Query
     * @param integer      $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function lessOrEqual(int $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, "<=", $value, condition: $condition);
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
        return $this->query->add($this->column, "IN", $values, condition: $condition);
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
        return $this->query->add($this->column, "NOT IN", $values, condition: $condition);
    }

    /**
     * Uses the Period to add a Between expression
     * @param Period|Request $period
     * @return Query
     */
    public function inPeriod(Period|Request $period): Query {
        return $this->query->addPeriod($this->column, $period);
    }
}
