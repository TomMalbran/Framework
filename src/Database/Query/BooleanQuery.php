<?php
namespace Framework\Database\Query;

use Framework\Database\Query;
use Framework\Database\Query\BaseQuery;

/**
 * The Boolean Query
 */
class BooleanQuery extends BaseQuery {

    /**
     * Generates an Equal Query
     * @param bool         $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function equal(bool $value, ?bool $condition = null): Query {
        return $this->query->add($this->column, "=", (int)$value, condition: $condition);
    }



    /**
     * Generates an Any Query
     * @return Query
     */
    public function isAny(): Query {
        return $this->query->add($this->column, ">=", 0);
    }

    /**
     * Generates a True Query
     * @return Query
     */
    public function isTrue(): Query {
        return $this->query->add($this->column, "=", 1);
    }

    /**
     * Generates a False Query
     * @return Query
     */
    public function isFalse(): Query {
        return $this->query->add($this->column, "=", 0);
    }
}
