<?php
namespace Tests\Database;

use Framework\Database\Database;

use Tests\LiveTestCase;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Database itself, connected to the one kept for the tests
 *
 * The connections are built here rather than taken from the base class, so
 * the connecting is what is being tested rather than what it runs on. Each
 * carries triggerError false, since a failure is the answer being asked for
 * rather than something to stop the run.
 *
 * Three things are left out because they end the run rather than answer:
 * changing to a database that is not there throws, closing a connection makes
 * the destructor throw over the one already closed, and a statement that will
 * not prepare is a fatal.
 */
class DatabaseLiveTest extends LiveTestCase {

    private const Table      = "test_database";
    private const ClosedPort = 1;


    protected function setUp(): void {
        parent::setUp();

        $this->query("DROP TABLE IF EXISTS `" . self::Table . "`");
    }

    protected function tearDown(): void {
        $this->query("DROP TABLE IF EXISTS `" . self::Table . "`");
    }

    /**
     * Opens a connection of its own to the database of the tests
     * @param string $database Optional.
     * @param int    $port     Optional.
     * @return Database
     */
    private function connect(string $database = "framework_test", int $port = 3306): Database {
        return new Database(
            host:         "127.0.0.1",
            database:     $database,
            username:     "root",
            password:     "",
            charset:      "utf8mb4",
            port:         $port,
            triggerError: false,
        );
    }

    /**
     * Opens a connection to a port that refuses it, which is refused at once
     * rather than waited on the way an address that is not there would be
     * @return Database
     */
    private function connectNowhere(): Database {
        return $this->connect(port: self::ClosedPort);
    }

