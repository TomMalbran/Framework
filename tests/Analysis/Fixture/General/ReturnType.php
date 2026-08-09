<?php
namespace Tests\Analysis\Fixture\General;

/** A plain function, described differently from a method */
function reportedFunction() {
    return "no return type";
}

class ReturnType {

    /** Constructors are not asked for one */
    public function __construct(private int $value = 0) {
    }

    public function reported() {
        return "no return type";
    }

    private function alsoReported() {
        return 1;
    }

    public function allowed(): string {
        return "typed";
    }

    public function allowedVoid(): void {
    }

    /** Closures and arrow functions are skipped */
    public function allowedClosures(): callable {
        $closure = function () {
            return 1;
        };
        $arrow = fn () => 2;

        return fn () => $closure() + $arrow();
    }
}
