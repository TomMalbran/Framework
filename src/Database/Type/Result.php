<?php
namespace Framework\Database\Type;

use Framework\Utils\Errors;

/**
 * The Database Result
 */
class Result {

    public bool   $isCreate    = false;
    public bool   $isEdit      = false;
    public bool   $canValidate = false;

    public int    $id          = 0;
    public string $code        = "";
    public string $name        = "";
    public string $status      = "";

    public Errors $errors;



    /**
     * Creates a new Result
     * @param bool    $isEdit      Optional.
     * @param bool    $canValidate Optional.
     * @param integer $id          Optional.
     * @param string  $code        Optional.
     * @param string  $name        Optional.
     * @param string  $status      Optional.
     * @param Errors  $errors      Optional.
     */
    public function __construct(
        bool    $isEdit      = false,
        bool    $canValidate = false,
        int     $id          = 0,
        string  $code        = "",
        string  $name        = "",
        string  $status      = "",
        ?Errors $errors      = null,
    ) {
        $this->isCreate    = !$isEdit;
        $this->isEdit      = $isEdit;
        $this->canValidate = $canValidate;

        $this->id          = $id;
        $this->code        = $code;
        $this->name        = $name;
        $this->status      = $status;

        $this->errors      = $errors ?? new Errors();
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
     * @param string              $field
     * @param string              $message
     * @param string|integer|null $value   Optional.
     * @return Result
     */
    public function addError(string $field, string $message, string|int|null $value = null): Result {
        $this->errors->add($field, $message, $value);
        return $this;
    }
}