    /**
     * Creates the table of the tests, with an id and a name
     * @return void
     */
    private function createTable(): void {
        $this->query(
            "CREATE TABLE `" . self::Table . "` (" .
            "`THING_ID` int(10) unsigned NOT NULL AUTO_INCREMENT, " .
            "`name` varchar(50) NOT NULL DEFAULT '', " .
            "PRIMARY KEY (`THING_ID`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        );
    }



    public function testAConnectionIsOpen(): void {
        $db = $this->connect();

        $this->assertTrue($db->isConnected());
    }

    /**
     * A connection that cannot be made, which says so rather than erroring
     * @param string $database
     * @param string $host
     * @return void
     */
    #[DataProvider("providerNoConnection")]
    public function testAConnectionThatFailsSaysSo(string $database, int $port): void {
        $db = $this->connect($database, $port);

        $this->assertFalse($db->isConnected());
    }

    /**
     * @return array<string,array{string,int}>
     */
    public static function providerNoConnection(): array {
        return [
            "a database of no name" => [ "", 3306 ],
            "one that is not there" => [ "framework_test_nope", 3306 ],
            "a port that refuses"   => [ "framework_test", self::ClosedPort ],
        ];
    }

    public function testTheDatabaseIsChanged(): void {
        $db = $this->connect();

        $this->assertTrue($db->setDatabase("framework_test"));
        $this->assertTrue($db->isConnected());
    }

    public function testOneThatWasNeverOpenIsNotClosed(): void {
        $db = $this->connectNowhere();

        $this->assertFalse($db->close());
    }



    public function testAStatementIsRun(): void {
        $db = $this->connect();
        $this->createTable();

        $this->assertTrue($db->execute(
            "INSERT INTO `" . self::Table . "` (`name`) VALUES (?)",
            [ "a thing" ],
        ));
        $this->assertGreaterThan(0, $db->getInsertID());
    }

    public function testTheDataIsRead(): void {
        $db = $this->connect();
        $this->createTable();
        $db->execute("INSERT INTO `" . self::Table . "` (`name`) VALUES ('a thing')");

        $result = $db->getData("SELECT * FROM `" . self::Table . "`");

        $this->assertSame(1, $result->count());
        $this->assertSame("a thing", $result->getDict(0)->getString("name"));
    }

    public function testTheBindingsAreOfEveryType(): void {
        $db = $this->connect();
        $this->createTable();
        $db->execute("INSERT INTO `" . self::Table . "` (`name`) VALUES ('a thing')");

        $result = $db->getData(
            "SELECT ? AS `text`, ? AS `number`, ? AS `amount` FROM `" . self::Table . "`",
            [ "a string", 42, 1.5 ],
        );

        $this->assertSame("a string", $result->getDict(0)->getString("text"));
        $this->assertSame(42, $result->getDict(0)->getInt("number"));
        $this->assertSame(1.5, $result->getDict(0)->getFloat("amount"));
    }

    public function testAClosedConnectionRunsNothing(): void {
        $db = $this->connectNowhere();

        $this->assertFalse($db->execute("SELECT 1"));
        $this->assertSame(0, $db->getData("SELECT 1")->count());
    }

    public function testTheStringIsEscaped(): void {
        $db = $this->connect();

        $this->assertSame("O\\'Hara", $db->escape("O'Hara"));
    }

    public function testWithNoConnectionTheStringIsLeftAlone(): void {
        $db = $this->connectNowhere();

        $this->assertSame("O'Hara", $db->escape("O'Hara"));
    }



    public function testTheTablesAreListed(): void {
        $db = $this->connect();
        $this->createTable();

        $this->assertContains(self::Table, $db->getTables());
    }

    public function testTheFilteredTablesAreLeftOut(): void {
        $db = $this->connect();
        $this->createTable();

        $this->assertNotContains(self::Table, $db->getTables([ self::Table ]));
        $this->assertContains(self::Table, $db->getTables([ "nothing" ]));
    }

    public function testATableIsThere(): void {
        $db = $this->connect();
        $this->createTable();

        $this->assertTrue($db->tableExists(self::Table));
        $this->assertFalse($db->tableExists("not_a_table"));
    }

    public function testATableIsEmpty(): void {
        $db = $this->connect();
        $this->createTable();

        $this->assertTrue($db->tableIsEmpty(self::Table));

        $db->execute("INSERT INTO `" . self::Table . "` (`name`) VALUES ('a thing')");
        $this->assertFalse($db->tableIsEmpty(self::Table));
    }

    public function testTheKeysOfATableAreRead(): void {
        $db = $this->connect();
        $this->createTable();

        $this->assertSame([ "THING_ID" ], $db->getPrimaryKeys(self::Table));
        $this->assertSame("THING_ID", $db->getAutoIncrement(self::Table));
    }

    public function testTheFieldsOfATableAreRead(): void {
        $db = $this->connect();
        $this->createTable();

        $fields = $db->getTableFields(self::Table);
        $this->assertCount(2, $fields);
    }

    public function testAColumnIsThere(): void {
        $db = $this->connect();
        $this->createTable();

        $this->assertTrue($db->columnExists(self::Table, "name"));
        $this->assertFalse($db->columnExists(self::Table, "nothing"));
    }

    public function testTheTypeOfAColumnIsRead(): void {
        $db = $this->connect();
        $this->createTable();

        $this->assertSame(
            "varchar(50) NOT NULL DEFAULT ''",
            $db->getColumnType(self::Table, "name"),
        );
    }



    public function testATableIsRenamed(): void {
        $db = $this->connect();
        $this->createTable();

        $db->renameTable(self::Table, self::Table . "_other");

        $this->assertFalse($db->tableExists(self::Table));
        $this->assertTrue($db->tableExists(self::Table . "_other"));
        $db->deleteTable(self::Table . "_other");
    }

    public function testAColumnIsRenamed(): void {
        $db = $this->connect();
        $this->createTable();

        $db->renameColumn(self::Table, "name", "title", "varchar(50) NOT NULL DEFAULT ''");

        $this->assertFalse($db->columnExists(self::Table, "name"));
        $this->assertTrue($db->columnExists(self::Table, "title"));
    }

    public function testAColumnIsAddedAndDropped(): void {
        $db = $this->connect();
        $this->createTable();

        $db->addColumn(self::Table, "amount", "int(10) unsigned NOT NULL DEFAULT 0");
        $this->assertTrue($db->columnExists(self::Table, "amount"));

        $db->deleteColumn(self::Table, "amount");
        $this->assertFalse($db->columnExists(self::Table, "amount"));
    }

    public function testThePrimaryKeyIsChanged(): void {
        $db = $this->connect();
        $this->createTable();

        // The auto increment has to go before the key it is part of
        $db->updateColumn(self::Table, "THING_ID", "int(10) unsigned NOT NULL DEFAULT 0");
        $db->updatePrimary(self::Table, [ "THING_ID", "name" ]);

        $this->assertSame([ "THING_ID", "name" ], $db->getPrimaryKeys(self::Table));

        $db->dropPrimary(self::Table);
        $this->assertSame([], $db->getPrimaryKeys(self::Table));
    }
}
