<?php
namespace Framework\IO\Value;

/**
 * The Value Interface
 * @template TInput
 * @template TOutput
 */
interface ValueInterface {

    /**
     * Sets the value
     * @param TInput $value
     * @return void
     */
    public function set(mixed $value): void;

    /**
     * Sets the value if the value is not empty
     * @param TInput $value
     * @return void
     */
    public function setIf(mixed $value): void;

    /**
     * Unsets the value
     * @return void
     */
    public function unset(): void;


    /**
     * Returns the value
     * @return TOutput
     */
    public function get(): mixed;

    /**
     * Returns the value or null if the value is empty
     * @return TOutput|null
     */
    public function getOrNull(): mixed;
}
