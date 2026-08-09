<?php
namespace Tests\Analysis\Fixture\General;

class NamedBoolArg {

    public function target(string $name, bool $isActive = false, bool $isAdmin = false): string {
        return $name . ($isActive ? "1" : "0") . ($isAdmin ? "1" : "0");
    }

    public function reported(): string {
        return $this->target("a", true);
    }

    public function allowed(): string {
        return $this->target("a", isActive: true, isAdmin: false);
    }

    public function allowedWithoutBools(): string {
        return $this->target("a");
    }
}
