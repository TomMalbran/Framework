<?php
namespace Tests\Analysis\Fixture\Log;

use Framework\Log\Attr\Section;

#[Section("Orphan", es: "Huerfano", en: "Orphan")]
class SectionWithoutAction {

    public static function doesNothingLoggable(int $id): int {
        return $id;
    }
}
