<?php
namespace Framework\Database\Query;

use Framework\Database\Query\Query;

/**
 * The Schema Query
 */
class SchemaQuery {

    public Query $query;

    public string $tableName = "";
    public string $idDbName  = "";



    /**
     * Creates a new SchemaQuery instance
     */
    protected function __construct() {
        $this->query = Query::select($this->tableName);
    }

    /**
     * Returns true if the Query is empty
     * @return bool
     */
    public function isEmpty(): bool {
        return $this->query->isEmpty();
    }

    /**
     * Returns true if the Query is not empty
     * @return bool
     */
    public function isNotEmpty(): bool {
        return $this->query->isNotEmpty();
    }



    /**
     * Adds a param to the Query
     * @param int|string $param
     * @return SchemaQuery
     */
    public function addParam(int|string $param): SchemaQuery {
        $this->query->addParam($param);
        return $this;
    }

    /**
     * Adds an Expression to the Query
     * @param string     $expression
     * @param int|string ...$values
     * @return SchemaQuery
     */
    public function whereExp(string $expression, int|string ...$values): SchemaQuery {
        $this->query->whereExp($expression, ...$values);
        return $this;
    }



    /**
     * Adds an Open Parenthesis
     * @return SchemaQuery
     */
    public function startParen(): SchemaQuery {
        $this->query->startParen();
        return $this;
    }

    /**
     * Adds a Close Parenthesis
     * @return SchemaQuery
     */
    public function endParen(): SchemaQuery {
        $this->query->endParen();
        return $this;
    }

    /**
     * Adds an And
     * @return SchemaQuery
     */
    public function and(): SchemaQuery {
        $this->query->and();
        return $this;
    }

    /**
     * Starts an And expression
     * @return SchemaQuery
     */
    public function startAnd(): SchemaQuery {
        $this->query->startAnd();
        return $this;
    }

    /**
     * Ends an And expression
     * @return SchemaQuery
     */
    public function endAnd(): SchemaQuery {
        $this->query->endAnd();
        return $this;
    }

    /**
     * Adds an Or
     * @return SchemaQuery
     */
    public function or(): SchemaQuery {
        $this->query->or();
        return $this;
    }

    /**
     * Starts an Or expression
     * @return SchemaQuery
     */
    public function startOr(): SchemaQuery {
        $this->query->startOr();
        return $this;
    }

    /**
     * Ends an Or expression
     * @return SchemaQuery
     */
    public function endOr(): SchemaQuery {
        $this->query->endOr();
        return $this;
    }



    /**
     * Adds a Limit
     * @param int      $from
     * @param int|null $to   Optional.
     * @return SchemaQuery
     */
    public function limit(int $from, ?int $to = null): SchemaQuery {
        $this->query->limit($from, $to);
        return $this;
    }

    /**
     * Adds a limit using pagination
     * @param int $page   Optional.
     * @param int $amount Optional.
     * @return SchemaQuery
     */
    public function paginate(int $page = 0, int $amount = 100): SchemaQuery {
        $this->query->paginate($page, $amount);
        return $this;
    }
}
