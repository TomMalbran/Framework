<?php
namespace Framework\IO\Value;

use Framework\IO\Request;
use Framework\IO\Value\Value;
use Framework\IO\Value\ValueInterface;
use Framework\Enum\Enum;
use Framework\Utils\Strings;

/**
 * The Enum Value
 * @implements ValueInterface<Enum,string>
 */
class EnumValue extends Value implements ValueInterface {

    private string $value;


    /**
     * Creates a new EnumValue instance
     * @param Request $request
     * @param string  $key
     */
    public function __construct(Request $request, string $key) {
        parent::__construct($request, $key);
        $this->value = $request->getString($key);
    }

    /**
     * Sets the value
     * @param Enum $value
     * @return void
     */
    #[\Override]
    public function set(mixed $value): void {
        $this->value = $value->toString();
        $this->setRaw($this->value);
    }

    /**
     * Sets the value if the value is not empty
     * @param Enum $value
     * @return void
     */
    #[\Override]
    public function setIf(mixed $value): void {
        if ($value->toString() !== "") {
            $this->set($value);
        }
    }

    /**
     * Unsets the value
     * @return void
     */
    #[\Override]
    public function unset(): void {
        $this->value = "";
        $this->setRaw("");
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
        return $this->value === "" ? null : $this->value;
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
}
