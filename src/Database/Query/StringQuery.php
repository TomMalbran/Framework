<?php
namespace Framework\Database\Query;

use Framework\Database\Query;
use Framework\Database\Query\BaseQuery;

/**
 * The String Query
 */
class StringQuery extends BaseQuery {

    /**
     * Adds a Search expression
     * @param mixed   $value
     * @param string  $expression      Optional.
     * @param boolean $caseInsensitive Optional.
     * @param boolean $splitValue      Optional.
     * @param string  $splitText       Optional.
     * @param boolean $matchAny        Optional.
     * @return Query
     */
    public function search(
        mixed  $value,
        string $expression = "LIKE",
        bool   $caseInsensitive = true,
        bool   $splitValue = false,
        string $splitText = " ",
        bool   $matchAny = false,
    ): Query {
        return $this->query->search(
            $this->column,
            $value,
            $expression,
            $caseInsensitive,
            $splitValue,
            $splitText,
            $matchAny
        );
    }

    /**
     * Generates an Equal Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function equal(string $value, bool $caseSensitive = false): Query {
        return $this->query->add($this->column, "=", $value, caseSensitive: $caseSensitive);
    }

    /**
     * Generates an Equal If Query
     * @param string       $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function equalIf(string $value, ?bool $condition = null): Query {
        return $this->query->addIf($this->column, "=", $value, $condition);
    }

    /**
     * Generates a Not Equal Query
     * @param string $value
     * @return Query
     */
    public function notEqual(string $value): Query {
        return $this->query->add($this->column, "<>", $value);
    }

    /**
     * Generates a Not Equal If Query
     * @param string       $value
     * @param boolean|null $condition Optional.
     * @return Query
     */
    public function notEqualIf(string $value, ?bool $condition = null): Query {
        return $this->query->addIf($this->column, "<>", $value, $condition);
    }



    /**
     * Generates a Like Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function like(string $value, bool $caseSensitive = false): Query {
        return $this->query->add($this->column, "LIKE", $value, caseSensitive: $caseSensitive);
    }

    /**
     * Generates a Like If Query
     * @param string       $value
     * @param boolean|null $condition     Optional.
     * @param boolean      $caseSensitive Optional.
     * @return Query
     */
    public function likeIf(string $value, ?bool $condition = null, bool $caseSensitive = false): Query {
        return $this->query->addIf($this->column, "LIKE", $value, $condition, caseSensitive: $caseSensitive);
    }

    /**
     * Generates a Not Like Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function notLike(string $value, bool $caseSensitive = false): Query {
        return $this->query->add($this->column, "NOT LIKE", $value, caseSensitive: $caseSensitive);
    }

    /**
     * Generates a Not Like If Query
     * @param string       $value
     * @param boolean|null $condition     Optional.
     * @param boolean      $caseSensitive Optional.
     * @return Query
     */
    public function notLikeIf(string $value, ?bool $condition = null, bool $caseSensitive = false): Query {
        return $this->query->add($this->column, "NOT LIKE", $value, $condition, caseSensitive: $caseSensitive);
    }

    /**
     * Generates a Starts With Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function startsWith(string $value, bool $caseSensitive = false): Query {
        return $this->query->add($this->column, "STARTS", $value, caseSensitive: $caseSensitive);
    }

    /**
     * Generates a Ends With Query
     * @param string  $value
     * @param boolean $caseSensitive Optional.
     * @return Query
     */
    public function endsWith(string $value, bool $caseSensitive = false): Query {
        return $this->query->add($this->column, "ENDS", $value, caseSensitive: $caseSensitive);
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
        return $this->query->add($this->column, "IN", $values, condition: $condition);
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
        return $this->query->add($this->column, "NOT IN", $values, condition: $condition);
    }
}
