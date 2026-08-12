<?php
namespace Tests\Email;

use Framework\Email\EmailWhiteList;
use Framework\Email\Schema\EmailWhiteListRequest;
use Framework\Utils\Arrays;

use Tests\LiveTestCase;

/**
 * The White List, the addresses still written to while an app is not live
 */
class EmailWhiteListLiveTest extends LiveTestCase {

    private const Email = "white@framework.test";


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        $this->query("DELETE FROM `email_white_list`");
    }

    /**
     * Puts the given address on the list and hands back its ID
     * @param string $email Optional.
     * @return int
     */
    private function add(string $email = self::Email): int {
        return EmailWhiteList::add(new EmailWhiteListRequest(
            email:       $email,
            description: "Someone of the tests",
        ));
    }



    public function testAnAddressAddedIsOnTheList(): void {
        $emailID = $this->add();

        $this->assertGreaterThan(0, $emailID);
        $this->assertTrue(EmailWhiteList::emailExists(self::Email));
        $this->assertSame(self::Email, EmailWhiteList::getByID($emailID)->email);
    }

    public function testAnAddressNeverAddedIsOff(): void {
        $this->assertFalse(EmailWhiteList::emailExists("nobody@framework.test"));
    }

    public function testAnAddressIsFoundByItself(): void {
        $emailID = $this->add();

        $this->assertSame($emailID, EmailWhiteList::getByEmail(self::Email)->emailID);
    }

    public function testAnEntryIsChanged(): void {
        $emailID = $this->add();

        $this->assertTrue(EmailWhiteList::edit($emailID, new EmailWhiteListRequest(
            email:       "other@framework.test",
            description: "Someone else of the tests",
        )));

        $this->assertFalse(EmailWhiteList::emailExists(self::Email));
        $this->assertTrue(EmailWhiteList::emailExists("other@framework.test"));
    }

    public function testAnEntryRemovedIsOff(): void {
        $emailID = $this->add();

        $this->assertTrue(EmailWhiteList::remove($emailID));

        $this->assertFalse(EmailWhiteList::emailExists(self::Email));
        $this->assertFalse(EmailWhiteList::exists($emailID));
    }

    public function testAnAddressIsOnTheListOnlyOnce(): void {
        // The check the create makes is what the unique field is for, and it
        // is asked with the ID of the entry itself left out
        $emailID = $this->add();

        $this->assertTrue(EmailWhiteList::emailExists(self::Email));
        $this->assertFalse(EmailWhiteList::emailExists(self::Email, skipID: $emailID));
    }

    public function testTheListHoldsEveryAddress(): void {
        $this->add();
        $this->add("other@framework.test");

        $request = new EmailWhiteListRequest();
        $this->assertSame(2, EmailWhiteList::getTotal($request));

        $emails = Arrays::createArray(EmailWhiteList::getList($request), "email");
        $this->assertSame([ self::Email, "other@framework.test" ], $emails);
    }
}
