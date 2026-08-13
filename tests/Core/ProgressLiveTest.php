<?php
namespace Tests\Core;

use Framework\Auth\Auth;
use Framework\Auth\Credential;
use Framework\Auth\Schema\CredentialRequest;
use Framework\Auth\Schema\CredentialStatus;
use Framework\Core\Progress;
use Framework\System\Access;

use Tests\LiveTestCase;

/**
 * The Progress a long request reports, kept on the Credential running it
 *
 * The update writes a space and flushes it, to notice a browser that hung up,
 * so the ones that call it are run inside a buffer of their own.
 */
class ProgressLiveTest extends LiveTestCase {

    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        Auth::logout();
        $this->query("DELETE FROM `credential`");
    }

    protected function tearDown(): void {
        Auth::logout();
    }

    /**
     * Signs in a Credential and hands back its ID
     * @return int
     */
    private function signIn(): int {
        $credentialID = Credential::create(new CredentialRequest(
            firstName: "Ana",
            lastName:  "Suárez",
            email:     "progress@framework.test",
            password:  "a-password-of-the-tests",
            access:    Access::Admin,
            status:    CredentialStatus::Active,
        ));

        Auth::login(Credential::getByID($credentialID, complete: true));
        return $credentialID;
    }

    /**
     * Runs the given callback with what it writes thrown away
     * @param callable $callback
     * @return void
     */
    private function quietly(callable $callback): void {
        // The start throws away a buffer of its own, so what is put back is
        // whatever is left over this one rather than the one it closed
        $level = ob_get_level();
        ob_start();
        try {
            $callback();
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }



    public function testThereIsNoneForNobody(): void {
        $this->assertSame(0, Progress::get());
    }

    public function testItStartsAtNothing(): void {
        $credentialID = $this->signIn();
        Credential::setProgress($credentialID, 40);

        $this->quietly(static function (): void {
            Progress::start();
        });

        $this->assertSame(0, Credential::getByID($credentialID)->progressValue);
    }

    public function testItIsUpdated(): void {
        $credentialID = $this->signIn();

        $this->quietly(static function (): void {
            Progress::update(40);
        });

        $this->assertSame(40, Credential::getByID($credentialID)->progressValue);
    }

    public function testItIsIncremented(): void {
        $credentialID = $this->signIn();
        Credential::setProgress($credentialID, 40);
        Auth::updateCredential();

        $this->quietly(static function (): void {
            Progress::increment(5);
        });

        $this->assertSame(45, Credential::getByID($credentialID)->progressValue);
    }

    public function testItIsIncrementedByOne(): void {
        $credentialID = $this->signIn();
        Credential::setProgress($credentialID, 40);
        Auth::updateCredential();

        $this->quietly(static function (): void {
            Progress::increment();
        });

        $this->assertSame(41, Credential::getByID($credentialID)->progressValue);
    }

    public function testItIsReadFromTheCredential(): void {
        $credentialID = $this->signIn();
        Credential::setProgress($credentialID, 40);
        Auth::updateCredential();

        $this->assertSame(40, Progress::get());
    }

    public function testItEndsAtNothing(): void {
        $credentialID = $this->signIn();
        Credential::setProgress($credentialID, 40);

        Progress::end();

        $this->assertSame(0, Credential::getByID($credentialID)->progressValue);
    }
}
