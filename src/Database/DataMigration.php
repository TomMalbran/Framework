<?php
namespace Framework\Database;

use Framework\Database\Database;

/**
 * The Data Migration
 */
interface DataMigration {

    /**
     * Returns a title for the Migration
     * @return string
     */
    public static function getTitle(): string;

    /**
     * Migrates the Data
     * @param Database $db
     * @return void
     */
    public static function migrate(Database $db): void;

}
