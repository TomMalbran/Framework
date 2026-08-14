<?php
namespace Tests\Database;

use Framework\Email\Schema\EmailContentColumn;
use Framework\Email\Schema\EmailContentQuery;
use Framework\System\EmailCode;

use Tests\LiveTestCase;
use Tests\Database\Fixture\OrderedEmails;

/**
 * The ordered writes of the Schema, over a Model that holds a position
 *
 * A Model with a position never stores one itself: the Schema hands out the
 * next one on a create, and closes the gap on a remove. The Email Content is
 * the Framework's only Model with one, so it is what these ask.
 */
class SchemaOrderLiveTest extends LiveTestCase {

    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        // The table is the subject, and its own migration empties it too
        OrderedEmails::empty();
    }

    /**
     * Returns the position of every row, in the order they were made
     * @return list<int>
     */
    private function positions(): array {
        $result = [];
        foreach (OrderedEmails::getEmailContentIDs() as $emailContentID) {
            $result[] = OrderedEmails::getByID($emailContentID)->position;
        }
        return $result;
    }

    /**
     * Adds a row of the given description
     * @param string $description
     * @return int
     */
    private function add(string $description): int {
        return OrderedEmails::add(EmailCode::Test, "en", $description);
    }



    public function testTheTableIsEmptied(): void {
        $this->add("the only one");
        $this->assertSame(1, OrderedEmails::getEntityTotal());

        // A truncate changes no rows, so its success is the statement having
        // run rather than a count of what it touched
        $this->assertTrue(OrderedEmails::empty());
        $this->assertSame(0, OrderedEmails::getEntityTotal());
    }

    public function testTheFirstRowTakesTheFirstPosition(): void {
        $emailContentID = $this->add("the first");

        $this->assertSame(1, OrderedEmails::getByID($emailContentID)->position);
    }

    public function testEachRowTakesTheNextPosition(): void {
        $this->add("the first");
        $this->add("the second");
        $this->add("the third");

        $this->assertSame([ 1, 2, 3 ], $this->positions());
    }

    public function testRemovingARowClosesTheGap(): void {
        $first  = $this->add("the first");
        $second = $this->add("the second");
        $third  = $this->add("the third");

        $this->assertTrue(OrderedEmails::drop($second));

        // The third moves up, rather than the positions keeping a hole
        $this->assertSame(1, OrderedEmails::getByID($first)->position);
        $this->assertSame(2, OrderedEmails::getByID($third)->position);
        $this->assertSame([ 1, 2 ], $this->positions());
    }

    public function testRemovingTheLastRowLeavesTheRest(): void {
        $first = $this->add("the first");
        $last  = $this->add("the last");

        OrderedEmails::drop($last);

        $this->assertSame(1, OrderedEmails::getByID($first)->position);
        $this->assertSame([ 1 ], $this->positions());
    }

    public function testTheNextRowTakesThePositionThatWasFreed(): void {
        $this->add("the first");
        $second = $this->add("the second");
        OrderedEmails::drop($second);

        $this->assertSame(2, OrderedEmails::getByID($this->add("the next"))->position);
    }



    public function testTheRowsComeBackAsTheOptionsOfASelect(): void {
        $first = $this->add("the first");
        $this->add("the second");

        $result = OrderedEmails::select(new EmailContentQuery());

        $this->assertCount(2, $result);
        $this->assertSame($first, $result[0]->key);
        $this->assertSame("the first", $result[0]->value);
    }

    public function testASelectOverNothingIsEmpty(): void {
        $this->assertSame([], OrderedEmails::select(new EmailContentQuery()));
    }

    public function testARowIsMovedUp(): void {
        $first  = $this->add("The first");
        $second = $this->add("The second");
        $third  = $this->add("The third");

        $this->assertTrue(OrderedEmails::move($third, 1));

        $this->assertSame(1, OrderedEmails::getByID($third)->position);
        $this->assertSame(2, OrderedEmails::getByID($first)->position);
        $this->assertSame(3, OrderedEmails::getByID($second)->position);
    }

    public function testARowIsMovedDown(): void {
        $first  = $this->add("The first");
        $second = $this->add("The second");
        $third  = $this->add("The third");

        $this->assertTrue(OrderedEmails::move($first, 3));

        $this->assertSame(3, OrderedEmails::getByID($first)->position);
        $this->assertSame(1, OrderedEmails::getByID($second)->position);
        $this->assertSame(2, OrderedEmails::getByID($third)->position);
    }

    public function testAMoveToTheSamePlaceChangesNothing(): void {
        $first  = $this->add("The first");
        $second = $this->add("The second");

        OrderedEmails::move($second, 2);

        $this->assertSame([ 1, 2 ], $this->positions());
        $this->assertSame(1, OrderedEmails::getByID($first)->position);
    }

    public function testAMoveToNoPlaceIsTheLast(): void {
        // An edit that names no position is asking for the last one, which
        // is how a row is sent to the end without counting the others
        $first  = $this->add("The first");
        $second = $this->add("The second");

        OrderedEmails::move($first, 0);

        $this->assertSame(2, OrderedEmails::getByID($first)->position);
        $this->assertSame(1, OrderedEmails::getByID($second)->position);
    }

    public function testAMovePastTheLastIsTheLast(): void {
        $first = $this->add("The first");
        $this->add("The second");

        OrderedEmails::move($first, 9);

        $this->assertSame(2, OrderedEmails::getByID($first)->position);
    }

    public function testAnEditThatIsNotAMoveLeavesTheOrder(): void {
        $first  = $this->add("The first");
        $second = $this->add("The second");

        $this->assertTrue(OrderedEmails::rename($second, "Another name"));

        $this->assertSame(1, OrderedEmails::getByID($first)->position);
        $this->assertSame(2, OrderedEmails::getByID($second)->position);
        $this->assertSame("Another name", OrderedEmails::getByID($second)->description);
    }



    public function testTheRowsAreSearchedByTheirName(): void {
        $this->add("The first");
        $this->add("Another one");

        $query = new EmailContentQuery();
        $query->description->like("Another");
        $result = OrderedEmails::getEntitySearch($query, EmailContentColumn::Description);

        $this->assertCount(1, $result);
        $this->assertSame("Another one", $result[0]->title);
    }

    public function testTheSqlOfTheSelectIsReadable(): void {
        // An App asks for it while it works, so it is the statement rather
        // than the rows that comes back
        $sql = OrderedEmails::debugSQL();

        $this->assertStringContainsString("SELECT", $sql);
        $this->assertStringContainsString("email_content", $sql);
    }
}
