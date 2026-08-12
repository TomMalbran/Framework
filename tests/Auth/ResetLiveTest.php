<?php
namespace Tests\Auth;

use Framework\Auth\Reset;

use Tests\LiveTestCase;

/**
 * The pending password resets, one row per credential that asked for one
 */
class ResetLiveTest extends LiveTestCase {

    private const CredentialID = 900011;
    private const OtherID      = 900012;
    private const Email        = "reset@framework.test";
    private const OtherEmail   = "other-reset@framework.test";


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        Reset::delete(self::CredentialID);
        Reset::delete(self::OtherID);
        Reset::delete(0, self::Email);
        Reset::delete(0, self::OtherEmail);
    }



    public function testTheCodeIsMadeOfTheSetsAsked(): void {
        $resetCode = Reset::create(self::CredentialID, self::Email);

        $this->assertSame(6, strlen($resetCode));
        $this->assertMatchesRegularExpression("/^[A-Z0-9]{6}$/", $resetCode);
    }

    public function testTheCodeCanBeAskedForInLowerCase(): void {
        $resetCode = Reset::create(self::CredentialID, self::Email, availableSets: "l");

        $this->assertMatchesRegularExpression("/^[a-z]{6}$/", $resetCode);
    }

    public function testTheCodeIsFoundAfterItIsMade(): void {
        $resetCode = Reset::create(self::CredentialID, self::Email);

        $this->assertTrue(Reset::codeExists($resetCode));
        $this->assertSame(self::CredentialID, Reset::getCredentialID($resetCode));
        $this->assertSame(self::Email, Reset::getEmail($resetCode));
    }

    public function testTheCodeIsFoundByItsEmailToo(): void {
        $resetCode = Reset::create(self::CredentialID, self::Email);

        $this->assertTrue(Reset::codeExists($resetCode, self::Email));
        $this->assertFalse(Reset::codeExists($resetCode, self::OtherEmail));
    }

    public function testACodeThatWasNeverMadeIsNotThere(): void {
        $this->assertFalse(Reset::codeExists("NOPE12"));
        $this->assertSame(0, Reset::getCredentialID("NOPE12"));
        $this->assertSame("", Reset::getEmail("NOPE12"));
    }

    public function testAskingTwiceLeavesOnlyTheLastCode(): void {
        // The row is replaced, so a credential holds one pending reset
        $first  = Reset::create(self::CredentialID, self::Email);
        $second = Reset::create(self::CredentialID, self::Email);

        $this->assertFalse(Reset::codeExists($first));
        $this->assertTrue(Reset::codeExists($second));
    }

    public function testTheResetIsDeleted(): void {
        $resetCode = Reset::create(self::CredentialID, self::Email);

        $this->assertTrue(Reset::delete(self::CredentialID));
        $this->assertFalse(Reset::codeExists($resetCode));
    }

    public function testDeletingLeavesTheResetsOfTheOthers(): void {
        Reset::create(self::CredentialID, self::Email);
        $other = Reset::create(self::OtherID, self::OtherEmail);

        Reset::delete(self::CredentialID);

        $this->assertTrue(Reset::codeExists($other));
    }

    public function testDeletingSomethingThatIsNotThereSaysSo(): void {
        $this->assertFalse(Reset::delete(self::CredentialID));
    }

    public function testAFreshResetIsNotOldEnoughToBeSweptAway(): void {
        // Only the ones asked for more than three hours ago are dropped
        $resetCode = Reset::create(self::CredentialID, self::Email);

        Reset::deleteOld();

        $this->assertTrue(Reset::codeExists($resetCode));
    }
}
