<?php
namespace Tests\Analysis\Fixture\General;

class ReturnNotNeeded extends ReturnNotNeededParent {

    public function reported(int $value): bool {
        if ($value > 0) {
            return true;
        }
        return true;
    }

    /** The returns are buried in a switch, a try and an else */
    public function reportedThroughBlocks(int $value): bool {
        switch ($value) {
            case 1:
                return true;
        }

        try {
            if ($value > 5) {
                return true;
            } else {
                return true;
            }
        } catch (\Throwable $e) {
            return true;
        } finally {
            $value = 0;
        }
    }

    public function allowed(int $value): bool {
        if ($value > 0) {
            return true;
        }
        return false;
    }

    public function allowedNotBoolean(int $value): int {
        return $value;
    }

    /** A single statement is left alone whatever it returns */
    public function allowedSingleStatement(): bool {
        return true;
    }

    /** Inherited, so the shape is not this class's to change */
    #[\Override]
    public function fromTheParent(int $value): bool {
        if ($value > 0) {
            return true;
        }
        return true;
    }
}
