<?php
namespace Framework\Schema;

/**
 * The Schema Assign
 */
class Assign {

    public string $type;
    public string $column;

    /** @var mixed[] */
    public array  $params;



    /**
     * Creates a new Assign instance
     * @param string  $type
     * @param string  $column Optional.
     * @param mixed[] $params Optional.
     */
    private function __construct(
        string $type,
        string $column = "",
        array $params = [],
    ) {
        $this->type   = $type;
        $this->column = $column;
        $this->params = $params;
    }

    /**
     * Generates an Equal Assign between columns
     * @param string $column
     * @return Assign
     */
    public static function equal(string $column): Assign {
        return new Assign("equal", column: $column);
    }

    /**
     * Generates a Not Assign of a column
     * @param string $column Optional.
     * @return Assign
     */
    public static function not(string $column): Assign {
        return new Assign("not", column: $column);
    }

    /**
     * Generates an Increase Assign of a column
     * @param integer $amount Optional.
     * @return Assign
     */
    public static function increase(int $amount = 1): Assign {
        return new Assign("increase", params: [ $amount ]);
    }

    /**
     * Generates an Decrease Assign of a column
     * @param integer $amount Optional.
     * @return Assign
     */
    public static function decrease(int $amount = 1): Assign {
        return new Assign("decrease", params: [ $amount ]);
    }

    /**
     * Method generates an UUID function call
     * @return Assign
     */
    public static function uuid(): Assign {
        return new Assign("uuid");
    }

    /**
     * Method generates an AES Encrypt function call
     * @param string $value
     * @param string $key
     * @return Assign
     */
    public static function encrypt(string $value, string $key): Assign {
        return new Assign("encrypt", params: [ $value, $key ]);
    }

    /**
     * Method generates an REPLACE function call
     * @param string $value
     * @param string $replace
     * @return Assign
     */
    public static function replace(string $value, string $replace): Assign {
        return new Assign("replace", params: [ $value, $replace ]);
    }

    /**
     * Method generates a GREATEST function call
     * @param integer $value
     * @return Assign
     */
    public static function greatest(int $value): Assign {
        return new Assign("greatest", params: [ $value ]);
    }
}
