<?php
namespace Framework\Database;

use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\DiscoveryMigration;
use Framework\Discovery\ConsoleCommand;
use Framework\Database\SchemaMigration;
use Framework\Core\Configs;

/**
 * The Database Migration
 */
class Migration {

    /** @var array{from:string,to:string}[] */
    private static array $tableRenames  = [];

    /** @var array{table:string,from:string,to:string}[] */
    private static array $columnRenames = [];


    /**
     * Renames a Table
     * @param string $from
     * @param string $to
     * @return boolean
     */
    public static function renameTable(string $from, string $to): bool {
        self::$tableRenames[] = [
            "from" => $from,
            "to"   => $to,
        ];
        return true;
    }

    /**
     * Renames a Column
     * @param string $table
     * @param string $from
     * @param string $to
     * @return boolean
     */
    public static function renameColumn(string $table, string $from, string $to): bool {
        self::$columnRenames[] = [
            "table" => $table,
            "from"  => $from,
            "to"    => $to,
        ];
        return true;
    }



    /**
     * Migrates the Data
     * @param string  $envFile Optional.
     * @param boolean $delete  Optional.
     * @return boolean
     */
    #[ConsoleCommand("migrate")]
    public static function migrate(string $envFile = "", bool $delete = false): bool {
        DiscoveryConfig::load();

        $timeStart = microtime(true);
        print("Migrating data...\n");

        if ($envFile !== "") {
            print("Using ENV file: $envFile\n");
            Configs::setFileName($envFile);
        }

        print("\nDATABASE MIGRATIONS\n");
        SchemaMigration::migrateData(self::$tableRenames, self::$columnRenames, $delete);


        /** @var DiscoveryMigration[] */
        $frameMigrations = Discovery::getClassesWithInterface(DiscoveryMigration::class, forFramework: true);
        if (count($frameMigrations) > 0) {
            print("\nFRAMEWORK MIGRATIONS\n");
            foreach ($frameMigrations as $migration) {
                $migration::migrateData();
            }
        }


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
