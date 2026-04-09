<?php
namespace Framework\Discovery\Type;

/**
 * The Discovery Migration
 */
interface DiscoveryMigration {

    /**
     * Migrates the Data
     * @return void
     */
    public static function migrateData(): void;
}
