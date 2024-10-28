<?php
namespace Framework\System;

use Attribute;

/**
 * The Listener Attribute
 */
#[Attribute]
class Listener {

    /** @var string[] */
    public array $triggers = [];


    /**
     * The Listener Attribute
     * @param string ...$trigger
     */
    public function __construct(string ...$trigger) {
        $this->triggers = $trigger;
    }
}
