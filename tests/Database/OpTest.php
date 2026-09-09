<?php
namespace Tests\Database;

use Framework\Database\Query\Query;
use Framework\Database\Query\Exp;
use Framework\Database\Query\Op;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Query Operators
 *
 * The cases are read from the enum rather than listed here, so one added
 * without a word on what it compiles to fails the provider instead of
 * quietly going untested.
 */
class OpTest extends TestCase {

    /**
     * Returns the SQL without the padding the builder leaves between clauses
     * @param Query $query
     * @return string
     */
    private function sql(Query $query): string {
        $result = preg_replace('/\s+/', " ", $query->toSQL());
        return trim((string)$result);
    }



    /**
     * An Operator, and the condition a Query writes for it
     * @param Op          $operator
     * @param mixed       $value
     * @param string      $expected
     * @param list<mixed> $bindings
     * @return void
     */
    #[DataProvider("providerOperators")]
    public function testEachOperatorIsWritten(
        Op $operator,
        mixed $value,
        string $expected,
        array $bindings,
    ): void {
        $query = Query::select("t");
        $query->where("n", $operator, $value);

        $this->assertSame(trim("SELECT * FROM `t` $expected"), $this->sql($query));
        $this->assertSame($bindings, $query->getBindings());
    }

    /**
     * An Operator given as the string it is worth, which is the other way a
     * condition names one, and has to reach the same place as the case does
     * @param Op          $operator
     * @param mixed       $value
     * @param string      $expected
     * @param list<mixed> $bindings
     * @return void
     */
    #[DataProvider("providerOperators")]
    public function testTheStringOfAnOperatorIsTheSameOne(
        Op $operator,
        mixed $value,
        string $expected,
        array $bindings,
    ): void {
        $query = Query::select("t");
        $query->where("n", $operator->value, $value);

        $this->assertSame(trim("SELECT * FROM `t` $expected"), $this->sql($query));
        $this->assertSame($bindings, $query->getBindings());
    }

    /**
     * One case per Operator the enum declares, so the set is the enum's own
     *
     * The three text operators all compile to LIKE and differ in where the
     * wildcards land, which is the part worth pinning down
     * @return array<string,array{Op,mixed,string,list<mixed>}>
     */
    public static function providerOperators(): array {
        $expected = [
            "None"           => [ 5,        "",                     [] ],

            "Equal"          => [ 5,        "WHERE n = ?",          [ 5 ] ],
            "NotEqual"       => [ 5,        "WHERE n <> ?",         [ 5 ] ],

            "In"             => [ [ 1, 2 ], "WHERE n IN (?,?)",     [ 1, 2 ] ],
            "NotIn"          => [ [ 1, 2 ], "WHERE n NOT IN (?,?)", [ 1, 2 ] ],

            "GreaterThan"    => [ 5,        "WHERE n > ?",          [ 5 ] ],
            "LessThan"       => [ 5,        "WHERE n < ?",          [ 5 ] ],
            "GreaterOrEqual" => [ 5,        "WHERE n >= ?",         [ 5 ] ],
            "LessOrEqual"    => [ 5,        "WHERE n <= ?",         [ 5 ] ],

            "Like"           => [ "wid",    "WHERE n LIKE ?",       [ "%wid%" ] ],
            "NotLike"        => [ "wid",    "WHERE n NOT LIKE ?",   [ "%wid%" ] ],
            "StartsWith"     => [ "wid",    "WHERE n LIKE ?",       [ "wid%" ] ],
            "NotStartsWith"  => [ "wid",    "WHERE n NOT LIKE ?",   [ "wid%" ] ],
            "EndsWith"       => [ "wid",    "WHERE n LIKE ?",       [ "%wid" ] ],
            "NotEndsWith"    => [ "wid",    "WHERE n NOT LIKE ?",   [ "%wid" ] ],
        ];

        $result = [];
        foreach (Op::cases() as $case) {
            if (!isset($expected[$case->name])) {
                throw new AssertionFailedError(
                    "Op::{$case->name} has no case here, so say what it writes",
                );
            }
            [ $value, $sql, $bindings ] = $expected[$case->name];
            $result[$case->name] = [ $case, $value, $sql, $bindings ];
        }
        return $result;
    }



    /**
     * An Operator whose value is not the shape it expects, and what it turns into
     * @param Op          $operator
     * @param mixed       $value
     * @param string      $expected
     * @param list<mixed> $bindings
     * @return void
     */
    #[DataProvider("providerValueShapes")]
    public function testTheValueCanChangeTheOperator(
        Op $operator,
        mixed $value,
        string $expected,
        array $bindings,
    ): void {
        $query = Query::select("t");
        $query->where("n", $operator, $value);

        $this->assertSame(trim("SELECT * FROM `t` $expected"), $this->sql($query));
        $this->assertSame($bindings, $query->getBindings());
    }

