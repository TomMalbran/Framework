<?php
namespace Tests\Analysis\Fixture\General;

use Tests\Analysis\Fixture\Enum\Grade;
use Tests\Analysis\Fixture\Enum\Size;

class SwitchProblems {

    /** None is handled, but not first */
    public function noneOutOfOrder(Size $size): string {
        switch ($size) {
            case Size::Small:
                return "small";
            case Size::None:
                return "none";
            case Size::Large:
                return "large";
        }
        return "";
    }

    /** A case belonging to another enum entirely */
    public function wrongEnum(Size $size): string {
        switch ($size) {
            case Size::None:
                return "none";
            case Grade::Pass:
                return "pass";
        }
        return "";
    }

    /** Everything but None is handled, and default is standing in for it */
    public function defaultForNone(Size $size): string {
        switch ($size) {
            case Size::Small:
                return "small";
            case Size::Large:
                return "large";
            default:
                return "none";
        }
    }
}
