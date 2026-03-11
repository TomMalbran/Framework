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
     * Adds a Compare condition
     * @param Operator            $operator
     * @param list<string>|string $value
     * @param bool                $caseSensitive Optional.
     * @param bool|null           $condition     Optional.
     * @return void
     */
    public function compare(
        Operator $operator,
        array|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): void {
        $this->query->where(
            $this->column,
            $operator,
            $value,
            $caseSensitive,
            $condition,
        );
    }



    /**
     * Adds a Is Empty condition
     * @return void
     */
    public function isEmpty(): void {
        $this->compare(Operator::NotEqual, "");
    }

    /**
     * Adds a Is Not Empty condition
     * @return void
     */
    public function isNotEmpty(): void {
        $this->compare(Operator::NotEqual, "");
    }

    /**
     * Adds an Equals condition
     * @param Enum ...$values
     * @return void
     */
    public function equal(Enum ...$values): void {
        $names = $this->toNames(array_values($values));
        $this->compare(Operator::Equal, $names);
    }

    /**
     * Adds a Not Equals condition
     * @param Enum ...$values
     * @return void
     */
    public function notEqual(Enum ...$values): void {
        $names = $this->toNames(array_values($values));
        $this->compare(Operator::NotEqual, $names);
    }



    /**
     * Adds an Equals condition with the Name of the Enum
     * @param string $name
     * @param bool   $caseSensitive Optional.
     * @return void
     */
    public function equalName(string $name, bool $caseSensitive = false): void {
        $this->compare(Operator::Equal, $name, $caseSensitive);
    }

    /**
     * Adds a Not Equals condition with the Name of the Enum
     * @param string $name
     * @param bool   $caseSensitive Optional.
     * @return void
     */
    public function notEqualName(string $name, bool $caseSensitive = false): void {
        $this->compare(Operator::NotEqual, $name, $caseSensitive);
    }

    /**
     * Adds a Like condition
     * @param string $value
     * @param bool   $caseSensitive Optional.
     * @return void
     */
    public function like(string $value, bool $caseSensitive = false): void {
        $this->compare(Operator::Like, $value, $caseSensitive);
    }

    /**
     * Adds a Not Like condition
     * @param string $value
     * @param bool   $caseSensitive Optional.
     * @return void
     */
    public function notLike(string $value, bool $caseSensitive = false): void {
        $this->compare(Operator::NotLike, $value, $caseSensitive);
    }



    /**
     * Adds an In condition
     * @param list<Enum> $values
     * @return void
     */
    public function in(array $values): void {
        $names = $this->toNames($values);
        $this->compare(Operator::In, $names);
    }

    /**
     * Adds a Not In condition
     * @param list<Enum> $values
     * @return void
     */
    public function notIn(array $values): void {
        $names = $this->toNames($values);
        $this->compare(Operator::NotIn, $names);
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
