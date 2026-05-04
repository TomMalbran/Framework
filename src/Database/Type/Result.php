<?php
namespace Framework\Database\Type;

use Framework\IO\Errors;

/**
 * The Database Result
 */
class Result {

    public bool   $canValidate;
    public Errors $errors;



    /**
     * Creates a new Result
     * @param bool   $canValidate
     * @param Errors $errors
     */
    public function __construct(bool $canValidate, Errors $errors) {
        $this->canValidate = $canValidate;
        $this->errors      = $errors;
    }

    /**
     * Returns whether there are any Error
     * @return bool
     */
    public function hasError(): bool {
        return $this->errors->has();
    }

    /**
     * Adds a new Error
     * @param string     $field
     * @param string     $message
     * @param int|string ...$value
     * @return Result
     */
    public function addError(string $field, string $message, int|string ...$value): Result {
        $this->errors->add($field, $message, ...$value);
        return $this;
    }
}
