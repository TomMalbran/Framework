<?php
namespace Framework\Enum;

/**
 * The Enum interface
 */
interface Enum {

    /**
     * Creates an Enum from a String
     * @param string $value
     * @return self
     */
    public static function fromValue(string $value): self;


    /**
     * Converts the Enum into a string
     * @return string
     */
    public function toString(): string;
}
