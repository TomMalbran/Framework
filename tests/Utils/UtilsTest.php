<?php
use Framework\Utils\Utils;

use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase {

    public function testIsValidPassword() {
        // default: letters+digits, minLength=6
        $this->assertTrue(Utils::isValidPassword("abc123"));
        $this->assertFalse(Utils::isValidPassword("abc12")); // too short
        $this->assertFalse(Utils::isValidPassword("abcdef")); // no digits

        // require lowercase only
        $this->assertFalse(Utils::isValidPassword("ABC123", "l"));
        $this->assertTrue(Utils::isValidPassword("abcde1", "ad", 5));

        // Explicit checks for each individual set
        // 'a' -> requires at least one letter (letters allowed with digits)
        $this->assertTrue(Utils::isValidPassword("abc", "a", 1));
        $this->assertFalse(Utils::isValidPassword("123", "a", 1));

        // 'l' -> requires at least one lowercase letter
        $this->assertTrue(Utils::isValidPassword("abc", "l", 1));
        $this->assertFalse(Utils::isValidPassword("ABC", "l", 1));

        // 'u' -> requires at least one uppercase letter
        $this->assertTrue(Utils::isValidPassword("ABC", "u", 1));
        $this->assertFalse(Utils::isValidPassword("abc", "u", 1));

        // 'd' -> requires at least one digit
        $this->assertTrue(Utils::isValidPassword("123", "d", 1));
        $this->assertFalse(Utils::isValidPassword("abc", "d", 1));
    }

    public function testIsValidColor() {
        // valid hex colors
        $this->assertTrue(Utils::isValidColor("#000"));
        $this->assertTrue(Utils::isValidColor("#fff"));
        $this->assertTrue(Utils::isValidColor("#a1b2c3"));

        // invalid colors
        $this->assertFalse(Utils::isValidColor("fff"));
        $this->assertFalse(Utils::isValidColor("#ggg"));
        $this->assertFalse(Utils::isValidColor("#gggff"));
    }

    public function testIsValidFullName() {
        // valid full names
        $this->assertTrue(Utils::isValidFullName("John Doe"));
        $this->assertTrue(Utils::isValidFullName(" Ana María "));
        $this->assertTrue(Utils::isValidFullName("Ana María Dora"));

        // invalid full names
        $this->assertFalse(Utils::isValidFullName(""));
        $this->assertFalse(Utils::isValidFullName("Madonna"));
        $this->assertFalse(Utils::isValidFullName(" Ana "));
    }

    public function testParseName() {
        // simple first + last
        $this->assertEquals([ "John", "Doe" ], Utils::parseName("John Doe"));

        // last name first with custom separator
        $this->assertEquals([ "John", "Doe" ], Utils::parseName("Doe,John", true, ","));

        // multi-part first name (middle name preserved)
        $this->assertEquals([ "Smith John", "Doe"], Utils::parseName("Smith John Doe"));

        // when last name is first and space separator
        $this->assertEquals([ "Smith John", "Doe" ], Utils::parseName("Doe Smith John", true, " "));

        // custom multi-character separator and trimming
        $this->assertEquals([ "First Middle", "Last" ], Utils::parseName("Last|First Middle", true, "|"));

        // single name -> firstName returned, lastName empty
        $this->assertEquals([ "Single", "" ], Utils::parseName("Single"));
    }

    public function testIsValidUsername() {
        // valid usernames
        $this->assertTrue(Utils::isValidUsername("user-name1"));
        $this->assertTrue(Utils::isValidUsername("username"));

        // invalid usernames
        $this->assertFalse(Utils::isValidUsername("user name"));
        $this->assertFalse(Utils::isValidUsername("user@name"));
        $this->assertFalse(Utils::isValidUsername("user.name"));
        $this->assertFalse(Utils::isValidUsername("user-name-"));
        $this->assertFalse(Utils::isValidUsername("-bad"));
        $this->assertFalse(Utils::isValidUsername("has space"));
    }

    public function testGenerateUsername() {
        $this->assertEquals("example", Utils::generateUsername("example.com"));
        $this->assertEquals("e1domain", Utils::generateUsername("1domain.com", "e@mail.com"));
        $this->assertEquals("nino", Utils::generateUsername("niño.com"));

        // additional cases: numeric-first, empty or invalid domains, replacements and truncation
        $this->assertEquals("1domain", Utils::generateUsername("1domain.com"));
        $this->assertEquals("", Utils::generateUsername(""));
        $this->assertEquals("", Utils::generateUsername(".com"));
        $this->assertEquals("abn", Utils::generateUsername("a-b-ñ.com"));
        $this->assertEquals("z9domain", Utils::generateUsername("9domain.com", "z@x.com"));

        // when result is long and email prefix is used the final value is truncated to 8 chars
        $this->assertEquals("a1234567", Utils::generateUsername("123456789.com", "a@b.com"));
    }

    public function testIsValidEmail() {
        $this->assertTrue(Utils::isValidEmail("test@example.com"));
        $this->assertTrue(Utils::isValidEmail("user+tag@example.co.uk"));
        $this->assertTrue(Utils::isValidEmail("user.name@example.com"));
        $this->assertTrue(Utils::isValidEmail("USER_123@sub.example-domain.com"));

        // invalid emails
        $this->assertFalse(Utils::isValidEmail("not-an-email"));
        $this->assertFalse(Utils::isValidEmail("user@.com"));
        $this->assertFalse(Utils::isValidEmail("@example.com"));
        $this->assertFalse(Utils::isValidEmail("user@com"));
        $this->assertFalse(Utils::isValidEmail("user@example..com"));
        $this->assertFalse(Utils::isValidEmail(""));
    }

    public function testExtractEmail() {
        $this->assertEquals("foo@bar.com", Utils::extractEmail("contact: foo@bar.com here"));
        $this->assertEquals("john.smith@sub.example.com", Utils::extractEmail("Contact: <john.smith@sub.example.com> is listed"));
        $this->assertEquals("first@a.com", Utils::extractEmail("Multiple: first@a.com second@b.com"));
        $this->assertEquals("user_1@domain.org", Utils::extractEmail("user_1@domain.org is the contact"));

        // plus in local part yields the piece after the plus to be matched by current regex
        $this->assertEquals("tag@example.com", Utils::extractEmail("user+tag@example.com"));
        $this->assertEquals("foo@domain.co.uk", Utils::extractEmail("name+foo@domain.co.uk and more"));
        $this->assertEquals("", Utils::extractEmail("no email here"));
        $this->assertEquals("", Utils::extractEmail(""));
    }

    public function testHideEmail() {
        $this->assertEquals("joh*****@example.com", Utils::hideEmail("john.doe@example.com"));

        // invalid emails return empty string
        $this->assertEquals("", Utils::hideEmail(""));
        $this->assertEquals("", Utils::hideEmail("not-an-email"));
        $this->assertEquals("", Utils::hideEmail("user@.com"));
        $this->assertEquals("", Utils::hideEmail("@example.com"));
        $this->assertEquals("", Utils::hideEmail("invalid@"));

        // short local parts
        $this->assertEquals("a@b.com", Utils::hideEmail("a@b.com"));
        $this->assertEquals("a*@b.com", Utils::hideEmail("ab@b.com"));
        $this->assertEquals("a**@b.com", Utils::hideEmail("abc@b.com"));
        $this->assertEquals("abc*@b.com", Utils::hideEmail("abcd@b.com"));

        // preserves case in local part and lowercases domain
        $this->assertEquals("Use*@example.com", Utils::hideEmail("User@EXAMPLE.COM"));
    }

    public function testIsValidPhone() {
        // valid cases
        $this->assertTrue(Utils::isValidPhone("1234567890"));
        $this->assertTrue(Utils::isValidPhone("(123) 456-7890"));
        $this->assertTrue(Utils::isValidPhone("123-456-7890"));
        $this->assertTrue(Utils::isValidPhone("+1 (123) 456-7890"));

        // invalid cases
        $this->assertFalse(Utils::isValidPhone(""));
        $this->assertFalse(Utils::isValidPhone("abc"));
    }

    public function testPhoneToNumber() {
        $this->assertEquals("1234567890", Utils::phoneToNumber("(123) 456-7890"));
        $this->assertEquals("1234567890", Utils::phoneToNumber("123-456-7890"));
        $this->assertEquals("11234567890", Utils::phoneToNumber("+1 (123) 456-7890"));

        // invalid cases return empty string
        $this->assertEquals("", Utils::phoneToNumber(""));
        $this->assertEquals("", Utils::phoneToNumber("abc"));
    }

    public function testHidePhone() {
        $this->assertEquals("123****890", Utils::hidePhone("1234567890"));
        $this->assertEquals("*3", Utils::hidePhone("03"));
        $this->assertEquals("", Utils::hidePhone(""));

        // very short phones
        $this->assertEquals("*", Utils::hidePhone("1"));
        $this->assertEquals("*2", Utils::hidePhone("12"));

        // small lengths
        $this->assertEquals("1*3", Utils::hidePhone("123"));
        $this->assertEquals("1**4", Utils::hidePhone("1234"));
        $this->assertEquals("1***5", Utils::hidePhone("12345"));
        $this->assertEquals("12***67", Utils::hidePhone("1234567"));

        // international / formatted input keeps formatting when hiding
        $this->assertEquals("+1 (1*******-7890", Utils::hidePhone("+1 (123) 456-7890"));

        // invalid input returns empty
        $this->assertEquals("", Utils::hidePhone("abc"));
    }

    public function testIsValidCUIT() {
        // valid raw digits
        $this->assertTrue(Utils::isValidCUIT("20123456786"));

        // valid when passed formatted with dashes
        $this->assertTrue(Utils::isValidCUIT("20-12345678-6"));

        // invalid when last digit changed (hardcoded)
        $this->assertFalse(Utils::isValidCUIT("20123456780"));

        // invalid length
        $this->assertFalse(Utils::isValidCUIT("1234567"));
    }

    public function testParseCUIT() {
        $this->assertEquals("20-12345678-6", Utils::parseCUIT("20123456786"));

        // non-11 input is returned as-is
        $this->assertEquals("20-12345678-6", Utils::parseCUIT("20-12345678-6"));
        $this->assertEquals("123", Utils::parseCUIT("123"));
        $this->assertEquals("", Utils::parseCUIT(""));

        // string with 11 digits but invalid returned as-is
        $this->assertEquals("2012345678a", Utils::parseCUIT("2012345678a"));
    }

    public function testCuitToNumber() {
        $this->assertEquals("20123456786", Utils::cuitToNumber("20-12345678-6"));
        $this->assertEquals("20123456786", Utils::cuitToNumber("20 12345678 6"));

        // invalid input returns empty string
        $this->assertEquals("", Utils::cuitToNumber(""));
    }

    public function testIsValidDNI() {
        // plain digits
        $this->assertTrue(Utils::isValidDNI("12345678"));

        // formatted with dots is accepted for validation
        $this->assertTrue(Utils::isValidDNI("12.345.678"));

        // leading/trailing spaces tolerated
        $this->assertTrue(Utils::isValidDNI(" 12.345.678 "));

        // invalid: too short
        $this->assertFalse(Utils::isValidDNI("123"));

        // invalid: too long
        $this->assertFalse(Utils::isValidDNI("123456789012"));

        // invalid: non-digits
        $this->assertFalse(Utils::isValidDNI("abcdefg"));

        // invalid: empty
        $this->assertFalse(Utils::isValidDNI(""));
    }

    public function testDniToNumber() {
        // converts formatted DNI to plain digits
        $this->assertEquals("12345678", Utils::dniToNumber("12.345.678"));
        $this->assertEquals("12345678", Utils::dniToNumber(" 12.345.678 "));
        $this->assertEquals("12345678", Utils::dniToNumber("12345678"));

        // preserves numeric string with leading zeros
        $this->assertEquals("00123456", Utils::dniToNumber("00.123.456"));

        // invalid inputs return empty string
        $this->assertEquals("", Utils::dniToNumber("abc"));
        $this->assertEquals("", Utils::dniToNumber(""));
    }

    public function testIsValidDomain() {
        $this->assertTrue(Utils::isValidDomain("example.com"));
        $this->assertTrue(Utils::isValidDomain("sub.example.co.uk"));
        $this->assertTrue(Utils::isValidDomain("ex-ample.com"));

        // invalid domains
        $this->assertFalse(Utils::isValidDomain("not valid"));
        $this->assertFalse(Utils::isValidDomain("example..com"));
        $this->assertFalse(Utils::isValidDomain("-example.com"));
        $this->assertFalse(Utils::isValidDomain("example.com-"));
        $this->assertFalse(Utils::isValidDomain(""));
    }

    public function testGetEmailDomain() {
        $this->assertEquals("example.com", Utils::getEmailDomain("user@Example.COM"));
        $this->assertEquals("sub.example.co.uk", Utils::getEmailDomain("user@Sub.Example.Co.UK"));
        $this->assertEquals("example.com", Utils::getEmailDomain("user+tag@EXAMPLE.COM"));
        $this->assertEquals("sub-example.com", Utils::getEmailDomain("User.Name+tag@Sub-Example.COM"));
        $this->assertEquals("123domain.com", Utils::getEmailDomain("user@123domain.com"));

        // invalid emails return empty string
        $this->assertEquals("", Utils::getEmailDomain(" user@Example.COM "));
        $this->assertEquals("", Utils::getEmailDomain("user@localhost"));
        $this->assertEquals("", Utils::getEmailDomain("not-an-email"));
        $this->assertEquals("", Utils::getEmailDomain(""));
    }

    public function testGetDomainExtension() {
        $this->assertEquals("com", Utils::getDomainExtension("example.com"));
        $this->assertEquals("uk", Utils::getDomainExtension("sub.example.co.uk"));
        $this->assertEquals("localhost", Utils::getDomainExtension("localhost"));
        $this->assertEquals("", Utils::getDomainExtension(""));
    }

    public function testParseDomain() {
        $this->assertEquals("example.com", Utils::parseDomain("Example.Com"));
        $this->assertEquals("example.com", Utils::parseDomain("www.example.com"));
        $this->assertEquals("example.org", Utils::parseDomain("WWW.EXAMPLE.ORG"));
        $this->assertEquals("example.com", Utils::parseDomain("http://www.Example.Com"));
        $this->assertEquals("example.com", Utils::parseDomain("https://WWW.EXAMPLE.COM"));
        $this->assertEquals("example.com", Utils::parseDomain("https://WWW.EXAMPLE.COM/path?query=string"));

        // subdomains preserved but www. removed
        $this->assertEquals("sub.example.co.uk", Utils::parseDomain("Sub.Example.Co.UK"));
        $this->assertEquals("sub.example.co.uk", Utils::parseDomain("www.Sub.Example.Co.UK"));
        $this->assertEquals("sub.example.co.uk", Utils::parseDomain("http://www.Sub.Example.Co.UK"));

        // invalid input returns as-is
        $this->assertEquals("not a domain", Utils::parseDomain("not a domain"));
        $this->assertEquals("", Utils::parseDomain(""));
    }

    public function testGetHost() {
        $this->assertEquals("example.com", Utils::getHost("http://example.com/path"));
        $this->assertEquals("www.example.co.uk", Utils::getHost("https://www.example.co.uk/some/page"));

        // invalid URLs return empty string
        $this->assertEquals("", Utils::getHost(""));
        $this->assertEquals("", Utils::getHost("not a url"));
        $this->assertEquals("", Utils::getHost("example.com/path"));
        $this->assertEquals("", Utils::getHost("www.example.com/path"));
    }

    public function testIsDelegated() {
        $hostIp = gethostbyname("localhost");
        $this->assertTrue(Utils::isDelegated("localhost"));

        // when providing the correct server IP it still reports delegated
        $this->assertTrue(Utils::isDelegated("localhost", $hostIp));

        // wrong server IP yields false
        $this->assertFalse(Utils::isDelegated("localhost", '1.2.3.4'));

        // unknown domain not delegated
        $this->assertFalse(Utils::isDelegated("no-such-host-example.invalid"));
    }

    public function testVerifyDelegation() {
        // localhost normally resolves to an IP
        $hostIp = gethostbyname("localhost");
        $this->assertTrue(Utils::verifyDelegation("localhost"));
        $this->assertTrue(Utils::verifyDelegation("localhost", $hostIp));

        // mismatching server IP should return false
        $this->assertFalse(Utils::verifyDelegation("localhost", "1.2.3.4"));

        // non-resolvable domain returns false
        $this->assertFalse(Utils::verifyDelegation("no-such-host-example.invalid"));

        // empty input returns false
        $this->assertFalse(Utils::verifyDelegation(""));
    }

    public function testIsValidUrl() {
        $this->assertTrue(Utils::isValidUrl("http://example.com"));
        $this->assertTrue(Utils::isValidUrl("https://example.com"));
        $this->assertTrue(Utils::isValidUrl("https://sub.example.co.uk/path?query=1#frag"));
        $this->assertTrue(Utils::isValidUrl("https://example.com:8080"));
        $this->assertTrue(Utils::isValidUrl("https://127.0.0.1"));

        // schemes other than http/https are considered invalid by Utils
        $this->assertFalse(Utils::isValidUrl("ftp://example.com"));

        // missing scheme or plain text is invalid
        $this->assertFalse(Utils::isValidUrl("www.example.com"));
        $this->assertFalse(Utils::isValidUrl(""));
        $this->assertFalse(Utils::isValidUrl("not a url"));
    }

    public function testEncodeUrl() {
        // spaces are encoded
        $this->assertStringNotContainsString(" ", Utils::encodeUrl("a b"));
        $this->assertStringContainsString("%20", Utils::encodeUrl("a b"));

        // complex strings should not contain raw spaces after encoding
        $this->assertStringNotContainsString(" ", Utils::encodeUrl("a b/c?d=e&f=g"));

        // non-ASCII characters are percent-encoded (UTF-8)
        $this->assertStringContainsString("%C3%B1", Utils::encodeUrl("mañana"));

        // already-encoded input retains encoding for encoded parts
        $encoded = Utils::encodeUrl("a%20b");
        $this->assertStringContainsString("%20", $encoded);
    }

    public function testAddParamsToUrl() {
        $this->assertEquals("/path?a=1&b=2", Utils::addParamsToUrl("/path", [ "a" => 1, "b" => 2 ]));
        $this->assertEquals("/path?existing=1&a=2", Utils::addParamsToUrl("/path?existing=1", [ "a" => 2 ]));

        // values requiring encoding are encoded in the resulting URL
        $result = Utils::addParamsToUrl("/path", [ "q" => "a b" ]);
        $this->assertStringNotContainsString(" ", $result);
        $this->assertStringContainsString("q=", $result);
        $this->assertStringContainsString("+", $result);

        // boolean and numeric values are represented in query string
        $this->assertStringContainsString("flag=true", Utils::addParamsToUrl("/path", [ "flag" => true ]));
        $this->assertStringContainsString("num=0", Utils::addParamsToUrl("/path", [ "num" => 0 ]));
    }

    public function testParseUrlParams() {
        // arrays/objects are JSON-encoded and urlencoded (contains %7B for '{')
        $this->assertStringContainsString("a=%7B", Utils::parseUrlParams(["a"=>["k"=>"v"]]));

        // special characters are encoded
        $this->assertStringContainsString("+", Utils::parseUrlParams(["q"=>"a b"]));

        // empty input yields empty string
        $this->assertEquals("", Utils::parseUrlParams([]));

        // numeric and boolean values serialized as strings
        $this->assertStringContainsString("n=123", Utils::parseUrlParams(["n"=>123]));
        $this->assertStringContainsString("t=true", Utils::parseUrlParams(["t"=>true]));
    }

    public function testGetAvatarUrl() {
        $this->assertEquals("https://gravatar.com/avatar/" . md5("me@example.com") . "?default=mp", Utils::getAvatarUrl("", "me@example.com"));
        $this->assertEquals("custom", Utils::getAvatarUrl("custom", "me@example.com"));

        // fallback when no email and no custom provided
        $this->assertEquals("https://gravatar.com/avatar/" . md5("" ) . "?default=mp", Utils::getAvatarUrl("", ""));
    }

    public function testGetWhatsAppUrl() {
        $this->assertEquals("https://wa.me/12345", Utils::getWhatsAppUrl("12345"));

        // accepts numbers with plus sign (normalized by Utils)
        $this->assertStringContainsString("wa.me/", Utils::getWhatsAppUrl("+5412345"));
    }
}
