<?php
namespace Tests\Analysis\Fixture\Log;

use Framework\Log\Attr\Section;

#[Section("Orphan", es: "Huérfano", en: "Orphan")]
class SectionWithoutAction {

    public static function doesNotCallLog(int $id): int {
        return $id;
    }
}
