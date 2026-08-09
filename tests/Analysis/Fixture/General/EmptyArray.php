<?php
namespace Tests\Analysis\Fixture\General;

class EmptyArray {

    /**
     * An empty array shape says nothing about what is in it
     * @param array{} $values
     * @return array{}
     */
    public function reported(array $values): array {
        return $values;
    }

    /**
     * A shape with keys is fine
     * @param array{name:string} $value
     * @return array<string,string>
     */
    public function allowed(array $value): array {
        return $value;
    }
}
