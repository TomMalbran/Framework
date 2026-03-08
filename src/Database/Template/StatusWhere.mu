<?php
namespace {{namespace}};

use {{namespace}}\{{statusClass}};

use Framework\Database\Query\Operator;
use Framework\Database\Where\BaseWhere;

/**
 * The {{name}} Status Where
 */
class {{statusWhereClass}} extends BaseWhere {

    /**
     * Adds a {{name}} Equals condition
     * @param {{statusClass}} ...$statuses
     * @return {{statusWhereClass}}
     */
    public function equal({{statusClass}} ...$statuses): {{statusWhereClass}} {
        $values = {{statusClass}}::toNames(array_values($statuses));
        $this->query->where($this->column, Operator::Equal, $values);
        return $this;
    }

    /**
     * Adds a {{name}} Not Equals condition
     * @param {{statusClass}} ...$statuses
     * @return {{statusWhereClass}}
     */
    public function notEqual({{statusClass}} ...$statuses): {{statusWhereClass}} {
        $values = {{statusClass}}::toNames(array_values($statuses));
        $this->query->where($this->column, Operator::NotEqual, $values);
        return $this;
    }

    /**
     * Adds a {{name}} In condition
     * @param list<{{statusClass}}> $statuses
     * @return {{statusWhereClass}}
     */
    public function in(array $statuses): {{statusWhereClass}} {
        $values = {{statusClass}}::toNames($statuses);
        $this->query->where($this->column, Operator::In, $values);
        return $this;
    }

    /**
     * Adds a {{name}} Not In condition
     * @param list<{{statusClass}}> $statuses
     * @return {{statusWhereClass}}
     */
    public function notIn(array $statuses): {{statusWhereClass}} {
        $values = {{statusClass}}::toNames($statuses);
        $this->query->where($this->column, Operator::NotIn, $values);
        return $this;
    }
}
