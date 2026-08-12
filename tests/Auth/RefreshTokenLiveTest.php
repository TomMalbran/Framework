<?php
namespace Tests\Auth;

use Framework\Auth\RefreshToken;

use Tests\LiveTestCase;

/**
 * The Refresh Tokens, which renew a session without asking for the password
 */
class RefreshTokenLiveTest extends LiveTestCase {

    private const CredentialID = 900021;
    private const OtherID      = 900022;
    private const Duration     = 3600;


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        RefreshToken::removeAll(self::CredentialID);
        RefreshToken::removeAll(self::OtherID);
    }



    public function testTheTokenIsMadeAndFound(): void {
        $refreshToken = RefreshToken::create(self::CredentialID, self::Duration);

        $this->assertSame(20, strlen($refreshToken));

        $entity = RefreshToken::get($refreshToken);
        $this->assertTrue($entity->exists());
        $this->assertSame(self::CredentialID, $entity->credentialID);
        $this->assertSame($refreshToken, $entity->refreshToken);
    }

    public function testEachTokenIsItsOwn(): void {
        $first  = RefreshToken::create(self::CredentialID, self::Duration);
        $second = RefreshToken::create(self::CredentialID, self::Duration);

        $this->assertNotSame($first, $second);
        $this->assertCount(2, RefreshToken::getAllForCredential(self::CredentialID));
    }

    public function testATokenThatWasNeverMadeIsNotThere(): void {
        $this->assertFalse(RefreshToken::get("not-a-token-at-all")->exists());
    }

    public function testACredentialWithNoTokenHasNone(): void {
        $this->assertSame([], RefreshToken::getAllForCredential(self::CredentialID));
    }

    public function testTheTokensOfTwoCredentialsAreKeptApart(): void {
        RefreshToken::create(self::CredentialID, self::Duration);
        RefreshToken::create(self::OtherID, self::Duration);

        $this->assertCount(1, RefreshToken::getAllForCredential(self::CredentialID));
        $this->assertCount(1, RefreshToken::getAllForCredential(self::OtherID));
    }

    public function testTheExpirationIsPushedBackWithoutChangingTheToken(): void {
        $refreshToken = RefreshToken::create(self::CredentialID, 60);
        $before       = RefreshToken::get($refreshToken)->expirationTime->toTime();

        $this->assertSame($refreshToken, RefreshToken::update($refreshToken, self::Duration));

        $after = RefreshToken::get($refreshToken)->expirationTime->toTime();
        $this->assertGreaterThan($before, $after);
    }

    public function testTheTokenIsRemoved(): void {
        $refreshToken = RefreshToken::create(self::CredentialID, self::Duration);

        $this->assertTrue(RefreshToken::remove($refreshToken));
        $this->assertFalse(RefreshToken::get($refreshToken)->exists());
    }

    public function testRemovingATokenLeavesTheOthers(): void {
        $first  = RefreshToken::create(self::CredentialID, self::Duration);
        $second = RefreshToken::create(self::CredentialID, self::Duration);

        RefreshToken::remove($first);

        $this->assertFalse(RefreshToken::get($first)->exists());
        $this->assertTrue(RefreshToken::get($second)->exists());
    }

    public function testEveryTokenOfACredentialGoesAtOnce(): void {
        RefreshToken::create(self::CredentialID, self::Duration);
        RefreshToken::create(self::CredentialID, self::Duration);
        $other = RefreshToken::create(self::OtherID, self::Duration);

        $this->assertTrue(RefreshToken::removeAll(self::CredentialID));

        $this->assertSame([], RefreshToken::getAllForCredential(self::CredentialID));
        $this->assertTrue(RefreshToken::get($other)->exists());
    }

    public function testAnExpiredTokenIsSweptAwayAndAFreshOneIsNot(): void {
        $expired = RefreshToken::create(self::CredentialID, -60);
        $fresh   = RefreshToken::create(self::CredentialID, self::Duration);

        RefreshToken::removeOld();

        $this->assertFalse(RefreshToken::get($expired)->exists());
        $this->assertTrue(RefreshToken::get($fresh)->exists());
    }

    public function testRemovingWhenThereIsNothingSaysSo(): void {
        $this->assertFalse(RefreshToken::remove("not-a-token-at-all"));
        $this->assertFalse(RefreshToken::removeAll(self::CredentialID));
    }
}
