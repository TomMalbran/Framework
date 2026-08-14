<?php
namespace Tests\Database;

use Framework\Application;
use Framework\Core\Configs;
use Framework\Core\MigrationData;
use Framework\Database\Migration;
use Framework\File\Storage;

use Tests\Database\Fixture\RanMigrations;
use Tests\LiveTestCase;
use Tests\TestHelpers;

/**
 * The Data Migrations, the files of an App that run once and are written down
 *
 * They are found by reading the source of every file for one that implements
 * the interface, so the ones here are written out into a directory of their
 * own rather than declared, and each carries a class of its own name so that
 * including one does not clash with the last.
 *
 * A creation is only asked for with a title: without one it reads the answer
 * from the terminal, which a test has none of and would wait on.
 */
class DataMigrationLiveTest extends LiveTestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Database/.tmp_migrations";

    private mixed $lastApplied    = null;
    private mixed $migrationsPath = null;


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        $this->lastApplied    = $this->getPrivateStaticProperty(Migration::class, "lastApplied");
        $this->migrationsPath = $this->getPrivateStaticProperty(Migration::class, "migrationsPath");
        $this->query("DELETE FROM `migrations`");
        RanMigrations::reset();
        Storage::createDir($this->basePath());
    }

    protected function tearDown(): void {
        $this->setPrivateStaticProperty(Migration::class, "lastApplied", $this->lastApplied);
        $this->setPrivateStaticProperty(Migration::class, "migrationsPath", $this->migrationsPath);
        Storage::deleteDir($this->basePath());
        RanMigrations::reset();
    }

    /**
     * Returns the directory the migrations of the tests are written into
     * @param string $dir Optional.
     * @return string
     */
    private function basePath(string $dir = ""): string {
        return Application::getBasePath(self::FixtureDir, $dir);
    }

    /**
     * Writes a Data Migration, named after the date the way a real one is
     * @param string $name  The file name, which is what it is stored under
     * @param string $title Optional.
     * @param string $dir   Optional.
     * @param string $class Optional. The class it declares, unique of itself
     * @return void
     */
    private function writeMigration(
        string $name,
        string $title = "A migration",
        string $dir = "",
        string $class = "",
    ): void {
        if ($class === "") {
            $class = "M" . str_replace("-", "", Storage::getBaseName($name));
        }
        $path  = $this->basePath($dir);
        Storage::createDir($path);

        Storage::writeFile("$path/$name.php", <<<PHP
        <?php
        use Framework\\Database\\DataMigration;
        use Framework\\Database\\Database;
        use Tests\\Database\\Fixture\\RanMigrations;

        class $class implements DataMigration {
            public static function getTitle(): string {
                return "$title";
            }

            public static function migrate(Database \$db): void {
                RanMigrations::add("$name");
            }
        }
        PHP);
    }

    /**
     * Creates a Migration with the given title, keeping what it printed
     *
     * The command opens the file it wrote in the editor when it is run from
     * one, which is the whole point of it there and a nuisance here: the
     * tests are usually run from the terminal of VS Code, and every run
     * would leave a handful of migrations open. So the variable it reads to
     * know where it is running is taken away for as long as this takes
     * @param string $title
     * @return string
     */
    private function create(string $title): string {
        $termProgram = getenv("TERM_PROGRAM");
        putenv("TERM_PROGRAM");

        ob_start();
        try {
            Migration::createMigration($title);
        } finally {
            $output = ob_get_clean();
            if ($termProgram !== false) {
                putenv("TERM_PROGRAM=$termProgram");
            }
        }
        return (string)$output;
    }

    /**
     * Returns the path of the Migration file of the given name
     * @param string $name
     * @return string
     */
    private function fileOf(string $name): string {
        $parts = explode("-", $name);
        return $this->basePath("{$parts[0]}/{$parts[1]}") . "/$name.php";
    }

    /**
     * Finds the migrations written for the test
     * @return array<string,string>
     */
    private function find(): array {
        return Migration::getMigrations($this->basePath());
    }

    /**
     * Applies the given migrations, keeping what it printed
     * @param array<string,string>|null $migrations Optional.
     * @return string
     */
    private function apply(?array $migrations = null): string {
        ob_start();
        try {
            Migration::applyMigrations($migrations ?? $this->find());
        } finally {
            $output = ob_get_clean();
        }
        return (string)$output;
    }



    public function testAMigrationIsFound(): void {
        $this->writeMigration("2020-01-01-000000", "The first one");

        $this->assertSame(
            [ "2020-01-01-000000" => "M20200101000000" ],
            $this->find(),
        );
    }

    public function testTheyAreFoundInTheirFolders(): void {
        $this->writeMigration("2020-01-01-000001", dir: "2020/01");
        $this->writeMigration("2021-02-02-000002", dir: "2021/02");

        $this->assertCount(2, $this->find());
    }

    public function testTheyComeBackSortedByName(): void {
        $this->writeMigration("2021-01-01-000003");
        $this->writeMigration("2020-01-01-000004");
        $this->writeMigration("2020-06-01-000005");

        $this->assertSame([
            "2020-01-01-000004",
            "2020-06-01-000005",
            "2021-01-01-000003",
        ], array_keys($this->find()));
    }

    public function testAFileThatIsNotOneIsSkipped(): void {
        $this->writeMigration("2020-01-01-000006");
        Storage::writeFile($this->basePath() . "/notes.txt", "not a migration");
        Storage::writeFile($this->basePath() . "/other.php", "<?php class Other {}");

        $this->assertCount(1, $this->find());
    }

    public function testThereAreNoneToFind(): void {
        $this->assertSame([], $this->find());
    }



    public function testAMigrationIsApplied(): void {
        $this->writeMigration("2020-01-01-000010", "The first one");

        $output = $this->apply();

        $this->assertStringContainsString("Running 1 migrations", $output);
        $this->assertStringContainsString("2020-01-01-000010: The first one", $output);
        $this->assertSame([ "2020-01-01-000010" ], RanMigrations::getAll());
    }

    public function testAnAppliedOneIsWrittenDown(): void {
        $this->writeMigration("2020-01-01-000011", "The first one");
        $this->apply();

        $this->assertSame([ "2020-01-01-000011" ], MigrationData::getAppliedNames());
    }

    public function testTheyAreAppliedInOrder(): void {
        $this->writeMigration("2021-01-01-000012");
        $this->writeMigration("2020-01-01-000013");

        $this->apply();

        $this->assertSame([
            "2020-01-01-000013",
            "2021-01-01-000012",
        ], RanMigrations::getAll());
    }

    public function testOneAlreadyAppliedIsLeftAlone(): void {
        $this->writeMigration("2020-01-01-000014");
        $this->apply();
        RanMigrations::reset();

        $this->assertStringContainsString("No data migrations required", $this->apply());
        $this->assertSame([], RanMigrations::getAll());
    }

    public function testOnlyTheNewOneIsApplied(): void {
        $this->writeMigration("2020-01-01-000015");
        $this->apply();
        RanMigrations::reset();

        $this->writeMigration("2021-01-01-000016");
        $this->apply();

        $this->assertSame([ "2021-01-01-000016" ], RanMigrations::getAll());
        $this->assertCount(2, MigrationData::getAppliedNames());
    }

    public function testThereAreNoneToApply(): void {
        $this->assertStringContainsString("No data migrations found", $this->apply([]));
    }



    public function testTheOnesBeforeTheLastAreWrittenDown(): void {
        // An App that ran before this was written says which one it got to,
        // and the ones up to it are stored rather than run
        $this->writeMigration("2020-01-01-000020");
        $this->writeMigration("2020-06-01-000021");
        $this->writeMigration("2021-01-01-000022");
        $this->setPrivateStaticProperty(Migration::class, "lastApplied", "2020-06-01-000021");

        $output = $this->apply();

        $this->assertStringContainsString("Stored 2 migrations that were already applied", $output);
        $this->assertSame([ "2021-01-01-000022" ], RanMigrations::getAll());
        $this->assertCount(3, MigrationData::getAppliedNames());
    }

    public function testTheLastAppliedIsOnlyReadOnce(): void {
        // It only means anything for a table with nothing in it, so a second
        // run does not store them all over again
        $this->writeMigration("2020-01-01-000023");
        $this->writeMigration("2021-01-01-000024");
        $this->setPrivateStaticProperty(Migration::class, "lastApplied", "2020-01-01-000023");
        $this->apply();

        $output = $this->apply();

        $this->assertStringNotContainsString("Stored", $output);
        $this->assertCount(2, MigrationData::getAppliedNames());
    }

    public function testWithNoLastAppliedTheyAllRun(): void {
        $this->writeMigration("2020-01-01-000025");
        $this->writeMigration("2021-01-01-000026");
        $this->setPrivateStaticProperty(Migration::class, "lastApplied", "");

        $this->apply();

        $this->assertCount(2, RanMigrations::getAll());
    }

    public function testThePathIsSet(): void {
        Migration::setPath("config/other");

        $this->assertSame(
            "config/other",
            $this->getPrivateStaticProperty(Migration::class, "migrationsPath"),
        );
    }

    public function testAnEmptyPathIsNotSet(): void {
        Migration::setPath("config/other");
        Migration::setPath("");

        $this->assertSame(
            "config/other",
            $this->getPrivateStaticProperty(Migration::class, "migrationsPath"),
        );
    }

    public function testTheLastAppliedIsSet(): void {
        Migration::setLastApplied("2020-01-01-000000");

        $this->assertSame(
            "2020-01-01-000000",
            $this->getPrivateStaticProperty(Migration::class, "lastApplied"),
        );
    }

    public function testTwoOfTheSameNameAreOne(): void {
        // The name is what they are stored under, so a second one of it would
        // be taken for the one that already ran. Each declares a class of its
        // own, since two of one name is a fatal before the check is reached
        $this->writeMigration("2020-01-01-000030", dir: "2020/01", class: "M20200101000030A");
        $this->writeMigration("2020-01-01-000030", dir: "2021/02", class: "M20200101000030B");

        ob_start();
        $result = $this->find();
        $output = (string)ob_get_clean();

        $this->assertCount(1, $result);
        $this->assertStringContainsString(
            "There is more than one migration called 2020-01-01-000030",
            $output,
        );
    }

    public function testThereAreNoneInTheRepository(): void {
        // Which is what the whole of it answers, since a Framework has none
        ob_start();
        $result = Migration::migrateData();
        $output = (string)ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString("No data migrations found", $output);
    }



    public function testAMigrationIsCreated(): void {
        Migration::setPath(self::FixtureDir);

        $output = $this->create("The new one");

        $this->assertStringContainsString("Created the migration", $output);
        $this->assertCount(1, $this->find());
    }

    public function testTheOneCreatedCarriesItsTitle(): void {
        Migration::setPath(self::FixtureDir);
        $this->create("The new one");

        $name     = array_key_first($this->find());
        $contents = Storage::readFile($this->fileOf($name));
        $this->assertStringContainsString("The new one", $contents);
        $this->assertStringContainsString("implements DataMigration", $contents);
    }

    public function testTheOneCreatedIsNamedAfterTheDate(): void {
        // They live in a directory per year and month, so the ones of every
        // branch can be told apart and still sort together
        Migration::setPath(self::FixtureDir);
        $this->create("The new one");

        $name = array_key_first($this->find());
        $this->assertMatchesRegularExpression("/^\d{4}-\d{2}-\d{2}-\d{6}$/", $name);
        $this->assertTrue(Storage::fileExists($this->fileOf($name)));
    }

    public function testASecondOneTakesTheNextSecond(): void {
        Migration::setPath(self::FixtureDir);
        $this->create("The first one");
        $this->create("The second one");

        $names = array_keys($this->find());
        $this->assertCount(2, $names);
        $this->assertNotSame($names[0], $names[1]);
    }


    public function testTheWholeMigrationIsRun(): void {
        // Which is the migrate of the command line: the tables, then the
        // Migrations of the Framework, then the ones written by hand
        ob_start();
        try {
            Migration::migrate();
        } finally {
            $output = (string)ob_get_clean();
        }

        $this->assertStringContainsString("DATABASE MIGRATIONS", $output);
        $this->assertStringContainsString("FRAMEWORK MIGRATIONS", $output);
        $this->assertStringContainsString("DATA MIGRATIONS", $output);
        $this->assertStringContainsString("Migrations completed in", $output);
    }

    public function testTheEnvFileIsNamed(): void {
        // A deploy runs the migration against one environment at a time, and
        // the file it reads is given rather than found
        $fileName = $this->getPrivateStaticProperty(Configs::class, "fileName");

        ob_start();
        try {
            Migration::migrate("staging");
        } finally {
            $output = (string)ob_get_clean();
            $this->setPrivateStaticProperty(Configs::class, "fileName", $fileName);
        }

        $this->assertStringContainsString("Using ENV file: staging", $output);
    }
}
