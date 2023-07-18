<?php
namespace Framework\Utils;

use Framework\Utils\Arrays;

/**
 * The Errors wrapper
 */
class Errors {

    /** @var array{} */
    private array $errors = [];

    /** @var array{} */
    private array $counts = [];


    /**
     * Creates a new Errors instance
     * @param array{}|null $errors
     */
    public function __construct(?array $errors = null) {
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
     * Increases the Errors amount
     * @param string  $section
     * @param integer $amount
     * @return Errors
     */
    public function incCount(string $section, int $amount = 1): Errors {
        if (empty($this->counts[$section])) {
            $this->counts[$section] = 0;
        }
        $this->counts[$section] += $amount;
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
     * Adds a new error
     * @param string      $error
     * @param string      $message
     * @param string|null $value   Optional.
     * @return Errors
     */
    public function add(string $error, string $message, ?string $value = null): Errors {
        if (!empty($value)) {
            $this->errors[$error] = [ $message, $value ];
        } else {
            $this->errors[$error] = $message;
        }
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
     * Adds a new error
     * @param string      $section
     * @param string      $error
     * @param string      $message
     * @param string|null $value   Optional.
     * @return Errors
     */
    public function addFor(string $section, string $error, string $message, ?string $value = null): Errors {
        if (empty($this->errors[$section])) {
            $this->errors[$section] = 1;
        } else {
            $this->errors[$section] += 1;
        }
        return $this->add($error, $message, $value);
    }

    /**
     * Merges the other Errors
     * @param Errors $errors
     * @param string $prefix Optional.
     * @param string $suffix Optional.
     * @return Errors
     */
    public function merge(Errors $errors, string $prefix = "", string $suffix = ""): Errors {
        if (empty($prefix) && empty($suffix)) {
            $this->errors += $errors->get();
            return $this;
        }

        $newErrors = $errors->get();
        foreach ($newErrors as $key => $error) {
            $this->errors["$prefix$key$suffix"] = $error;
        }
        return $this;
    }

    /**
     * Merges the other Errors
     * @param string $section
     * @param Errors $errors
     * @param string $prefix  Optional.
     * @param string $suffix  Optional.
     * @return Errors
     */
    public function mergeFor(string $section, Errors $errors, string $prefix = "", string $suffix = ""): Errors {
        $this->incCount($section, $errors->getTotal());
        return $this->merge($errors, $prefix, $suffix);
    }



    /**
     * Returns true if there are errors or if the given error exists
     * @param string[]|string|null $error Optional.
     * @return boolean
     */
    public function has(array|string $error = null): bool {
        if ($error === null) {
            return !empty($this->errors);
        }
        $errors = Arrays::toArray($error);
        foreach ($errors as $err) {
            if (!empty($this->errors[$err])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the errors as an Object
     * @return array{}
     */
    public function get(): array {
        return array_merge($this->errors, $this->counts);
    }

    /**
     * Returns the error keys
     * @return mixed[]
     */
    public function keys(): array {
        return array_keys($this->errors);
    }

    /**
     * Returns the amount of errors
     * @return integer
     */
    public function getTotal(): int {
        return count($this->errors);
    }
}
