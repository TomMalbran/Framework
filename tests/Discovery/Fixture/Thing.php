<?php
namespace Tests\Discovery\Fixture;

use Framework\Discovery\Attr\Priority;
use Framework\Utils\Strings;

/**
 * A class with a parent, an attribute and a constructor
 */
#[Priority(Priority::High)]
class Thing extends BaseThing {

    public string $colour = "";

    protected int $size = 0;

    private bool $hidden = false;


    /**
     * A class with a parent, an attribute and a constructor
     * @param string $colour Optional.
     */
    public function __construct(string $colour = "red") {
        $this->colour = $colour;
        $this->hidden = Strings::isEqual($colour, "red");
    }

    /**
     * Returns true if the Thing is hidden
     * @return bool
     */
    public function isHidden(): bool {
        return $this->hidden;
    }
}
