<?php
namespace Tests\Analysis\Fixture\General;

class PrivateReturnUnused {

    public function callsThem(): string {
        // The result is thrown away, so the return type earns nothing
        $this->reported();

        // The same through a static call
        self::reportedStatically();

        // These are used, so they are fine
        $kept = $this->allowedUsed();
        $also = static::allowedUsedStatically();
        return $kept . $also . ($this->allowedInCondition() ? "!" : "");
    }

    private function reported(): string {
        return "ignored";
    }

    private static function reportedStatically(): string {
        return "ignored";
    }

    private function allowedUsed(): string {
        return "kept";
    }

    private static function allowedUsedStatically(): string {
        return "kept";
    }

    private function allowedInCondition(): bool {
        return true;
    }
}
