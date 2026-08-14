<?php
namespace Tests\Log;

use Framework\Log\SessionLog;

use Tests\LiveTestCase;

/**
 * The Sessions Log, one row per stretch of time a Credential was signed in
 */
class SessionLogLiveTest extends LiveTestCase {

    private const CredentialID = 9001;
    private const OtherID      = 9002;


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        $this->query("DELETE FROM `log_session`");
    }



    public function testASessionIsStarted(): void {
        $sessionID = SessionLog::start(self::CredentialID);

        $this->assertGreaterThan(0, $sessionID);

        $session = SessionLog::getByID($sessionID);
        $this->assertSame(self::CredentialID, $session->credentialID);
        $this->assertTrue($session->isOpen);
    }

    public function testTheOpenOneIsFound(): void {
        $sessionID = SessionLog::start(self::CredentialID);

        $this->assertSame($sessionID, SessionLog::getID(self::CredentialID));
    }

    public function testACredentialWithNoneHasNone(): void {
        SessionLog::start(self::CredentialID);

        $this->assertSame(0, SessionLog::getID(self::OtherID));
    }

    public function testEndingClosesIt(): void {
        $sessionID = SessionLog::start(self::CredentialID);

        $this->assertTrue(SessionLog::end($sessionID));

        $this->assertFalse(SessionLog::getByID($sessionID)->isOpen);
    }

    public function testAClosedOneIsNotFound(): void {
        $sessionID = SessionLog::start(self::CredentialID);
        SessionLog::end($sessionID);

        $this->assertSame(0, SessionLog::getID(self::CredentialID));
    }

    public function testOnlyOneSessionIsOpen(): void {
        // A sign in from a browser that died never ended its session, so
        // starting a new one closes whatever is still open: the actions
        // always land on the session of the sign in that made them
        $first  = SessionLog::start(self::CredentialID);
        $second = SessionLog::start(self::CredentialID);

        $this->assertSame($second, SessionLog::getID(self::CredentialID));
        $this->assertFalse(SessionLog::getByID($first)->isOpen);
    }

    public function testAnotherCredentialKeepsItsOwn(): void {
        $other = SessionLog::start(self::OtherID);
        SessionLog::start(self::CredentialID);

        $this->assertSame($other, SessionLog::getID(self::OtherID));
    }

    public function testTheOldSessionsAreDeleted(): void {
        $sessionID = SessionLog::start(self::CredentialID);
        $this->query(
            "UPDATE `log_session` SET `createdTime` = UNIX_TIMESTAMP() - 400 * 86400 " .
            "WHERE `SESSION_ID` = $sessionID",
        );
        $young = SessionLog::start(self::OtherID);

        $this->assertTrue(SessionLog::deleteOld());

        $this->assertFalse(SessionLog::exists($sessionID));
        $this->assertTrue(SessionLog::exists($young));
    }

    public function testThereIsNothingOldToDelete(): void {
        SessionLog::start(self::CredentialID);

        $this->assertFalse(SessionLog::deleteOld());
    }
}
