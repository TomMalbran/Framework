<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;
use Framework\Utils\URL;
use Framework\Utils\Utils;

/**
 * The String Value
 * @implements ValueInterface<string,string>
 */
class StringValue extends Value implements ValueInterface {

    private string $value;


    /**
     * Creates a new StringValue instance
     * @param Request $request
     * @param string  $key
     */
    public function __construct(Request $request, string $key) {
        parent::__construct($request, $key);
        $this->value = $request->getString($key);
    }

    /**
     * Sets the value
     * @param string $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = $value;
        $this->setRaw($value);
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
        return $this->value !== "" ? $this->value : null;
    }

    /**
     * Returns the value for the database
     * @return string
     */
    #[\Override]
    public function getValue(): string {
        return $this->value;
    }



    /**
     * Returns true if the value is valid
     * @return bool
     */
    public function isValid(): bool {
        return Strings::isValid($this->raw);
    }

    /**
     * Returns true if the length of the value is within the specified limit
     * @param int $maxLength
     * @return bool
     */
    public function isValidLength(int $maxLength): bool {
        return Strings::length($this->raw) <= $maxLength;
    }

    /**
     * Returns true if the value is a valid email
     * @return bool
     */
    public function isValidEmail(): bool {
        return Utils::isValidEmail($this->raw);
    }

    /**
     * Returns true if the value is a valid username
     * @return bool
     */
    public function isValidUsername(): bool {
        return Utils::isValidUsername($this->raw);
    }

    /**
     * Returns true if the value is a valid password
     * @param string $checkSets Optional.
     * @param int    $minLength Optional.
     * @return bool
     */
    public function isValidPassword(string $checkSets = "ad", int $minLength = 6): bool {
        return Utils::isValidPassword($this->raw, $checkSets, $minLength);
    }

    /**
     * Returns true if the value is a valid URL
     * @return bool
     */
    public function isValidUrl(): bool {
        return URL::isValid($this->raw);
    }

    /**
     * Returns true if the value is valid Number
     * @param int|null $min Optional.
     * @param int|null $max Optional.
     * @return bool
     */
    public function isValidNumber(?int $min = 1, ?int $max = null): bool {
        $float = Numbers::toFloat($this->raw);
        return Numbers::isValidFloat($float, $min, $max);
    }
}
