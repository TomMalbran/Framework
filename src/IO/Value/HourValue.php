<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Date\DateUtils;
use Framework\Utils\Strings;

use JsonSerializable;

/**
 * The Hour Value
 * @implements ValueInterface<string,string>
 */
class HourValue extends Value implements ValueInterface, JsonSerializable {

    private string $value;


    /**
     * Creates a new DateValue instance
     * @param Request $request
     * @param string  $key
     */
    public function __construct(Request $request, string $key) {
        parent::__construct($request, $key);
        $this->value = $request->getString($key);
    }

    /**
     * Sets the value
     * @param HourValue|string $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = Strings::toString($value);
        $this->setRaw($this->value);
    }

    /**
     * Sets the value if the value is not empty
     * @param string $value
     * @return void
     */
    #[\Override]
    public function setIf(mixed $value): void {
        if ($value !== "") {
            $this->set($value);
        }
    }

    /**
    * Unsets the value
    * @return void
    */
    #[\Override]
    public function unset(): void {
        $this->set("");
    }



    /**
     * Returns the value
     * @return string
     */
    #[\Override]
    public function get(): string {
        return $this->value;
    }

    /**
     * Returns the value or null if the value is empty
     * @return string|null
     */
    #[\Override]
    public function getOrNull(): ?string {
        return $this->hasValue() ? $this->value : null;
    }

    /**
     * Returns the value for database storage
     * @return string
     */
    #[\Override]
    public function toDatabase(): string {
        return $this->value;
    }



    /**
     * Returns true if the value is valid
     * @return bool
     */
    public function isValid(): bool {
        return DateUtils::isValidHour($this->raw);
    }

    /**
     * Returns true if the period between this hour and the end hour is valid
     * @param HourValue $endHour
     * @return bool
     */
    public function isValidPeriod(HourValue $endHour): bool {
        if (!$this->hasValue() || !$endHour->hasValue()) {
            return true;
        }
        return DateUtils::isValidHourPeriod($this->value, $endHour->value);
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
