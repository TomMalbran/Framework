<?php
use Framework\Database\DataMigration;
use Framework\Database\Database;

class {{class}} implements DataMigration {

    #[\Override]
    public static function getTitle(): string {
        return "{{title}}";
    }

    #[\Override]
    public static function migrate(Database $db): void {
    }
}
