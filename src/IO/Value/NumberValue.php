<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Date\DateUtils;
use Framework\Utils\Numbers;

use JsonSerializable;

/**
 * The Number Value
 * @implements ValueInterface<int,int>
 */
class NumberValue extends Value implements ValueInterface, JsonSerializable {

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
     * @param NumberValue|int $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = Numbers::toInt($value);
        $this->setRaw($this->value);
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
     * Returns the value for database storage
     * @return int
     */
    #[\Override]
    public function toDatabase(): int {
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

    /**
     * Returns true if the value is a valid week day
     * @param bool $startMonday Optional.
     * @return bool
     */
    public function isValidWeekDay(bool $startMonday = false): bool {
        return DateUtils::isValidWeekDay($this->value, $startMonday);
    }



    /**
     * Returns true if the value is equal to the other value
     * @param NumberValue|int $other
     * @return bool
     */
    public function isEqual(NumberValue|int $other): bool {
        return $this->value === Numbers::toInt($other);
    }

    /**
     * Returns true if the value is not equal to the other value
     * @param NumberValue|int $other
     * @return bool
     */
    public function isNotEqual(NumberValue|int $other): bool {
        return $this->value !== Numbers::toInt($other);
    }

    /**
     * Returns true if the value is greater than the other value
     * @param NumberValue $other
     * @return bool
     */
    public function isGreaterThan(NumberValue $other): bool {
        if (!$this->hasValue() || !$other->hasValue()) {
            return true;
        }
        return $this->value > $other->get();
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
