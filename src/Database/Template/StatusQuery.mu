<?php
namespace {{namespace}};

use {{namespace}}\{{statusClass}};

use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\QueryOperator;

/**
 * The {{name}} Status Query
 */
class {{statusQueryClass}} extends BaseQuery {

    /**
     * Adds a {{name}} Equals condition
     * @param {{statusClass}} ...$statuses
     * @return {{statusQueryClass}}
     */
    public function equal({{statusClass}} ...$statuses): {{statusQueryClass}} {
        $values = {{statusClass}}::toNames($statuses);
        $this->query->add($this->column, QueryOperator::Equal, $values);
        return $this;
    }

    /**
     * Adds a {{name}} Not Equals condition
     * @param {{statusClass}} ...$statuses
     * @return {{statusQueryClass}}
     */
    public function notEqual({{statusClass}} ...$statuses): {{statusQueryClass}} {
        $values = {{statusClass}}::toNames($statuses);
        $this->query->add($this->column, QueryOperator::NotEqual, $values);
        return $this;
    }

    /**
     * Adds a {{name}} In condition
     * @param {{statusClass}}[] $statuses
     * @return {{statusQueryClass}}
     */
    public function in(array $statuses): {{statusQueryClass}} {
        $values = {{statusClass}}::toNames($statuses);
        $this->query->add($this->column, QueryOperator::In, $values);
        return $this;
    }

    /**
     * Adds a {{name}} Not In condition
     * @param {{statusClass}}[] $statuses
     * @return {{statusQueryClass}}
     */
    public function notIn(array $statuses): {{statusQueryClass}} {
        $values = {{statusClass}}::toNames($statuses);
        $this->query->add($this->column, QueryOperator::NotIn, $values);
        return $this;
    }
}
