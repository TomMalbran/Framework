<?php
namespace Tests\Analysis\Fixture\General;

use Tests\Analysis\Fixture\Enum\Colour;

class EnumNameValue {

    public function __construct(
        public string $name = "",
        public string $value = "",
    ) {
    }

    public function reported(Colour $colour): string {
        $name = $colour->name;
        return $name . $colour->value;
    }

    public function allowed(Colour $colour): string {
        return $colour->toString() === "" ? "" : "ok";
    }

    /** name and value on something that is not an enum */
    public function allowedOnAPlainObject(EnumNameValue $other): string {
        return $other->name . $other->value;
    }

    /** A property that is neither name nor value */
    public function allowedOtherProperty(Box $box): int {
        return $box->size;
    }
}
