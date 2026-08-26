<?php
namespace Framework\Database;

use Framework\Application;
use Framework\Console;
use Framework\Analysis\Attr\NotTested;
use Framework\Discovery\Discovery;
use Framework\Discovery\DiscoveryConfig;
use Framework\Discovery\Package;
use Framework\Discovery\Type\DiscoveryMigration;
use Framework\Discovery\Attr\ConsoleCommand;
use Framework\Database\Database;
use Framework\Database\SchemaMigration;
use Framework\Database\DataMigration;
use Framework\Provider\Mustache;
use Framework\Core\Configs;
use Framework\Core\MigrationData;
use Framework\Date\Date;
use Framework\Date\Timer;
use Framework\File\Storage;
use Framework\Utils\Arrays;
use Framework\Utils\Strings;

/**
 * The Database Migration
 */
class Migration {

    private const Template = "src/Database/Template/Migration.mu";

    private static string $migrationsPath = "config/migrations";
    private static string $lastApplied    = "";


    /** @var list<array{from:string,to:string}> */
    private static array $tableRenames  = [];

    /** @var list<array{table:string,from:string,to:string}> */
    private static array $columnRenames = [];


    /**
     * Sets the Directory where the Data Migrations are created
     * @param string $migrationsPath
     * @return void
     */
    #[NotTested("It needs a Database")]
    public static function setPath(string $migrationsPath): void {
        if ($migrationsPath !== "") {
            self::$migrationsPath = $migrationsPath;
        }
    }

    /**
     * Sets the last Data Migration that was applied before this system, so that
     * it and the ones before it are stored as applied, without running them
     * @param string $lastApplied
     * @return void
     */
    #[NotTested("It needs a Database")]
    public static function setLastApplied(string $lastApplied): void {
        self::$lastApplied = $lastApplied;
    }

    /**
     * Renames a Table
     * @param string $from
     * @param string $to
     * @return void
     */
    #[NotTested("It needs a Database")]
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
    #[NotTested("It needs a Database")]
    public static function renameColumn(string $table, string $from, string $to): void {
        self::$columnRenames[] = [
            "table" => $table,
            "from"  => $from,
            "to"    => $to,
        ];
    }



    /**
     * Creates a new Data Migration with the given Title
     * @param string $title Optional.
     * @return void
     */
    #[ConsoleCommand("migration")]
    #[NotTested("It needs a Database")]
    public static function createMigration(string $title = ""): void {
        DiscoveryConfig::load();

        $title = Strings::trim($title);
        if ($title === "") {
            $title = Strings::trim(Console::prompt("Title of the migration"));
        }
        if ($title === "") {
            print("The title of the migration is required\n");
            return;
        }

        // The name is the date, so the migrations of every branch can live together,
        // and they are grouped in a directory per year and month
        $date     = Date::now();
        $dirName  = Storage::parsePath($date->getYear(), $date->getMonthZero());
        $basePath = Application::getBasePath(self::$migrationsPath, $dirName);

        // Move to the next second while there is a migration with the same name
        $name     = $date->format("Y-m-d-His");
        $fileName = "$name.php";
        while (Storage::fileExists($basePath, $fileName)) {
            $date     = $date->add(seconds: 1);
            $dirName  = Storage::parsePath($date->getYear(), $date->getMonthZero());
            $basePath = Application::getBasePath(self::$migrationsPath, $dirName);
            $name     = $date->format("Y-m-d-His");
            $fileName = "$name.php";
        }

        // The class has the same date as the name, so it is unique too
        $template = Storage::readFile(Package::getBasePath(self::Template));
        $contents = Mustache::render($template, [
            "class" => "M" . $date->format("Ymd") . "T" . $date->format("His"),
            "title" => $title,
        ]);

        Storage::createDir($basePath);
        Storage::createFile($basePath, $fileName, $contents);

        $printPath = Storage::parsePath(self::$migrationsPath, $dirName, $fileName);
        print("Created the migration $printPath\n");

        // Open the new Migration, so it can be edited right away
        Console::openFile(Storage::parsePath($basePath, $fileName));
    }



