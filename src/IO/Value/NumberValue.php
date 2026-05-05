<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Utils\Numbers;

/**
 * The Number Value
 * @implements ValueInterface<int,int>
 */
class NumberValue extends Value implements ValueInterface {

    private int $value;


    /**
     * Creates a new NumberValue instance
     * @param Request $request
     * @param string  $key
     */
    public function __construct(Request $request, string $key) {
        parent::__construct($request, $key);
        $this->value = $request->getInt($key);
    }

    /**
     * Sets the value
     * @param int $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = $value;
        $this->setRaw($value);
    }

    /**
     * Sets the value if the value is not empty
     * @param int $value
     * @return void
     */
    #[\Override]
    public function setIf(mixed $value): void {
        if ($value !== 0) {
            $this->set($value);
        }
    }

    /**
     * Unsets the value
     * @return void
     */
    #[\Override]
    public function unset(): void {
        $this->set(0);
    }



    /**
     * Returns the value
     * @return int
     */
    #[\Override]
    public function get(): int {
        return $this->value;
    }

    /**
     * Returns the value or null if the value is 0
     * @return int|null
     */
    #[\Override]
    public function getOrNull(): ?int {
        return $this->value !== 0 ? $this->value : null;
    }

    /**
     * Returns the value
     * @return int
     */
    #[\Override]
    public function getValue(): int {
        return $this->value;
    }



    /**
     * Returns true if the value is valid
     * @param int|null $min Optional.
     * @param int|null $max Optional.
     * @return bool
     */
    public function isValid(?int $min = 1, ?int $max = null): bool {
        return Numbers::isValid($this->value, $min, $max);
    }
}
