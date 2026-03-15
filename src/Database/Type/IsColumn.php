<?php
namespace Framework\Database\Type;

use Framework\Utils\Strings;

/**
 * The Column trait
 */
trait IsColumn {

    /**
     * Get the name of the column
     * @return string
     */
    public function name(): string {
        return $this->value;
    }

    /**
     * Get the key of the column
     * @return string
     */
    public function key(): string {
        return Strings::lowerCaseFirst($this->name);
    }

    /**
     * Get the name of the column without the table
     * @return string
     */
    public function base(): string {
        return Strings::substringAfter($this->value, ".");
    }



    /**
     * Get the name of the column without the table
     * @param list<self>|self|null $values
     * @return list<string>
     */
    public static function toKeys(array|self|null $values): array {
        if (is_null($values)) {
            return [];
        }
        if ($values instanceof self) {
            return [ $values->key() ];
        }
        return array_map(fn($value) => $value->key(), $values);
    }
}