    /**
     * Migrates the Data
     * @param string $envFile   Optional.
     * @param bool   $canDelete Optional.
     * @return void
     */
    #[ConsoleCommand("migrate")]
    public static function migrate(
        string $envFile = "",
        bool $canDelete = false,
    ): void {
        $timer = new Timer();
        print("Migrating data...\n");

        DiscoveryConfig::load();
        if ($envFile !== "") {
            print("Using ENV file: $envFile\n");
            Configs::setFileName($envFile);
        }


        // Migrate the Schema
        print("\nDATABASE MIGRATIONS\n");
        SchemaMigration::migrateData(
            self::$tableRenames,
            self::$columnRenames,
            $canDelete,
        );


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
        return self::applyMigrations(self::getMigrations(Application::getBasePath()));
    }

    /**
     * Applies the Data Migrations that are pending
     * @param array<string,class-string<DataMigration>> $migrations
     * @return bool
     */
    #[NotTested("It needs a Database")]
    public static function applyMigrations(array $migrations): bool {
        if (count($migrations) === 0) {
            print("- No data migrations found\n");
            return false;
        }

        // Store the Migrations that ran before this system, so they are not run again
        self::storeApplied($migrations);

        // Determine the Migrations that were not applied yet
        $applied = MigrationData::getAppliedNames();
        $pending = [];
        foreach ($migrations as $name => $className) {
            if (!Arrays::contains($applied, $name)) {
                $pending[$name] = $className;
            }
        }

        if (count($pending) === 0) {
            print("- No data migrations required\n");
            return false;
        }

        // Run the Migrations that are pending
        $amount = count($pending);
        print("Running $amount migrations\n");

        $db = Database::getInstance();
        foreach ($pending as $name => $className) {
            $title = $className::getTitle();

            print("- $name: $title\n");
            $className::migrate($db);
            MigrationData::add($name, $title);
        }
        return true;
    }

    /**
     * Returns all the Data Migrations in the given path, indexed and sorted by their Name
     * @param string $appPath
     * @return array<string,class-string<DataMigration>>
     */
    #[NotTested("It needs a Database")]
    public static function getMigrations(string $appPath): array {
        $filePaths = Storage::getFilesInDir($appPath, recursive: true, skipVendor: true);
        $result    = [];

        foreach ($filePaths as $filePath) {
            if (!Strings::endsWith($filePath, ".php")) {
                continue;
            }

            $content = Storage::readFile($filePath);
            if (!Strings::contains($content, DataMigration::class) ||
                !Strings::contains($content, " implements ")
            ) {
                continue;
            }

            $className = Strings::trim(Strings::substringBetween($content, "class", "implements"));
            include_once $filePath;
            if (!class_exists($className) || !is_subclass_of($className, DataMigration::class)) {
                continue;
            }

            // The file name is the Name used to sort the Migrations and to store them
            $name = Storage::getBaseName(Storage::getFileName($filePath));
            if (isset($result[$name])) {
                print("- There is more than one migration called $name\n");
                continue;
            }

            $result[$name] = $className;
        }

        ksort($result, SORT_NATURAL | SORT_FLAG_CASE);
        return $result;
    }

    /**
     * Stores the Migrations up to the last applied one, so that an App that is
     * already running does not apply a second time the ones that already ran
     * @param array<string,class-string<DataMigration>> $migrations
     * @return void
     */
    private static function storeApplied(array $migrations): void {
        if (self::$lastApplied === "" || !MigrationData::isEmpty()) {
            return;
        }

        $index = 0;
        foreach ($migrations as $name => $className) {
            if (Strings::compare($name, self::$lastApplied) > 0) {
                break;
            }

            MigrationData::add($name, $className::getTitle());
            $index += 1;
        }

        print("- Stored $index migrations that were already applied\n");
    }
}
