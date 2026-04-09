<?php
namespace Framework\Discovery\Attr;

use Attribute;

/**
 * The Listener Attribute
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Listener {

    /** @var list<string> */
    public array $triggers = [];



    /**
     * The Listener Attribute
     * @param string ...$trigger
     */
    public function __construct(string ...$trigger) {
        $this->triggers = array_values($trigger);
    }
}
