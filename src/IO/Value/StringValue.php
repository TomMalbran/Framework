<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Database\Query\Assign;
use Framework\System\Config;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;
use Framework\Utils\URL;
use Framework\Utils\Utils;

use JsonSerializable;

/**
 * The String Value
 * @implements ValueInterface<string,string>
 */
class StringValue extends Value implements ValueInterface, JsonSerializable {

    private string $value;
    private bool $isEncrypted;


    /**
     * Creates a new StringValue instance
     * @param Request $request
     * @param string  $key
     * @param bool    $isEncrypted Optional.
     */
    public function __construct(Request $request, string $key, bool $isEncrypted = false) {
        parent::__construct($request, $key);
        $this->value       = $request->getString($key);
        $this->isEncrypted = $isEncrypted;
    }

    /**
     * Sets the value
     * @param StringValue|string $value
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
     * @return Assign|string
     */
    #[\Override]
    public function toDatabase(): Assign|string {
        if ($this->isEncrypted) {
            return Assign::encrypt($this->value, Config::getDbKey());
        }
        return $this->value;
    }

    /**
     * Returns the value as an integer
     * @return int
     */
    public function toInt(): int {
        return Numbers::toInt($this->value);
    }

    /**
     * Returns the value with only numbers
     * @return string
     */
    public function toNumber(): string {
        return Strings::toNumber($this->value);
    }

    /**
     * Returns the value as a slug
     * @return string
     */
    public function toSlug(): string {
        return URL::toSlug($this->value);
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
     * Returns true if the value is a valid Email
     * @return bool
     */
    public function isValidEmail(): bool {
        return Utils::isValidEmail($this->raw);
    }

    /**
     * Returns true if the value is a valid Phone
     * @return bool
     */
    public function isValidPhone(): bool {
        return Utils::isValidPhone($this->raw);
    }

    /**
     * Returns true if the value is a valid Username
     * @return bool
     */
    public function isValidUsername(): bool {
        return Utils::isValidUsername($this->raw);
    }

    /**
     * Returns true if the value is a valid Password
     * @param string $checkSets Optional.
     * @param int    $minLength Optional.
     * @return bool
     */
    public function isValidPassword(string $checkSets = "ad", int $minLength = 6): bool {
        return Utils::isValidPassword($this->raw, $checkSets, $minLength);
    }

    /**
     * Returns true if the value is a valid CUIT
     * @return bool
     */
    public function isValidCUIT(): bool {
        return Utils::isValidCUIT($this->raw);
    }

    /**
     * Returns true if the value is a valid DNI
     * @return bool
     */
    public function isValidDNI(): bool {
        return Utils::isValidDNI($this->raw);
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



    /**
     * Returns true if the value is equal to the given value
     * @param StringValue|string $value
     * @param bool               $caseInsensitive Optional.
     * @return bool
     */
    public function isEqual(StringValue|string $value, bool $caseInsensitive = false): bool {
        return Strings::isEqual($this->value, $value, $caseInsensitive);
    }

    /**
     * Returns true if the value is not equal to the given value
     * @param StringValue|string $value
     * @param bool               $caseInsensitive Optional.
     * @return bool
     */
    public function isNotEqual(StringValue|string $value, bool $caseInsensitive = false): bool {
        return !Strings::isEqual($this->value, $value, $caseInsensitive);
    }

    /**
     * Returns true if the value contains any of the given Needles
     * @param list<string>|string $needle
     * @param bool                $caseInsensitive Optional.
     * @param bool                $atLeastOne      Optional.
     * @return bool
     */
    public function contains(
        array|string $needle,
        bool $caseInsensitive = false,
        bool $atLeastOne = true,
    ): bool {
        return Strings::contains($this->value, $needle, $caseInsensitive, $atLeastOne);
    }

    /**
     * Returns true if the value starts any of the given Needles
     * @param string ...$needles
     * @return bool
     */
    public function startsWith(string ...$needles): bool {
        return Strings::startsWith($this->value, ...$needles);
    }

    /**
     * Returns true if the value ends with the given Needle
     * @param string ...$needles
     * @return bool
     */
    public function endsWith(string ...$needles): bool {
        return Strings::endsWith($this->value, ...$needles);
    }



    /**
     * Transforms the value to lowercase
     * @return string
     */
    public function toLowerCase(): string {
        return Strings::toLowerCase($this->value);
    }

    /**
     * Returns a Substring from the Needle to the end
     * @param string $needle
     * @param bool   $useFirst Optional.
     * @return string
     */
    public function substringAfter(string $needle, bool $useFirst = false): string {
        return Strings::substringAfter($this->value, $needle, $useFirst);
    }

    /**
     * Returns a short version of the given string
     * @param int  $length
     * @param bool $asUtf8 Optional.
     * @return string
     */
    public function makeShort(int $length, bool $asUtf8 = false): string {
        return Strings::makeShort($this->value, $length, $asUtf8);
    }

    /**
     * Merges the value with the given StringValue
     * @param StringValue $value
     * @return string
     */
    public function merge(StringValue $value): string {
        return Strings::merge($this->value, $value->value);
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
