<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryMigration;
use Framework\Discovery\ConsoleCommand;
use Framework\Database\SchemaMigration;
use Framework\Core\Configs;
use Framework\Core\Settings;
use Framework\Email\EmailTemplate;

/**
 * The Database Migration
 */
class Migration {

    /**
     * Migrates the Data
     * @param string  $envFile Optional.
     * @param boolean $delete  Optional.
     * @return boolean
     */
    #[ConsoleCommand("migrate")]
    public static function migrate(string $envFile = "", bool $delete = false): bool {
        $timeStart = microtime(true);
        print("Migrating data...\n");

        if ($envFile !== "") {
            print("Using ENV file: $envFile\n");
            Configs::setFileName($envFile);
        }

        print("\nDATABASE MIGRATIONS\n");
        SchemaMigration::migrateData($delete);

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


        // Calculate and show the time taken
        $timeEnd = microtime(true);
        $seconds = $timeEnd - $timeStart;
        $minutes = round(($seconds / 60) * 100) / 100;
        print("\n\nMigrations completed in $minutes m ($seconds s)\n\n");
        return true;
    }
}
