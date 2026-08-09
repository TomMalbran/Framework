<?php
namespace Tests\Analysis\Fixture\Signal;

use Framework\Discovery\Attr\Listener;

class Listeners {

    #[Listener("boxCreated")]
    public static function reported(int $boxID): string {
        return "created $boxID";
    }

    #[Listener("boxEdited")]
    public static function allowed(int $boxID): void {
        error_log("edited $boxID");
    }

    public static function allowedWithoutTheAttribute(int $boxID): string {
        return "plain $boxID";
    }
}
