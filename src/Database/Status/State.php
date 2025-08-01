<?php
namespace Framework\Database\Status;

use Framework\Database\Status\StateColor;

use Attribute;

/**
 * The State Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class State {

    public string     $name;
    public StateColor $color;
    public bool       $isHidden;



    /**
     * The State Attribute
     * @param string     $name
     * @param StateColor $color
     * @param bool       $isHidden Optional.
     */
    public function __construct(string $name, StateColor $color, bool $isHidden = false) {
        $this->name     = $name;
        $this->color    = $color;
        $this->isHidden = $isHidden;
    }
}
