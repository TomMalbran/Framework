<?php
namespace Tests\Analysis\Fixture\Base;

use Framework\Analysis\Attr\MustOverride;

class MustOverrideBase {

    /**
     * The one every class below has to write for itself
     * @return string
     */
    #[MustOverride]
    public function getName(): string {
        return "";
    }

    /**
     * The one they are welcome to keep
     * @return string
     */
    public function getTitle(): string {
        return "";
    }
}

abstract class MustOverrideMiddle extends MustOverrideBase {
}
