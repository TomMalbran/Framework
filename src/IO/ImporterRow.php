<?php
namespace Framework\IO;

use Framework\Utils\Arrays;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * The Importer Row
 */
class ImporterRow {

    /** @var string[] */
    private array $fields;

    /** @var array<string,integer> */
    private array $columns;



    /**
     * Creates a new ImporterRow instance
     * @param string[]              $fields
     * @param array<string,integer> $columns
     */
    public function __construct(array $fields, array $columns) {
        $this->fields  = $fields;
        $this->columns = $columns;
    }

    /**
     * Returns all the Fields
     * @return array{}
     */
    public function toArray(): array {
        return $this->fields;
    }



    /**
     * Returns the Field Value for the given Key
     * @param string  $key
     * @param boolean $splitResult Optional.
     * @return string[]|string
     */
    private function getValue(string $key, bool $splitResult = false): array|string {
        $index  = isset($this->columns[$key]) ? $this->columns[$key] - 1 : -1;
        $result = "";

        if ($index < 0 || !isset($this->fields[$index])) {
            $result = "";
        } elseif (!Arrays::isEmpty($this->fields, $index) || $this->fields[$index] === "0") {
            $result = $this->fields[$index];
        }
        return $splitResult ? Strings::split($result, ",", true, true) : $result;
    }

    /**
     * Returns the Field Value for the given Key as a String
     * @param string $key
     * @return string
     */
    public function getString(string $key): string {
        $value = $this->getValue($key);
        return Strings::toString($value);
    }

    /**
     * Returns the Field Value for the given Key as an Integer
     * @param string $key
     * @return integer
     */
    public function getInt(string $key): int {
        $value = $this->getValue($key);
        return Numbers::toInt($value);
    }

    /**
     * Returns the Field Value for the given Key as a Float
     * @param string $key
     * @return float
     */
    public function getFloat(string $key): float {
        $value = $this->getValue($key);
        return Numbers::toFloat($value);
    }

    /**
     * Returns the Field Value for the given Key as a List of Strings
     * @param string $key
     * @return string[]
     */
    public function getList(string $key): array {
        $value = $this->getValue($key);
        return Arrays::toStrings($value);
    }
}
