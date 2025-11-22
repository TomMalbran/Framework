<?php
namespace Framework\Database\Query;

use Framework\Database\Query\BaseQuery;
use Framework\Database\Query\Query;
use Framework\Database\Query\QueryOperator;

/**
 * The String Query
 */
class StringQuery extends BaseQuery {

    /**
     * Adds a Search expression
     * @param mixed         $value
     * @param QueryOperator $operator        Optional.
     * @param boolean       $caseInsensitive Optional.
     * @param boolean       $splitValue      Optional.
     * @param string        $splitText       Optional.
     * @param boolean       $matchAny        Optional.
     * @return Query
     */
    public function search(
        mixed $value,
        QueryOperator $operator = QueryOperator::Like,
        bool $caseInsensitive = true,
        bool $splitValue = false,
        string $splitText = " ",
        bool $matchAny = false,
    ): Query {
        return $this->query->search(
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
     * Generates a Compare Query
     * @param QueryOperator   $operator
     * @param string[]|string $value
     * @param boolean         $caseSensitive Optional.
     * @param boolean|null    $condition     Optional.
     * @return Query
     */
    public function compare(
        QueryOperator $operator,
        array|string $value,
        bool $caseSensitive = false,
        ?bool $condition = null,
    ): Query {
        return $this->query->add(
            $this->column,
            $operator,
            $value,
            $caseSensitive,
            $condition,
        );
    }

    /**
     * Generates a Compare If Query
     * @param QueryOperator   $operator
     * @param string[]|string $value
     * @param boolean|null    $condition     Optional.
     * @param boolean         $caseSensitive Optional.
     * @return Query
     */
    public function compareIf(
        QueryOperator $operator,
        array|string $value,
        ?bool $condition = null,
        bool $caseSensitive = false,
    ): Query {
        return $this->query->addIf(
            $this->column,
            $operator,
            $value,
            $condition,
            $caseSensitive,
        );
    }



    /**
     * Generates an Equal Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function equal(string $value, bool $caseSensitive = false): Query {
        return $this->compare(QueryOperator::Equal, $value, $caseSensitive);
    }

    /**
     * Generates an Equal If Query
     * @param string       $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function equalIf(string $value, ?bool $condition = null): Query {
        return $this->compareIf(QueryOperator::Equal, $value, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param string $value
     * @return Query
     */
    public function notEqual(string $value): Query {
        return $this->compare(QueryOperator::NotEqual, $value);
    }

    /**
     * Generates a Not Equal If Query
     * @param string       $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notEqualIf(string $value, ?bool $condition = null): Query {
        return $this->compareIf(QueryOperator::NotEqual, $value, $condition);
    }



    /**
     * Generates a Like Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function like(string $value, bool $caseSensitive = false): Query {
        return $this->compare(QueryOperator::Like, $value, $caseSensitive);
    }

    /**
     * Generates a Like If Query
     * @param string       $value
     * @param boolean|null $condition     Optional.
     * @param boolean      $caseSensitive Optional.
     * @return Query
     */
    public function likeIf(string $value, ?bool $condition = null, bool $caseSensitive = false): Query {
        return $this->compareIf(QueryOperator::Like, $value, $condition, $caseSensitive);
    }

    /**
     * Generates a Not Like Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function notLike(string $value, bool $caseSensitive = false): Query {
        return $this->compare(QueryOperator::NotLike, $value, $caseSensitive);
    }

    /**
     * Generates a Not Like If Query
     * @param string       $value
     * @param boolean|null $condition     Optional.
     * @param boolean      $caseSensitive Optional.
     * @return Query
     */
    public function notLikeIf(string $value, ?bool $condition = null, bool $caseSensitive = false): Query {
        return $this->compareIf(QueryOperator::NotLike, $value, $condition, $caseSensitive);
    }

    /**
     * Generates a Starts With Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function startsWith(string $value, bool $caseSensitive = false): Query {
        return $this->compare(QueryOperator::StartsWith, $value, $caseSensitive);
    }

    /**
     * Generates a Ends With Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function endsWith(string $value, bool $caseSensitive = false): Query {
        return $this->compare(QueryOperator::EndsWith, $value, $caseSensitive);
    }



    /**
     * Generates an In Query
     * @param string[]     $values
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function in(array $values, ?bool $condition = null): Query {
        if (count($values) === 0) {
            return $this->query;
        }
        return $this->compare(QueryOperator::In, $values, false, $condition);
    }

    /**
     * Generates a Not In Query
     * @param string[]     $values
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notIn(array $values, ?bool $condition = null): Query {
        if (count($values) === 0) {
            return $this->query;
        }
        return $this->compare(QueryOperator::NotIn, $values, false, $condition);
    }
}
