<?php
namespace Framework\Database;

use Framework\Framework;
use Framework\Application;
use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\DiscoveryMigration;
use Framework\Discovery\ConsoleCommand;
use Framework\Database\SchemaMigration;
use Framework\Database\DataMigration;
use Framework\Core\Configs;
use Framework\Core\SettingData;
use Framework\File\File;
use Framework\Utils\Strings;

use ReflectionClass;

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


        print("\nDATA MIGRATIONS\n");
        self::migrateData();


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

    /**
     * Migrates the Data
     * @return boolean
     */
    public static function migrateData(): bool {
        $appPath    = Application::getAppPath();
        $filePaths  = File::getFilesInDir($appPath, recursive: true, skipVendor: true);
        $migrations = [];

        // Find all the Migrations
        foreach ($filePaths as $filePath) {
            if (!Strings::endsWith($filePath, ".php")) {
                continue;
            }

            $content = File::read($filePath);
            if (Strings::contains($content, DataMigration::class) && Strings::contains($content, " implements ")) {
                $className = trim(Strings::substringBetween($content, "class", "implements"));
                $migrations[$className] = $filePath;
            }
        }

        // No Migrations Found
        if (count($migrations) === 0) {
            print("\n- No data migrations found\n");
            return false;
        }

        // Sort the Migrations using the Class Name
        ksort($migrations);

        // Determine the Migrations to Run
        $startMigration = SettingData::getCore("migration");
        $firstMigration = $startMigration + 1;
        $lastMigration  = count($migrations);
        if ($firstMigration > $lastMigration) {
            print("\n- No data migrations required\n");
            return false;
        }

        // Run the Migrations
        print("\nRunning migrations $firstMigration -> $lastMigration\n");

        $db    = Framework::getDatabase();
        $index = 0;
        foreach ($migrations as $className => $filePath) {
            $index += 1;
            if ($index < $firstMigration) {
                continue;
            }

            include_once $filePath;
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            $instance   = $reflection->newInstance();
            if (!($instance instanceof DataMigration)) {
                continue;
            }

            $title = $instance::getTitle();
            print("- $index: $title\n");
            $instance::migrate($db);
        }

        // Save the Last Migration
        SettingData::setCore("migration", $lastMigration);
        return true;
    }
}
