<?php
namespace Framework\Enum;

use Framework\Request;

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
     * Creates an Enum from a String
     * @param Request $request
     * @param string  $key
     * @return self
     */
    public static function fromRequest(Request $request, string $key): self;



    /**
     * Converts the Enumerable into a string
     * @return string
     */
    public function toString(): string;
}
