<?php
namespace Tests\Auth;

use Framework\Auth\AuthToken;
use Framework\Auth\RefreshToken;
use Framework\Date\Date;

use Tests\LiveTestCase;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Tokens a request is answered with, and the ones it is answered for
 */
class AuthTokenLiveTest extends LiveTestCase {

    private const CredentialID = 900031;
    private const AdminID      = 900032;


    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();

        AuthToken::deleteAllForCredential(self::CredentialID);
    }



    public function testTheAccessTokenCarriesWhatItWasGiven(): void {
        $accessToken = AuthToken::createAccessToken([
            "credentialID" => self::CredentialID,
            "adminID"      => self::AdminID,
            "apiToken"     => "an-api-token",
        ]);

        $this->assertNotSame("", $accessToken);
        $this->assertSame(
            [ self::CredentialID, self::AdminID ],
            AuthToken::getCredentials($accessToken, ""),
        );
        $this->assertSame("an-api-token", AuthToken::getAPIToken($accessToken));
    }

    /**
     * A token the framework did not sign, which carries nothing
     * @param string $accessToken
     * @return void
     */
    #[DataProvider("providerBadToken")]
    public function testATokenItDidNotSignCarriesNothing(string $accessToken): void {
        $this->assertSame([ 0, 0 ], AuthToken::getCredentials($accessToken, ""));
        $this->assertSame("", AuthToken::getAPIToken($accessToken));
    }

    /**
     * @return array<string,array{string}>
     */
    public static function providerBadToken(): array {
        return [
            "nothing given"  => [ "" ],
            "not a token"    => [ "not-a-jwt-at-all" ],
            "three parts"    => [ "aaa.bbb.ccc" ],
        ];
    }

    /**
     * A token, and whether it is one the framework signed
     * @param bool $isSigned
     * @param bool $expected
     * @return void
     */
    #[DataProvider("providerValid")]
    public function testOnlyATokenItSignedIsValid(bool $isSigned, bool $expected): void {
        $accessToken = $isSigned
            ? AuthToken::createAccessToken([ "credentialID" => self::CredentialID ])
            : "not-a-jwt-at-all";

        $this->assertSame($expected, AuthToken::isValidAccessToken($accessToken));
        $this->assertSame($expected, AuthToken::isValid($accessToken, ""));
    }

    /**
     * @return array<string,array{bool,bool}>
     */
    public static function providerValid(): array {
        return [
            "one it signed" => [ true, true ],
            "one it did not" => [ false, false ],
        ];
    }



    public function testARefreshTokenIsMadeAndAccepted(): void {
        $refreshToken = AuthToken::createRefreshToken(self::CredentialID);

        $this->assertNotSame("", $refreshToken);
        $this->assertTrue(AuthToken::isValid("", $refreshToken));
        $this->assertSame([ self::CredentialID, 0 ], AuthToken::getCredentials("", $refreshToken));
    }

    public function testARefreshTokenItNeverMadeIsRefused(): void {
        $this->assertFalse(AuthToken::isValid("", "not-a-refresh-token"));
        $this->assertSame([ 0, 0 ], AuthToken::getCredentials("", "not-a-refresh-token"));
    }

    public function testAFreshTokenIsLeftAsItIs(): void {
        // Only one that has not been touched for an hour is renewed, so the
        // expiration is not pushed back on every request
        $refreshToken = AuthToken::createRefreshToken(self::CredentialID);

        $this->assertSame("", AuthToken::updateRefreshToken($refreshToken));
        $this->assertTrue(AuthToken::isValid("", $refreshToken));
    }

    public function testATokenOlderThanAnHourIsRenewed(): void {
        $refreshToken = AuthToken::createRefreshToken(self::CredentialID);
        $past         = Date::now()->subtract(hours: 2)->toTime();
        $this->query(
            "UPDATE `credential_refresh_token` SET `modifiedTime` = $past " .
            "WHERE `refreshToken` = '$refreshToken'",
        );

        $this->assertSame($refreshToken, AuthToken::updateRefreshToken($refreshToken));
        $this->assertTrue(AuthToken::isValid("", $refreshToken));
    }

    public function testATokenThatIsNotThereIsNotRenewed(): void {
        $this->assertSame("", AuthToken::updateRefreshToken(""));
        $this->assertSame("", AuthToken::updateRefreshToken("not-a-refresh-token"));
    }

    public function testTheRefreshTokenIsDeleted(): void {
        $refreshToken = AuthToken::createRefreshToken(self::CredentialID);

        $this->assertTrue(AuthToken::deleteRefreshToken($refreshToken));
        $this->assertFalse(AuthToken::isValid("", $refreshToken));
    }

    public function testEveryTokenOfACredentialGoesAtOnce(): void {
        AuthToken::createRefreshToken(self::CredentialID);
        AuthToken::createRefreshToken(self::CredentialID);

        $this->assertTrue(AuthToken::deleteAllForCredential(self::CredentialID));
        $this->assertSame([], AuthToken::getAllForCredential(self::CredentialID));
    }

    public function testTheTokensOfACredentialAreListed(): void {
        // They come back as rows for a screen rather than as Entities, with
        // the platform read out of the agent that asked for each
        $refreshToken = AuthToken::createRefreshToken(self::CredentialID);

        $result = AuthToken::getAllForCredential(self::CredentialID);
        $this->assertCount(1, $result);
        $this->assertSame($refreshToken, $result[0]["refreshToken"]);
        $this->assertArrayHasKey("platform", $result[0]);
        $this->assertArrayHasKey("time", $result[0]);
    }

    public function testTheTokensOfNobodyAreNone(): void {
        $this->assertSame([], AuthToken::getAllForCredential(0));
        $this->assertFalse(AuthToken::deleteAllForCredential(0));
    }

    public function testTheExpiredTokensAreSweptAway(): void {
        $expired = RefreshToken::create(self::CredentialID, -60);
        $fresh   = AuthToken::createRefreshToken(self::CredentialID);

        AuthToken::deleteOld();

        $this->assertFalse(AuthToken::isValid("", $expired));
        $this->assertTrue(AuthToken::isValid("", $fresh));
    }

    public function testTheAccessTokenIsPreferredOverTheRefreshOne(): void {
        // The signed token names both, so it answers even when a refresh one
        // for somebody else is handed over beside it
        $accessToken  = AuthToken::createAccessToken([
            "credentialID" => self::CredentialID,
            "adminID"      => self::AdminID,
        ]);
        $refreshToken = AuthToken::createRefreshToken(999999);

        $this->assertSame(
            [ self::CredentialID, self::AdminID ],
            AuthToken::getCredentials($accessToken, $refreshToken),
        );
    }
}
