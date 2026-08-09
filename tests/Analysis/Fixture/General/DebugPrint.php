<?php
namespace Tests\Analysis\Fixture\General;

class DebugPrint {

    public function reported(mixed $value): void {
        var_dump($value);
        print_r($value);
    }

    public function allowed(mixed $value): void {
        print($value);
        echo $value;
        error_log((string)$value);
    }
}
