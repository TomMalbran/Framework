<?php
namespace Tests\Analysis\Fixture\Base;

class MustOverrideDone extends MustOverrideBase {

    /**
     * The one it was asked for
     * @return string
     */
    #[\Override]
    public function getName(): string {
        return "done";
    }
}

class MustOverrideDeep extends MustOverrideMiddle {

    /**
     * The base is two above, and this is still the class that can be built
     * @return string
     */
    #[\Override]
    public function getName(): string {
        return "deep";
    }
}
