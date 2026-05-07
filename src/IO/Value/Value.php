<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\Database\Query\Assign;
use Framework\Utils\Strings;

/**
 * The Value
 */
class Value {

    protected Request $request;
    protected string $key;
    protected string $raw;


    /**
     * Creates a new Value instance
     * @param Request $request
     * @param string  $key
     */
    public function __construct(Request $request, string $key) {
        $this->request = $request;
        $this->key     = $key;
        $this->raw     = $request->getString($key);
    }

    /**
     * Returns true if the value exists in the request
     * @return bool
     */
    public function exists(): bool {
        return $this->request->exists($this->key);
    }

    /**
     * Returns true if the value is Empty
     * @return bool
     */
    public function isEmpty(): bool {
        return !$this->request->has($this->key);
    }

    /**
     * Returns true if the value is Not Empty
     * @return bool
     */
    public function hasValue(): bool {
        return $this->request->has($this->key);
    }

    /**
     * Returns the value for database storage
     * @return Assign|string|int
     */
    public function toDatabase(): Assign|string|int {
        return $this->raw;
    }

    /**
     * Sets the value in the request
     * @param mixed $value
     * @return void
     */
    protected function setRaw(mixed $value): void {
        $this->raw = Strings::toString($value);
        $this->request->set($this->key, $value);
    }
}
