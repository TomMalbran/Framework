<?php
namespace Framework\Database\Where;

use Framework\Database\Query\Exp;
use Framework\Database\Query\Op;
use Framework\Database\Where\BaseWhere;

/**
 * The JSON Where
 */
class JsonWhere extends BaseWhere {

    /**
     * Adds a condition over a value inside the JSON
     * @param string                      $path
     * @param Op                          $operator
     * @param list<int|string>|int|string $value
     * @return void
     */
    public function where(string $path, Op $operator, array|int|string $value): void {
        $this->query->where(Exp::json($this->column, $path), $operator, $value);
    }

    /**
     * Adds an In condition over a value inside the JSON
     * @param string           $path
     * @param list<int|string> $values
     * @return void
     */
    public function in(string $path, array $values): void {
        if (count($values) > 0) {
            $this->where($path, Op::In, $values);
        }
    }

    /**
     * Adds a condition over the JSON holding the given value at all
     * @param string $value
     * @return void
     */
    public function contains(string $value): void {
        // The value is looked for whole, so a 5 is not found inside a 51, which is
        // what a like over the column cannot say. It answers with the path it sits
        // at, and with nothing when the JSON does not hold it anywhere
        $this->query->where(Exp::jsonSearch($this->column, $value)->isNotNull());
    }

    /**
     * Adds an Is Valid condition
     * @return void
     */
    public function isValid(): void {
        $this->query->where(Exp::jsonValid($this->column));
    }

    /**
     * Adds an Is Empty condition
     * @return void
     */
    public function isEmpty(): void {
        $this->query->where($this->length(), Op::Equal, 0);
    }

    /**
     * Adds an Is Not Empty condition
     * @return void
     */
    public function isNotEmpty(): void {
        $this->query->where($this->length(), Op::GreaterThan, 0);
    }

    /**
     * Adds a Like condition over the whole JSON
     * It is the way to find a text without knowing where it is written, as a value
     * can be at any depth and inside any amount of elements
     * @param string $value
     * @param bool   $caseSensitive Optional.
     * @return void
     */
    public function like(string $value, bool $caseSensitive = false): void {
        // The column is matched as the text it is written as, so the names of the
        // keys are in it as much as the values are, and a word that is both is
        // found in either. Ask a path for a value that has to be one
        $this->query->where($this->column, Op::Like, $value, $caseSensitive);
    }



    /**
     * Returns the amount of elements of the JSON
     * @return Exp
     */
    private function length(): Exp {
        // A column with no value, or with something that is not a JSON, has no
        // elements, and JSON_LENGTH answers null to both of those
        return Exp::create("IFNULL(JSON_LENGTH({$this->column}), 0)");
    }
}
