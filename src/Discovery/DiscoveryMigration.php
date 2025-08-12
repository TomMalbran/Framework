<?php
namespace Framework\Discovery;

/**
 * The Discovery Migration
 */
interface DiscoveryMigration {

    /**
     * Migrates the Data
     * @return boolean If the migration was successful.
     */
    public static function migrateData(): bool;

}
