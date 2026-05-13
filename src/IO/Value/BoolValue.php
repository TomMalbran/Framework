<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;

use JsonSerializable;

/**
 * The Bool Value
 * @implements ValueInterface<bool,bool>
 */
class BoolValue extends Value implements ValueInterface, JsonSerializable {

    private bool $value;


    /**
     * Creates a new BoolValue instance
     * @param Request $request
     * @param string  $key
     */
    public function __construct(Request $request, string $key) {
        parent::__construct($request, $key);
        $this->value = $request->isActive($key);
    }

    /**
     * Sets the value
     * @param bool $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = $value;
        $this->setRaw($value ? "1" : "0");
    }

    /**
     * Sets the value to true
     * @return void
     */
    public function setTrue(): void {
        $this->set(value: true);
    }

    /**
     * Sets the value to false
     * @return void
     */
    public function setFalse(): void {
        $this->set(value: false);
    }

    /**
     * Sets the value if the value is not empty
     * @param bool $value
     * @return void
     */
    #[\Override]
    public function setIf(mixed $value): void {
        if ($value) {
            $this->set($value);
        }
    }

    /**
     * Unsets the value
     * @return void
     */
    #[\Override]
    public function unset(): void {
        $this->set(value: false);
    }



    /**
     * Returns the value
     * @return bool
     */
    #[\Override]
    public function get(): bool {
        return $this->value;
    }

    /**
     * Returns the value or null if the value is false
     * @return bool|null
     */
    #[\Override]
    public function getOrNull(): ?bool {
        return $this->value ? true : null;
    }

    /**
     * Returns the value for database storage
     * @return int
     */
    #[\Override]
    public function toDatabase(): int {
        return (int)$this->value;
    }



    /**
     * Returns whether the value is true
     * @return bool
     */
    public function isTrue(): bool {
        return $this->value === true;
    }

    /**
     * Returns whether the value is false
     * @return bool
     */
    public function isFalse(): bool {
        return $this->value === false;
    }



    /**
     * Implements the JSON Serializable Interface
     * @return mixed
     */
    #[\Override]
    public function jsonSerialize(): mixed {
        return $this->get();
    }
}
