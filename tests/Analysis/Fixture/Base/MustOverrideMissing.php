<?php
namespace Tests\Analysis\Fixture\Base;

class MustOverrideMissing extends MustOverrideBase {

    /**
     * The one it is welcome to keep is not the one it was asked for
     * @return string
     */
    #[\Override]
    public function getTitle(): string {
        return "missing";
    }
}

class MustOverrideMissingDeep extends MustOverrideMiddle {
}
