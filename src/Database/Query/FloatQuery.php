<?php
namespace Framework\Database\Query;

use Framework\Request;
use Framework\Database\Query;
use Framework\Database\Query\BaseQuery;

/**
 * The Float Query
 */
class FloatQuery extends BaseQuery {

    /**
     * Generates an Equal Query
     * @param float $value
     * @return Query
     */
    public function equal(float $value): Query {
        return $this->query->add($this->column, "=", $value);
    }

    /**
     * Generates an Equal If Query
     * @param float        $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function equalIf(float $value, ?bool $condition = null): Query {
        return $this->query->addIf($this->column, "=", $value, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param float $value
     * @return Query
     */
    public function notEqual(float $value): Query {
        return $this->query->add($this->column, "<>", $value);
    }

    /**
     * Generates a Not Equal If Query
     * @param float        $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notEqualIf(float $value, ?bool $condition = null): Query {
        return $this->query->addIf($this->column, "<>", $value, $condition);
    }



    /**
     * Generates a Greater Than Query
     * @param float        $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function greaterThan(float $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, ">", $value, condition: $condition);
    }

    /**
     * Generates a Greater or Equal Query
     * @param float        $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function greaterOrEqual(float $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, ">=", $value, condition: $condition);
    }

    /**
     * Generates a Less Than Query
     * @param float        $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function lessThan(float $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, "<", $value, condition: $condition);
    }

    /**
     * Generates a Less or Equal Query
     * @param float        $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function lessOrEqual(float $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, "<=", $value, condition: $condition);
    }
}
