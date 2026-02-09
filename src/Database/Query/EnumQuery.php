<?php
namespace Framework\Database\Query;

use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\QueryOperator;
use Framework\Database\Type\Enum;

/**
 * The Enum Query
 */
class EnumQuery extends BaseQuery {

    /**
     * Adds an Enum Is Empty condition
     * @return void
     */
    public function isEmpty() {
        $this->query->add($this->column, QueryOperator::NotEqual, "");
    }

    /**
     * Generates an Is Not Empty Query
     * @return void
     */
    public function isNotEmpty() {
        $this->query->add($this->column, QueryOperator::NotEqual, 0);
    }

    /**
     * Adds an Enum Equals condition
     * @param Enum ...$values
     * @return void
     */
    public function equal(Enum ...$values) {
        $names = $this->toNames($values);
        $this->query->add($this->column, QueryOperator::Equal, $names);
    }

    /**
     * Adds an Enum Not Equals condition
     * @param Enum ...$values
     * @return void
     */
    public function notEqual(Enum ...$values) {
        $names = $this->toNames($values);
        $this->query->add($this->column, QueryOperator::NotEqual, $names);
    }

    /**
     * Adds an Enum In condition
     * @param Enum[] $values
     * @return void
     */
    public function in(array $values) {
        $names = $this->toNames($values);
        $this->query->add($this->column, QueryOperator::In, $names);
    }

    /**
     * Adds an Enum Not In condition
     * @param Enum[] $values
     * @return void
     */
    public function notIn(array $values) {
        $names = $this->toNames($values);
        $this->query->add($this->column, QueryOperator::NotIn, $names);
    }



    /**
     * Creates a list of Names from the given Enums
     * @param Enum[] $values
     * @return string[]
     */
    private function toNames(array $values): array {
        $result = [];
        foreach ($values as $value) {
            $name = $value->toString();
            if ($name !== "None") {
                $result[] = $name;
            }
        }
        return $result;
    }
}
