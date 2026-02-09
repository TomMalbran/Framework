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
