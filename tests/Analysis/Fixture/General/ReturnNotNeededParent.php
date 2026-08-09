<?php
namespace Tests\Analysis\Fixture\General;

class ReturnNotNeededParent {

    public function fromTheParent(int $value): bool {
        return $value > 0;
    }
}
