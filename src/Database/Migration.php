<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryMigration;
use Framework\Database\SchemaMigration;
use Framework\Core\Settings;
use Framework\Email\EmailTemplate;

/**
 * The Database Migration
 */
class Migration {

    /**
     * Migrates the Data
     * @param boolean $canDelete Optional.
     * @return boolean
     */
    public static function migrate(bool $canDelete = false): bool {
        print("Migrating data...\n");

        print("\nDATABASE MIGRATIONS\n");
        SchemaMigration::migrateData($canDelete);

        print("\nSETTINGS MIGRATIONS\n");
        Settings::migrateData();

        print("\nEMAIL MIGRATIONS\n");
        EmailTemplate::migrateData();


        /** @var DiscoveryMigration[] */
        $appMigrations = Discovery::getClassesWithInterface(DiscoveryMigration::class);
        if (count($appMigrations) > 0) {
            print("\nAPP MIGRATIONS\n");
            foreach ($appMigrations as $migration) {
                $migration::migrateData();
            }
        }

        print("\n\nMigrations completed\n\n");
        return true;
    }
}
