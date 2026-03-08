<?php
namespace Framework\Database\Where;

use Framework\Database\Query\Operator;
use Framework\Database\Where\BaseWhere;
use Framework\Enum\Enum;

/**
 * The Enum Where
 */
class EnumWhere extends BaseWhere {

    /**
     * Adds a Is Empty condition
     * @return void
     */
    public function isEmpty(): void {
        $this->query->where($this->column, Operator::NotEqual, "");
    }

    /**
     * Adds a Is Not Empty condition
     * @return void
     */
    public function isNotEmpty(): void {
        $this->query->where($this->column, Operator::NotEqual, "");
    }

    /**
     * Adds an Equals condition
     * @param Enum ...$values
     * @return void
     */
    public function equal(Enum ...$values): void {
        $names = $this->toNames(array_values($values));
        $this->query->where($this->column, Operator::Equal, $names);
    }

    /**
     * Adds a Not Equals condition
     * @param Enum ...$values
     * @return void
     */
    public function notEqual(Enum ...$values): void {
        $names = $this->toNames(array_values($values));
        $this->query->where($this->column, Operator::NotEqual, $names);
    }

    /**
     * Adds an In condition
     * @param list<Enum> $values
     * @return void
     */
    public function in(array $values): void {
        $names = $this->toNames($values);
        $this->query->where($this->column, Operator::In, $names);
    }

    /**
     * Adds a Not In condition
     * @param list<Enum> $values
     * @return void
     */
    public function notIn(array $values): void {
        $names = $this->toNames($values);
        $this->query->where($this->column, Operator::NotIn, $names);
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
