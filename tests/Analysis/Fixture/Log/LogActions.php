<?php
namespace Tests\Analysis\Fixture\Log;

use Framework\Log\Attr\Action;
use Framework\Log\Attr\Section;

#[Section("Boxes", es: "Cajas", en: "Boxes")]
class LogActions {

    #[Action("Create", es: "Creó una caja", en: "Created a box")]
    public static function create(int $boxID): int {
        return $boxID;
    }

    /** The name is there, but only one of the two languages is */
    #[Action("Edit", es: "Editó una caja")]
    public static function edit(int $boxID): int {
        return $boxID;
    }

    /** The translations are positional rather than named */
    #[Action("Remove", "Borró una caja", "Removed a box")]
    public static function remove(int $boxID): int {
        return $boxID;
    }
}
