<?php
namespace Tests\Analysis\Fixture\General;

class MatchOverSwitch {

    public function reported(string $value): string {
        switch ($value) {
            case "a":
                return "first";
            case "b":
                return "second";
            default:
                return "none";
        }
    }

    public function allowed(string $value): string {
        return match ($value) {
            "a"     => "first",
            "b"     => "second",
            default => "none",
        };
    }

    public function allowedWithWork(string $value): string {
        $result = "";
        switch ($value) {
            case "a":
                $result = "first";
                $result .= "!";
                break;
            default:
                $result = "none";
                break;
        }
        return $result;
    }
}
