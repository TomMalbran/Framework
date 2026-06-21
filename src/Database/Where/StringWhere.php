<?php
namespace Framework\Database\Where;

use Framework\Database\Query\Operator;
use Framework\Database\Where\BaseWhere;

/**
 * The String Where
 */
class StringWhere extends BaseWhere {

    /**
     * Adds a Search condition
     * @param mixed    $value
     * @param Operator $operator        Optional.
     * @param bool     $caseInsensitive Optional.
     * @param bool     $splitValue      Optional.
     * @param string   $splitText       Optional.
     * @param bool     $matchAny        Optional.
     * @return void
     */
    public function search(
        mixed $value,
        Operator $operator = Operator::Like,
        bool $caseInsensitive = true,
        bool $splitValue = false,
        string $splitText = " ",
        bool $matchAny = false,
    ): void {
        $this->query->search(
            $this->column,
            $value,
            $operator,
            $caseInsensitive,
            $splitValue,
            $splitText,
            $matchAny
        );
    }

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
     * Adds a Compare If condition
     * @param Operator            $operator
     * @param list<string>|string $value
     * @param bool|null           $condition     Optional.
     * @param bool                $caseSensitive Optional.
     * @return void
     */
    public function compareIf(
        Operator $operator,
        array|string $value,
        ?bool $condition = null,
        bool $caseSensitive = false,
    ): void {
        $this->query->whereIf(
            $this->column,
            $operator,
            $value,
            $condition,
            $caseSensitive,
        );
    }



    /**
     * Adds an Equal condition
     * @param string $value
     * @param bool   $caseSensitive Optional.
     * @return void
     */
    public function equal(string $value, bool $caseSensitive = false): void {
        $this->compare(Operator::Equal, $value, $caseSensitive);
    }

    /**
     * Adds an Equal If condition
     * @param string    $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function equalIf(string $value, ?bool $condition = null): void {
        $this->compareIf(Operator::Equal, $value, $condition);
    }

    /**
     * Adds a Not Equal condition
     * @param string $value
     * @return void
     */
    public function notEqual(string $value): void {
        $this->compare(Operator::NotEqual, $value);
    }

    /**
     * Adds a Not Equal If condition
     * @param string    $value
     * @param bool|null $condition Optional.
     * @return void
     */
    public function notEqualIf(string $value, ?bool $condition = null): void {
        $this->compareIf(Operator::NotEqual, $value, $condition);
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
     * Adds a Like If condition
     * @param string    $value
     * @param bool|null $condition     Optional.
     * @param bool      $caseSensitive Optional.
     * @return void
     */
    public function likeIf(
        string $value,
        ?bool $condition = null,
        bool $caseSensitive = false,
    ): void {
        $this->compareIf(Operator::Like, $value, $condition, $caseSensitive);
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
     * Adds a Not Like If condition
     * @param string    $value
     * @param bool|null $condition     Optional.
     * @param bool      $caseSensitive Optional.
     * @return void
     */
    public function notLikeIf(
        string $value,
        ?bool $condition = null,
        bool $caseSensitive = false,
    ): void {
        $this->compareIf(Operator::NotLike, $value, $condition, $caseSensitive);
    }

    /**
     * Adds a Starts With condition
     * @param string $value
     * @param bool   $caseSensitive Optional.
     * @return void
     */
    public function startsWith(string $value, bool $caseSensitive = false): void {
        $this->compare(Operator::StartsWith, $value, $caseSensitive);
    }

    /**
     * Adds an Ends With condition
     * @param string $value
     * @param bool   $caseSensitive Optional.
     * @return void
     */
    public function endsWith(string $value, bool $caseSensitive = false): void {
        $this->compare(Operator::EndsWith, $value, $caseSensitive);
    }



    /**
     * Adds an In condition
     * @param list<string> $values
     * @param bool|null    $condition     Optional.
     * @param bool         $caseSensitive Optional.
     * @return void
     */
    public function in(
        array $values,
        ?bool $condition = null,
        bool $caseSensitive = false,
    ): void {
        if (count($values) > 0) {
            $this->compare(Operator::In, $values, $caseSensitive, $condition);
        }
    }

    /**
     * Adds a Not In condition
     * @param list<string> $values
     * @param bool|null    $condition     Optional.
     * @param bool         $caseSensitive Optional.
     * @return void
     */
    public function notIn(
        array $values,
        ?bool $condition = null,
        bool $caseSensitive = false,
    ): void {
        if (count($values) > 0) {
            $this->compare(Operator::NotIn, $values, $caseSensitive, $condition);
        }
    }
}
