<?php
namespace Framework\Database\Query;

use Framework\Database\Query;

/**
 * The Schema Query
 */
class SchemaQuery {

    public Query $query;


    /**
     * Creates a new SchemaQuery instance
     * @param Query|null $query Optional.
     */
    public function __construct(?Query $query = null) {
        $this->query = new Query($query);
    }

    /**
     * Returns true if the Query is empty
     * @return boolean
     */
    public function isEmpty(): bool {
        return $this->query->isEmpty();
    }

    /**
     * Creates a list of question marks for the given array
     * @param mixed[] $array
     * @return string
     */
    public function createBinds(array $array): string {
        return $this->query->createBinds($array);
    }



    /**
     * Adds a param to the Query
     * @param mixed $param
     * @return Query
     */
    public function addParam(mixed $param): Query {
        return $this->query->addParam($param);
    }

    /**
     * Adds an Expression to the Query
     * @param string $expression
     * @param mixed  ...$values
     * @return Query
     */
    public function addExp(string $expression, mixed ...$values): Query {
        return $this->query->addExp($expression, ...$values);
    }



    /**
     * Adds a Open Parenthesis
     * @return Query
     */
    public function startParen(): Query {
        return $this->query->startParen();
    }

    /**
     * Adds a Close Parenthesis
     * @return Query
     */
    public function endParen(): Query {
        return $this->query->endParen();
    }

    /**
     * Adds an And
     * @return Query
     */
    public function and(): Query {
        return $this->query->and();
    }

    /**
     * Starts an And expression
     * @return Query
     */
    public function startAnd(): Query {
        return $this->query->startAnd();
    }

    /**
     * Ends an And expression
     * @return Query
     */
    public function endAnd(): Query {
        return $this->query->endAnd();
    }

    /**
     * Adds an Or
     * @return Query
     */
    public function or(): Query {
        return $this->query->or();
    }

    /**
     * Starts an Or expression
     * @return Query
     */
    public function startOr(): Query {
        return $this->query->startOr();
    }

    /**
     * Ends an Or expression
     * @return Query
     */
    public function endOr(): Query {
        return $this->query->endOr();
    }



    /**
     * Adds an Limit
     * @param integer      $from
     * @param integer|null $to   Optional.
     * @return Query
     */
    public function limit(int $from, ?int $to = null): Query {
        if ($from !== 0 || ($to !== null && $to !== 0)) {
            return $this->query->limit($from, $to);
        }
        return $this->query;
    }

    /**
     * Adds a limit using pagination
     * @param integer $page   Optional.
     * @param integer $amount Optional.
     * @return Query
     */
    public function paginate(int $page = 0, int $amount = 100): Query {
        return $this->query->paginate($page, $amount);
    }
}
