<?php
namespace Tests\Analysis\Fixture\Enum;

class EnumInternal {

    public function reported(): string {
        $day = Weekday::from("mon");
        $try = Weekday::tryFrom("fri");
        $all = Weekday::cases();

        return $day->value . ($try?->value ?? "") . count($all);
    }

    public function allowed(): string {
        // The trait gives its own way in, which is the point of the rule
        return Size::fromValue("Small")->toString();
    }

    /** A static call on something that is not an enum at all */
    public function allowedOnAPlainClass(): int {
        return Counter::next() + Counter::cases();
    }

    /** A constant rather than one of the three names */
    public function allowedOtherConstant(): int {
        return Counter::STEP;
    }
}
