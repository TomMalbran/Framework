<?php
namespace Tests\Analysis\Fixture\General;

class ClassCompare {

    public function reported(Box $left, Box $right): bool {
        return $left === $right;
    }

    public function alsoReported(Box $left, Box $right): bool {
        return $left == $right;
    }

    public function allowedOnValues(Box $left, Box $right): bool {
        return $left->size === $right->size;
    }

    public function allowedAgainstThis(ClassCompare $other): bool {
        return $this === $other;
    }
}
