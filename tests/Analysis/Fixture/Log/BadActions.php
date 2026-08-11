<?php
// spell-checker: ignore  modifié
namespace Tests\Analysis\Fixture\Log;

use Framework\Log\Attr\Action;
use Framework\Log\Attr\Section;

#[Section("Bad", es: "Malo", en: "Bad")]
class BadActions {

    /** No arguments at all, so there is not even a name */
    #[Action]
    public static function noName(int $id): int {
        return $id;
    }

    /** A language nobody asked for */
    #[Action("Edit", es: "Editó algo", en: "Edited something", fr: "A modifié")]
    public static function badLanguage(int $id): int {
        return $id;
    }
}
