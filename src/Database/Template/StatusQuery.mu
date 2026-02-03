<?php
namespace {{namespace}};

use {{namespace}}\{{status}};

use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\QueryOperator;

/**
 * The {{name}} Status Query
 */
class {{query}} extends BaseQuery {

    /**
     * Adds a {{name}} Equals condition
     * @param {{status}} ...$statuses
     * @return {{query}}
     */
    public function equal({{status}} ...$statuses): {{query}} {
        $values = {{status}}::toNames($statuses);
        $this->query->add($this->column, QueryOperator::Equal, $values);
        return $this;
    }

    /**
     * Adds a {{name}} Not Equals condition
     * @param {{status}} ...$statuses
     * @return {{query}}
     */
    public function notEqual({{status}} ...$statuses): {{query}} {
        $values = {{status}}::toNames($statuses);
        $this->query->add($this->column, QueryOperator::NotEqual, $values);
        return $this;
    }
}
