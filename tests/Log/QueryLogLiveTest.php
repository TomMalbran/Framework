<?php
namespace Tests\Log;

use Framework\Log\QueryLog;
use Framework\Log\Schema\LogQueryRequest;

use Tests\LiveTestCase;
use Tests\TestHelpers;

/**
 * The Query Log, one row per statement with what it has cost so far
 */
class QueryLogLiveTest extends LiveTestCase {
    use TestHelpers;

    private const Expression = "SELECT * FROM `thing` WHERE `id` = ?";


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        $this->query("DELETE FROM `log_query`");
    }

    /**
     * Returns the one Query that was logged
     * @return \Framework\Log\Schema\LogQueryEntity
     */
    private function onlyOne(): object {
        $list = QueryLog::getList(new LogQueryRequest());
        $this->assertCount(1, $list);
        return $list[0];
    }



    public function testAQueryIsLogged(): void {
        QueryLog::createOrEdit(12.7, self::Expression, [ 42 ]);

        $log = $this->onlyOne();
        $this->assertSame(1, $log->amount);
        $this->assertSame(12, $log->elapsedTime);
        $this->assertSame(12, $log->totalTime);
        $this->assertFalse($log->isResolved);
    }

    public function testTheParamsAreWrittenIn(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);

        $this->assertSame("SELECT * FROM `thing` WHERE `id` = 42", $this->onlyOne()->expression);
    }

    public function testAStringParamIsQuoted(): void {
        QueryLog::createOrEdit(1, "SELECT * FROM `thing` WHERE `name` = ?", [ "Ana" ]);

        $this->assertSame(
            "SELECT * FROM `thing` WHERE `name` = 'Ana'",
            $this->onlyOne()->expression,
        );
    }

    public function testEachParamFillsTheNextOne(): void {
        QueryLog::createOrEdit(1, "SELECT ? FROM `thing` WHERE `id` = ?", [ "a", 7 ]);

        $this->assertSame("SELECT 'a' FROM `thing` WHERE `id` = 7", $this->onlyOne()->expression);
    }

    public function testTheSpacesAreSquashed(): void {
        // The expressions are built over several lines, and the one logged is
        // the same statement however it was laid out
        QueryLog::createOrEdit(1, "SELECT   *    FROM  `thing`", []);

        $this->assertSame("SELECT * FROM `thing`", $this->onlyOne()->expression);
    }

    public function testTheSameQueryIsCounted(): void {
        QueryLog::createOrEdit(10, self::Expression, [ 42 ]);
        QueryLog::createOrEdit(4, self::Expression, [ 42 ]);
        QueryLog::createOrEdit(7, self::Expression, [ 42 ]);

        $log = $this->onlyOne();
        $this->assertSame(3, $log->amount);
        $this->assertSame(21, $log->totalTime);

        // The elapsed one is the worst it has been, not the last
        $this->assertSame(10, $log->elapsedTime);
    }

    public function testAnotherQueryIsAnotherRow(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);
        QueryLog::createOrEdit(1, self::Expression, [ 7 ]);

        $this->assertSame(2, QueryLog::getEntityTotal());
    }

    public function testOneCountedAgainIsNotResolved(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);
        $logID = $this->onlyOne()->logID;
        QueryLog::markResolved($logID);
        $this->assertTrue(QueryLog::getByID($logID)->isResolved);

        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);

        $this->assertFalse(QueryLog::getByID($logID)->isResolved);
    }

    public function testAQueryIsMarkedAsResolved(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);
        $logID = $this->onlyOne()->logID;

        QueryLog::markResolved($logID);

        $this->assertTrue(QueryLog::getByID($logID)->isResolved);
    }

    public function testAQueryIsDeleted(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);
        $logID = $this->onlyOne()->logID;

        QueryLog::delete($logID);

        $this->assertFalse(QueryLog::exists($logID));
    }

    public function testSeveralAreDeletedAtOnce(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);
        QueryLog::createOrEdit(1, self::Expression, [ 7 ]);

        QueryLog::delete(QueryLog::getLogIDs());

        $this->assertSame(0, QueryLog::getEntityTotal());
    }

    public function testTheListIsFilteredByResolved(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);
        QueryLog::markResolved($this->onlyOne()->logID);
        QueryLog::createOrEdit(1, self::Expression, [ 7 ]);

        $this->assertSame(1, QueryLog::getTotal(new LogQueryRequest(isResolved: "yes")));
        $this->assertSame(1, QueryLog::getTotal(new LogQueryRequest(isResolved: "no")));
        $this->assertSame(2, QueryLog::getTotal(new LogQueryRequest()));
    }

    public function testTheListIsSearched(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);
        QueryLog::createOrEdit(1, "SELECT * FROM `other`", []);

        $request = new LogQueryRequest(search: "other");
        $this->assertSame(1, QueryLog::getTotal($request));
        $this->assertSame("SELECT * FROM `other`", QueryLog::getList($request)[0]->expression);
    }

    public function testTheOldOnesAreDeleted(): void {
        QueryLog::createOrEdit(1, self::Expression, [ 42 ]);
        $logID = $this->onlyOne()->logID;
        $this->query(
            "UPDATE `log_query` SET `createdTime` = UNIX_TIMESTAMP() - 91 * 86400 " .
            "WHERE `LOG_ID` = $logID",
        );
        QueryLog::createOrEdit(1, self::Expression, [ 7 ]);

        QueryLog::deleteOld();

        $this->assertFalse(QueryLog::exists($logID));
        $this->assertSame(1, QueryLog::getEntityTotal());
    }


    public function testASlowQueryLogsItself(): void {
        // The Database writes down anything slower than the config says, and
        // the write it does to log it is not itself logged. The limit is in
        // whole seconds, so the query has to take one
        $this->setConfig("DB_LOG_TIME", 1);

        try {
            $this->db()->getData("SELECT SLEEP(1)");
        } finally {
            $this->setConfig("DB_LOG_TIME", 0);
        }

        $log = $this->onlyOne();
        $this->assertStringContainsString("SLEEP(1)", $log->expression);
        $this->assertSame(1, $log->amount);
    }
}
