<?php
namespace Tests\Analysis\Fixture\General;

class IntFloatWidening {

    public function takesFloat(float $amount): float {
        return $amount * 2;
    }

    public static function takesFloatStatically(float $amount): float {
        return $amount * 2;
    }

    /** A parameter that says an integer is welcome */
    public function takesEither(int|float $amount): float {
        return $amount * 2;
    }

    public function reported(): float {
        return $this->takesFloat(3);
    }

    public function reportedOnAStaticCall(): float {
        return self::takesFloatStatically(3);
    }

    public function reportedOnANamedArgument(): float {
        return $this->takesFloat(amount: 3);
    }

    public function allowed(): float {
        return $this->takesFloat(3.0);
    }

    public function allowedFromAFloat(float $amount): float {
        return $this->takesFloat($amount);
    }

    public function allowedWhenIntIsWelcome(): float {
        return $this->takesEither(3);
    }

    public function allowedWithTooManyArguments(): float {
        // No parameter to line the extra argument up against
        return $this->takesFloat(3.0, ...[ 4.0 ]);
    }
}
