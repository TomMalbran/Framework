<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Utils\Numbers;

/**
 * The Float Value
 * @implements ValueInterface<float,float>
 */
class FloatValue extends Value implements ValueInterface {

    private float $value;
    private int $decimals;


    /**
     * Creates a new FloatValue instance
     * @param Request $request
     * @param string  $key
     * @param int     $decimals
     */
    public function __construct(Request $request, string $key, int $decimals) {
        parent::__construct($request, $key);
        $this->value    = $request->getFloat($key);
        $this->decimals = $decimals;
    }

    /**
     * Sets the value
     * @param float $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = $value;
        $this->setRaw($value);
    }

    /**
     * Sets the value if the value is not empty
     * @param float $value
     * @return void
     */
    #[\Override]
    public function setIf(mixed $value): void {
        if ($value !== 0.0) {
            $this->set($value);
        }
    }

    /**
     * Unsets the value
     * @return void
     */
    #[\Override]
    public function unset(): void {
        $this->set(0.0);
    }



    /**
     * Returns the value
     * @return float
     */
    #[\Override]
    public function get(): float {
        return $this->value;
    }

    /**
     * Returns the value or null if the value is 0
     * @return float|null
     */
    #[\Override]
    public function getOrNull(): ?float {
        return $this->value !== 0.0 ? $this->value : null;
    }

    /**
     * Returns the value for the database
     * @return int
     */
    #[\Override]
    public function getValue(): int {
        return Numbers::toInt($this->value, $this->decimals);
    }



    /**
     * Returns true if the value is valid
     * @param int|null $min Optional.
     * @param int|null $max Optional.
     * @return bool
     */
    public function isValid(?int $min = 1, ?int $max = null): bool {
        return Numbers::isValidFloat($this->value, $min, $max, $this->decimals);
    }

    /**
     * Returns true if the value is a valid price
     * @param int|null $min Optional.
     * @param int|null $max Optional.
     * @return bool
     */
    public function isValidPrice(?int $min = 1, ?int $max = null): bool {
        return Numbers::isValidPrice($this->value, $min, $max);
    }

    /**
     * Returns true if the value is greater than the other value
     * @param FloatValue $other
     * @return bool
     */
    public function isGreaterThan(FloatValue $other): bool {
        if (!$this->hasValue() || !$other->hasValue()) {
            return true;
        }
        return $this->value > $other->get();
    }
}
