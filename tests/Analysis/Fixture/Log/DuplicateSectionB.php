<?php
namespace Tests\Analysis\Fixture\Log;

use Framework\Log\Attr\Action;
use Framework\Log\Attr\Section;

/** Claims the same section name as DuplicateSectionA */
#[Section("Crates", es: "Cajones", en: "Crates")]
class DuplicateSectionB {

    #[Action("Edit", es: "Editó un cajón", en: "Edited a crate")]
    public static function edit(int $id): int {
        return $id;
    }
}
