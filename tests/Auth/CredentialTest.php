<?php
namespace Tests\Auth;

use Framework\Auth\Credential;
use Framework\Auth\Schema\CredentialEntity;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CredentialTest extends TestCase {

    /**
     * Returns a Credential with the given password already hashed into it
     * @param string $password
     * @param string $salt
     * @return CredentialEntity
     */
    private function credentialWith(string $password, string $salt): CredentialEntity {
        $hash = Credential::createHash($password, $salt);
        return new CredentialEntity(
            data:         new Dictionary([ "credentialID" => 1 ]),
            credentialID: 1,
            password:     $hash["password"],
            salt:         $hash["salt"],
        );
    }



    public function testHashingIsDeterministicForTheSameSalt(): void {
        $first  = Credential::createHash("correct horse", "a-fixed-salt");
        $second = Credential::createHash("correct horse", "a-fixed-salt");

        $this->assertEquals($first["password"], $second["password"]);
        $this->assertEquals("a-fixed-salt", $first["salt"]);
    }

    public function testHashingUsesADifferentSaltEachTime(): void {
        $first  = Credential::createHash("correct horse");
        $second = Credential::createHash("correct horse");

        $this->assertNotEquals($first["salt"], $second["salt"]);
        $this->assertNotEquals($first["password"], $second["password"]);
    }

    public function testTheSamePasswordUnderADifferentSaltHashesDifferently(): void {
        $first  = Credential::createHash("correct horse", "salt-one");
        $second = Credential::createHash("correct horse", "salt-two");

        $this->assertNotEquals($first["password"], $second["password"]);
    }

    public function testThePasswordIsNeverStoredInTheHash(): void {
        $hash = Credential::createHash("correct horse", "a-fixed-salt");

        $this->assertStringNotContainsString("correct horse", $hash["password"]);
        $this->assertNotEquals("correct horse", $hash["password"]);
    }


    public function testTheCorrectPasswordIsAccepted(): void {
        $credential = $this->credentialWith("correct horse", "a-fixed-salt");

        $this->assertTrue(Credential::isPasswordCorrect($credential, "correct horse"));
    }

    #[DataProvider("providerWrongPasswords")]
    public function testAWrongPasswordIsRejected(string $password): void {
        $credential = $this->credentialWith("correct horse", "a-fixed-salt");

        $this->assertFalse(Credential::isPasswordCorrect($credential, $password));
    }

    public static function providerWrongPasswords(): array {
        return [
            "different"  => [ "wrong horse" ],
            "empty"      => [ "" ],
            "case"       => [ "Correct Horse" ],
            "whitespace" => [ " correct horse" ],
            "prefix"     => [ "correct" ],
        ];
    }

    public function testAnEmptyCredentialNeverMatches(): void {
        $this->assertFalse(Credential::isPasswordCorrect(new CredentialEntity(), "correct horse"));
        $this->assertFalse(Credential::isPasswordCorrect(new CredentialEntity(), ""));
    }


    #[DataProvider("providerNames")]
    public function testGetName(array $data, bool $withEmail, string $expected): void {
        $this->assertEquals($expected, Credential::getName($data, $withEmail));
    }

    public static function providerNames(): array {
        $full  = [ "credentialID" => 7, "firstName" => "Ada", "lastName" => "Lovelace", "email" => "ada@example.com" ];
        $first = [ "credentialID" => 7, "firstName" => "Ada", "email" => "ada@example.com" ];
        $none  = [ "credentialID" => 7, "email" => "ada@example.com" ];

        return [
            // With no name at all it falls back to the id, not to the email
            "full"            => [ $full,  false, "Ada Lovelace" ],
            "full with email" => [ $full,  true,  "Ada Lovelace (ada@example.com)" ],
            "first only"      => [ $first, false, "Ada" ],
            "no name"         => [ $none,  false, "#7" ],
            "no name, email"  => [ $none,  true,  "#7" ],
            "nothing at all"  => [ [],     false, "" ],
        ];
    }

    public function testGetNameReadsAPrefixedRow(): void {
        $row = [ "ownerFirstName" => "Ada", "ownerLastName" => "Lovelace" ];

        $this->assertEquals("Ada Lovelace", Credential::getName($row, prefix: "owner"));
        $this->assertEquals("", Credential::getName($row));
    }


    #[DataProvider("providerPhones")]
    public function testGetPhone(array $data, bool $withPlus, string $expected): void {
        $this->assertEquals($expected, Credential::getPhone($data, "", $withPlus));
    }

    public static function providerPhones(): array {
        return [
            "phone"                => [ [ "phone" => "1155667788" ], false, "1155667788" ],
            "cellphone wins"       => [ [ "phone" => "1", "cellphone" => "2" ], false, "2" ],
            "cellphone with idd"   => [ [ "cellphone" => "1155667788", "iddRoot" => "54" ], false, "541155667788" ],
            "idd with plus"        => [ [ "cellphone" => "1155667788", "iddRoot" => "54" ], true, "+541155667788" ],
            "idd needs cellphone"  => [ [ "phone" => "1155667788", "iddRoot" => "54" ], true, "1155667788" ],
            "nothing"              => [ [], false, "" ],
        ];
    }

    public function testGetWhatsAppUrlUsesTheInternationalNumber(): void {
        $row = [ "cellphone" => "1155667788", "iddRoot" => "54" ];

        $this->assertEquals("https://wa.me/+541155667788", Credential::getWhatsAppUrl($row));
    }
}
