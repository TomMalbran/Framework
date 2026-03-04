<?php
namespace Framework\Database\Query;

use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\QueryOperator;
use Framework\Enum\Enum;

/**
 * The Enum Query
 */
class EnumQuery extends BaseQuery {

    /**
     * Adds an Enum Is Empty condition
     * @return void
     */
    public function isEmpty(): void {
        $this->query->where($this->column, QueryOperator::NotEqual, "");
    }

    /**
     * Generates an Is Not Empty Query
     * @return void
     */
    public function isNotEmpty(): void {
        $this->query->where($this->column, QueryOperator::NotEqual, "");
    }

    /**
     * Adds an Enum Equals condition
     * @param Enum ...$values
     * @return void
     */
    public function equal(Enum ...$values): void {
        $names = $this->toNames(array_values($values));
        $this->query->where($this->column, QueryOperator::Equal, $names);
    }

    /**
     * Adds an Enum Not Equals condition
     * @param Enum ...$values
     * @return void
     */
    public function notEqual(Enum ...$values): void {
        $names = $this->toNames(array_values($values));
        $this->query->where($this->column, QueryOperator::NotEqual, $names);
    }

    /**
     * Adds an Enum In condition
     * @param list<Enum> $values
     * @return void
     */
    public function in(array $values): void {
        $names = $this->toNames($values);
        $this->query->where($this->column, QueryOperator::In, $names);
    }

    /**
     * Adds an Enum Not In condition
     * @param list<Enum> $values
     * @return void
     */
    public function notIn(array $values): void {
        $names = $this->toNames($values);
        $this->query->where($this->column, QueryOperator::NotIn, $names);
    }



    /**
     * Creates a list of Names from the given Enums
     * @param list<Enum> $values
     * @return list<string>
     */
    private function toNames(array $values): array {
        $result = [];
        foreach ($values as $value) {
            $value = $value->toString();
            if ($value !== "") {
                $result[] = $value;
            }
        }
        return $result;
    }
}