    /**
     * A list of one is a single value, and a single value is not a list, so the
     * four operators that read either one hand over to the other when they have
     * to. The ones written here are the shapes that do not match the operator
     * @return array<string,array{Op,mixed,string,list<mixed>}>
     */
    public static function providerValueShapes(): array {
        return [
            "an equal with one"        => [ Op::Equal,    [ 5 ],       "WHERE n = ?",          [ 5 ] ],
            "an equal with several"    => [ Op::Equal,    [ 1, 2, 3 ], "WHERE n IN (?,?,?)",   [ 1, 2, 3 ] ],
            "a not equal with one"     => [ Op::NotEqual, [ 5 ],       "WHERE n <> ?",         [ 5 ] ],
            "a not equal with several" => [ Op::NotEqual, [ 1, 2 ],    "WHERE n NOT IN (?,?)", [ 1, 2 ] ],

            "an in with a scalar"      => [ Op::In,       5,           "WHERE n = ?",          [ 5 ] ],
            "an in with one"           => [ Op::In,       [ 5 ],       "WHERE n = ?",          [ 5 ] ],
            "a not in with a scalar"   => [ Op::NotIn,    5,           "WHERE n <> ?",         [ 5 ] ],
            "a not in with one"        => [ Op::NotIn,    [ 5 ],       "WHERE n <> ?",         [ 5 ] ],
        ];
    }



    /**
     * An Operator, and the condition it writes against an Expression rather than
     * against a value
     * @param Op     $operator
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerAgainstAnExp")]
    public function testAnOperatorTakesAnExpression(Op $operator, string $expected): void {
        $query = Query::select("t");
        $query->where("a", $operator, Exp::column("b"));

        $this->assertSame("SELECT * FROM `t` $expected", $this->sql($query));
        // The Expression is written, never bound, so there is nothing to bind
        $this->assertSame([], $query->getBindings());
    }

    /**
     * The wildcards of a text operator go around a value as it is bound, and an
     * Expression is not bound, so they are put around its SQL instead. The rest
     * compare against it as they would against anything
     * @return array<string,array{Op,string}>
     */
    public static function providerAgainstAnExp(): array {
        return [
            "equal"           => [ Op::Equal,          "WHERE a = b" ],
            "greater"         => [ Op::GreaterThan,    "WHERE a > b" ],

            "like"            => [ Op::Like,           "WHERE a LIKE CONCAT('%', b, '%')" ],
            "not like"        => [ Op::NotLike,        "WHERE a NOT LIKE CONCAT('%', b, '%')" ],
            "starts with"     => [ Op::StartsWith,     "WHERE a LIKE CONCAT(b, '%')" ],
            "not starts with" => [ Op::NotStartsWith,  "WHERE a NOT LIKE CONCAT(b, '%')" ],
            "ends with"       => [ Op::EndsWith,       "WHERE a LIKE CONCAT('%', b)" ],
            "not ends with"   => [ Op::NotEndsWith,    "WHERE a NOT LIKE CONCAT('%', b)" ],
        ];
    }

    public function testAnExpressionOnBothSidesKeepsItsParams(): void {
        $query = Query::select("t");
        $query->where(Exp::json("t.d", "name"), Op::StartsWith, Exp::json("t.d", "prefix"));

        $this->assertSame(
            "SELECT * FROM `t` WHERE JSON_UNQUOTE(JSON_EXTRACT(t.d, ?)) LIKE " .
                "CONCAT(JSON_UNQUOTE(JSON_EXTRACT(t.d, ?)), '%')",
            $this->sql($query),
        );
        $this->assertSame([ "$.name", "$.prefix" ], $query->getBindings());
    }

    /**
     * An Operator, and the SQL it stands for
     * @param Op     $operator
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerToSQL")]
    public function testEachOperatorHasItsSQL(Op $operator, string $expected): void {
        $this->assertSame($expected, $operator->toSQL());
    }

    /**
     * One case per Operator, since the SQL of one is not always its value
     * @return array<string,array{Op,string}>
     */
    public static function providerToSQL(): array {
        $expected = [
            "None"           => "",

            "Equal"          => "=",
            "NotEqual"       => "<>",

            "In"             => "IN",
            "NotIn"          => "NOT IN",

            "GreaterThan"    => ">",
            "LessThan"       => "<",
            "GreaterOrEqual" => ">=",
            "LessOrEqual"    => "<=",

            // The four that say where the wildcards go are a LIKE by the time
            // they are SQL, as the value is what carries the difference
            "Like"           => "LIKE",
            "NotLike"        => "NOT LIKE",
            "StartsWith"     => "LIKE",
            "NotStartsWith"  => "NOT LIKE",
            "EndsWith"       => "LIKE",
            "NotEndsWith"    => "NOT LIKE",
        ];

        $result = [];
        foreach (Op::cases() as $case) {
            if (!isset($expected[$case->name])) {
                throw new AssertionFailedError(
                    "Op::{$case->name} has no case here, so say what its SQL is",
                );
            }
            $result[$case->name] = [ $case, $expected[$case->name] ];
        }
        return $result;
    }
}
