<?php
namespace Tests\Analysis\Fixture\General;

class NativeDate {

    public function reported(): string {
        $now = time();
        return date("Y-m-d", $now);
    }

    public function allowed(): string {
        $stamp = mktime(0, 0, 0, 1, 1, 2020);
        return strftime("%Y", $stamp);
    }
}
