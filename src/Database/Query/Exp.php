<?php
namespace Framework\Database\Query;

use Framework\Utils\Strings;

/**
 * A Query Expression
 */
class Exp {

    private string $sql;

    /** @var list<float|int|string> */
    private array $params;


    /**
     * Creates a new Exp instance
     * @param string                 $sql
     * @param list<float|int|string> $params Optional.
     */
    private function __construct(string $sql, array $params = []) {
        $this->sql    = $sql;
        $this->params = $params;
    }

    /**
     * Creates an Expression from the given SQL
     * @param string           $sql
     * @param float|int|string ...$params
     * @return Exp
     */
    public static function create(string $sql, float|int|string ...$params): Exp {
        return new Exp($sql, array_values($params));
    }

    /**
     * Creates an Expression with the name of a Column
     * It is what makes a comparison against another Column instead of a value, as a
     * plain string on that side of a where is bound and compared as text
     * @param string $column
     * @return Exp
     */
    public static function column(string $column): Exp {
        return new Exp($column);
    }

    /**
     * Creates an Expression that counts the rows
     * @param string $column Optional.
     * @return Exp
     */
    public static function count(string $column = "*"): Exp {
        return new Exp("COUNT($column)");
    }

    /**
     * Creates an Expression that adds up the values of a Column
     * @param string $column
     * @return Exp
     */
    public static function sum(string $column): Exp {
        return new Exp("SUM($column)");
    }

    /**
     * Creates an Expression with the value of a Column in lower case
     * @param string $column
     * @return Exp
     */
    public static function lower(string $column): Exp {
        return new Exp("LOWER($column)");
    }

    /**
     * Creates an Expression with the value of a Column, or the given one when it is null
     * @param string           $column
     * @param float|int|string $value
     * @return Exp
     */
    public static function ifNull(string $column, float|int|string $value): Exp {
        return new Exp("IFNULL($column, ?)", [ $value ]);
    }

    /**
     * Creates an Expression that joins the given Columns into one value
     * @param string ...$columns
     * @return Exp
     */
    public static function concat(string ...$columns): Exp {
        return new Exp("CONCAT(" . Strings::join($columns, ", ") . ")");
    }

    /**
     * Creates an Expression that reads a value of a JSON column
     * It is read as text so it is found whether it was saved as a number or as a
     * string, which a LIKE over the column can not do without taking 1 for 15 too
     * @param string $column
     * @param string $path
     * @return Exp
     */
    public static function json(string $column, string $path): Exp {
        // The path is bound rather than written into the SQL, so a quote in one
        // closes nothing, and it can be taken from a request like any other value
        return new Exp("JSON_UNQUOTE(JSON_EXTRACT($column, ?))", [ "$.$path" ]);
    }

    /**
     * Creates an Expression that is true when the Column holds a valid JSON
     * @param string $column
     * @return Exp
     */
    public static function jsonValid(string $column): Exp {
        return new Exp("JSON_VALID($column)");
    }

    /**
     * Creates an Expression with the path at which the given value sits inside a
     * JSON column, which is null when it is not there at all, so what asks whether
     * a JSON holds a value is an isNotNull around this one
     * @param string $column
     * @param string $value
     * @return Exp
     */
    public static function jsonSearch(string $column, string $value): Exp {
        return new Exp("JSON_SEARCH($column, 'one', ?)", [ $value ]);
    }



    /**
     * Returns an Expression that is true when this one has no value
     * @return Exp
     */
    public function isNull(): Exp {
        return new Exp("{$this->sql} IS NULL", $this->params);
    }

    /**
     * Returns an Expression that is true when this one has a value
     * @return Exp
     */
    public function isNotNull(): Exp {
        return new Exp("{$this->sql} IS NOT NULL", $this->params);
    }

    /**
     * Returns the SQL of the Expression
     * @return string
     */
    public function toSQL(): string {
        return $this->sql;
    }

    /**
     * Returns the Params the Expression binds
     * @return list<float|int|string>
     */
    public function getParams(): array {
        return $this->params;
    }
}
