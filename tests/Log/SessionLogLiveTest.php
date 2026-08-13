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

    public function testTheFirstOpenOneIsFound(): void {
        // Two are open at once only when a sign in did not close the one
        // before, and it is the older that the actions are then logged
        // against, since nothing orders the lookup
        $first = SessionLog::start(self::CredentialID);
        SessionLog::start(self::CredentialID);

        $this->assertSame($first, SessionLog::getID(self::CredentialID));
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
