<?php
namespace Tests\Analysis\Fixture\Log;

use Framework\Log\Attr\Action;
use Framework\Log\Attr\Section;

#[Section("Crates", es: "Cajones", en: "Crates")]
class DuplicateSectionA {

    #[Action("Create", es: "Creó un cajón", en: "Created a crate")]
    public static function create(int $id): int {
        return $id;
    }
}
