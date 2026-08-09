<?php
namespace Tests\Analysis\Fixture\General;

class OptionalComment {

    /**
     * The default is not called out in the description
     * @param string $name
     * @param int    $amount
     * @return string
     */
    public function reported(string $name, int $amount = 0): string {
        return $name . $amount;
    }

    /**
     * The default is called out
     * @param string $name
     * @param int    $amount Optional.
     * @return string
     */
    public function allowed(string $name, int $amount = 0): string {
        return $name . $amount;
    }
}
