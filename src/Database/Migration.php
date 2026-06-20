<?php
namespace Framework\Database;

use Framework\Application;
use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\Type\DiscoveryMigration;
use Framework\Discovery\Attr\ConsoleCommand;
use Framework\Database\Database;
use Framework\Database\SchemaMigration;
use Framework\Database\DataMigration;
use Framework\Core\Configs;
use Framework\Core\SettingData;
use Framework\File\Storage;
use Framework\Date\Timer;
use Framework\Utils\Strings;

use ReflectionClass;

/**
 * The Database Migration
 */
class Migration {

    /** @var list<array{from:string,to:string}> */
    private static array $tableRenames  = [];

    /** @var list<array{table:string,from:string,to:string}> */
    private static array $columnRenames = [];


    /**
     * Renames a Table
     * @param string $from
     * @param string $to
     * @return void
     */
    public static function renameTable(string $from, string $to): void {
        self::$tableRenames[] = [
            "from" => $from,
            "to"   => $to,
        ];
    }

    /**
     * Renames a Column
     * @param string $table
     * @param string $from
     * @param string $to
     * @return void
     */
    public static function renameColumn(string $table, string $from, string $to): void {
        self::$columnRenames[] = [
            "table" => $table,
            "from"  => $from,
            "to"    => $to,
        ];
    }



    /**
     * Migrates the Data
     * @param string $envFile Optional.
     * @param bool   $delete  Optional.
     * @return void
     */
    #[ConsoleCommand("migrate")]
    public static function migrate(string $envFile = "", bool $delete = false): void {
        $timer = new Timer();
        print("Migrating data...\n");

        DiscoveryConfig::load();
        if ($envFile !== "") {
            print("Using ENV file: $envFile\n");
            Configs::setFileName($envFile);
        }


        // Migrate the Schema
        print("\nDATABASE MIGRATIONS\n");
        SchemaMigration::migrateData(self::$tableRenames, self::$columnRenames, $delete);


        // Apply other Migrations from the Framework
        $frameClasses = Discovery::findClasses(
            interface:    DiscoveryMigration::class,
            forFramework: true,
        );
        if (count($frameClasses) > 0) {
            print("\nFRAMEWORK MIGRATIONS\n");
            foreach ($frameClasses as $class) {
                $instance = $class->newInstance();
                if ($instance instanceof DiscoveryMigration) {
                    $instance::migrateData();
                }
            }
        }


        // Apply the Migrations from the App
        $appMigrations = Discovery::findClasses(
            interface:    DiscoveryMigration::class,
            forFramework: false,
        );
        if (count($appMigrations) > 0) {
            print("\nAPP MIGRATIONS\n");
            foreach ($appMigrations as $class) {
                $instance = $class->newInstance();
                if ($instance instanceof DiscoveryMigration) {
                    $instance::migrateData();
                }
            }
        }


        // Execute the required Data Migrations
        print("\nDATA MIGRATIONS\n");
        self::migrateData();


        // Calculate and show the time taken
        $time = $timer->getElapsedText();
        print("\nMigrations completed in $time\n");
    }

    /**
     * Migrates the Data
     * @return bool
     */
    public static function migrateData(): bool {
        $appPath    = Application::getBasePath();
        $filePaths  = Storage::getFilesInDir($appPath, recursive: true, skipVendor: true);
        $migrations = [];

        // Find all the Migrations
        foreach ($filePaths as $filePath) {
            if (!Strings::endsWith($filePath, ".php")) {
                continue;
            }

            $content = Storage::readFile($filePath);
            if (Strings::contains($content, DataMigration::class) &&
                Strings::contains($content, " implements ")
            ) {
                $className = trim(Strings::substringBetween($content, "class", "implements"));
                $migrations[$className] = $filePath;
            }
        }

        // No Migrations Found
        if (count($migrations) === 0) {
            print("- No data migrations found\n");
            return false;
        }

        // Sort the Migrations using the Class Name
        ksort($migrations, SORT_NATURAL | SORT_FLAG_CASE);

        // Determine the Migrations to Run
        $startMigration = SettingData::getCore("migration");
        $firstMigration = $startMigration + 1;
        $lastMigration  = count($migrations);
        if ($firstMigration > $lastMigration) {
            print("- No data migrations required\n");
            return false;
        }

        // Run the Migrations
        $firstMigration += 1;
        $lastMigration  += 1;
        print("Running migrations $firstMigration -> $lastMigration\n");

        $db    = Database::getInstance();
        $index = 1;
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
        SettingData::setCore("migration", $lastMigration - 1);
        return true;
    }
}
