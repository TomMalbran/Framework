<?php
namespace Framework\Analysis\Attr;

use Attribute;

/**
 * The Not Tested Attribute
 *
 * A public method the suite does not reach. The check that every method is
 * tested reads these and lets them be, so the ones that predate it do not
 * hold the rest back, and it fails the moment a test does reach one, which
 * is what takes the mark back off.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class NotTested {

    public string $reason = "";



    /**
     * The Not Tested Attribute
     * @param string $reason Optional.
     */
    public function __construct(string $reason = "") {
        $this->reason = $reason;
    }
}
