<?php
namespace Tests\Analysis\Fixture\Log;

use Framework\Log\Attr\Action;

class ActionWithoutSection {

    #[Action("Create", es: "Creó algo", en: "Created something")]
    public static function create(int $id): int {
        return $id;
    }
}
