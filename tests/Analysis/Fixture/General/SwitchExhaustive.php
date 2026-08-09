<?php
namespace Tests\Analysis\Fixture\General;

use Tests\Analysis\Fixture\Enum\Size;

class SwitchExhaustive {

    public function reported(Size $size): string {
        switch ($size) {
            case Size::None:
                return "none";
            case Size::Small:
                return "small";
        }
        return "";
    }

    public function allowed(Size $size): string {
        switch ($size) {
            case Size::None:
                return "none";
            case Size::Small:
                return "small";
            case Size::Large:
                return "large";
        }
        return "";
    }
}
