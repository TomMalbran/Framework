<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

/**
 * The Errors wrapper
 */
class Errors {
    
    private $errors = [];
    
    
    /**
     * Creates a new Errors instance
     * @param array $errors
     */
    public function __construct(array $errors = null) {
        if ($errors !== null) {
            $errors = Arrays::toArray($errors);
            foreach ($errors as $error => $message) {
                $this->add($error, $message);
            }
        }
    }
    
    

    /**
     * Sets the given key on the error data with the given value
     * @param string $error
     * @param string $message
     * @return void
     */
    public function __set(string $error, string $message): void {
        $this->add($error, $message);
    }

    /**
     * Sets the given key on the error data with the given value
     * @param string $error
     * @return string
     */
    public function __get(string $error): string {
        if ($this->has($error)) {
            return $this->errors[$error];
        }
        return "";
    }
    


    /**
     * Adds a new error
     * @param string $error
     * @param string $message
     * @return Errors
     */
    public function add(string $error, string $message): Errors {
        $this->errors[$error] = $message;
        return $this;
    }

    /**
     * Adds a new form error
     * @param string $message
     * @return Errors
     */
    public function form(string $message): Errors {
        $this->errors["form"] = $message;
        return $this;
    }

    /**
     * Adds a new global error
     * @param string $message
     * @return Errors
     */
    public function global(string $message): Errors {
        $this->errors["global"] = $message;
        return $this;
    }
    
    /**
     * Adds a new error if the condition is true
     * @param boolean $condition
     * @param string  $error
     * @param string  $message
     * @return Errors
     */
    public function addIf(bool $condition, string $error, string $message): Errors {
        if ($condition) {
            $this->add($error, $message);
        }
        return $this;
    }
    


    /**
     * Returns true if there are errors or if the given error exists
     * @param string|string[] $error Optional.
     * @return boolean
     */
    public function has($error = null): bool {
        if ($error === null) {
            return !empty($this->errors);
        }
        $errors = !is_array($error) ? [ $error ] : $error;
        foreach ($errors as $err) {
            if (!empty($this->errors[$err])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns the errors as an Object
     * @return array
     */
    public function get(): array {
        return $this->errors;
    }
}
