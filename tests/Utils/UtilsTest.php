<?php
namespace Tests\Utils;

use Framework\Utils\Utils;

use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase {

    /** @dataProvider providerIsValidPassword */
    public function testIsValidPassword(string $password, string $requirements, int $minLength, bool $expected) {
        $this->assertEquals($expected, Utils::isValidPassword($password, $requirements, $minLength));
    }

    public static function providerIsValidPassword() {
        return [
            "def_ld6_valid"          => [ "abc123", "ld", 6, true ],
            "def_ld6_invalid_short"  => [ "abc12", "ld", 6, false ],
            "def_ld6_invalid_digits" => [ "abcdef", "ld", 6, false ],
            "req_l_uppercase"        => [ "ABC123", "l", 6, false ],
            "req_l_valid"            => [ "abcde1", "l", 5, true ],
            "req_l_invalid"          => [ "ABC", "l", 1, false ],
            "req_a_valid"            => [ "abc", "a", 1, true ],
            "req_a_invalid"          => [ "123", "a", 1, false ],
            "req_u_valid"            => [ "ABC", "u", 1, true ],
            "req_u_invalid"          => [ "abc", "u", 1, false ],
            "req_d_valid"            => [ "123", "d", 1, true ],
            "req_d_invalid"          => [ "abc", "d", 1, false ],
        ];
    }


    /** @dataProvider providerIsValidColor */
    public function testIsValidColor(string $color, bool $expected) {
        $this->assertEquals($expected, Utils::isValidColor($color));
    }

    public static function providerIsValidColor() {
        return [
            "valid_short_black" => [ "#000", true ],
            "valid_short_white" => [ "#fff", true ],
            "valid_long"        => [ "#a1b2c3", true ],
            "valid_long_upper"  => [ "#A1B2C3", true ],
            "valid_short_upper" => [ "#FFF", true ],
            "invalid_no_hash"   => [ "fff", false ],
            "invalid_bad_hex"   => [ "#ggg", false ],
            "invalid_long_bad"  => [ "#gggFF", false ],
        ];
    }


    /** @dataProvider providerIsValidFullName */
    public function testIsValidFullName(string $fullName, bool $expected) {
        $this->assertEquals($expected, Utils::isValidFullName($fullName));
    }

    public static function providerIsValidFullName() {
        return [
            "valid_simple"        => [ "John Doe", true ],
            "valid_with_spaces"   => [ " Ana María ", true ],
            "valid_multi_part"    => [ "Ana María Dora", true ],
            "invalid_empty"       => [ "", false ],
            "invalid_single_name" => [ "Madonna", false ],
            "invalid_single_word" => [ " Ana ", false ],
        ];
    }


    /** @dataProvider providerParseName */
    public function testParseName(string $name, bool $lastNameFirst, string $separator, array $expected) {
        $this->assertEquals($expected, Utils::parseName($name, $lastNameFirst, $separator));
    }

    public static function providerParseName() {
        return [
            "simple_first_last"     => [ "John Doe", false, " ", [ "John", "Doe" ] ],
            "last_name_first_comma" => [ "Doe,John", true, ",", [ "John", "Doe" ] ],
            "multi_part_first_name" => [ "Smith John Doe", false, " ", [ "Smith John", "Doe" ] ],
            "last_name_first_space" => [ "Doe Smith John", true, " ", [ "Smith John", "Doe" ] ],
            "custom_separator"      => [ "Last|First Middle", true, "|", [ "First Middle", "Last" ] ],
            "single_name"           => [ "Single", false, " ", [ "Single", "" ] ],
        ];
    }


    /** @dataProvider providerIsValidUsername */
    public function testIsValidUsername(string $username, bool $expected) {
        $this->assertEquals($expected, Utils::isValidUsername($username));
    }

    public static function providerIsValidUsername() {
        return [
            "valid_with_dash_number" => [ "user-name1", true ],
            "valid_simple"           => [ "username", true ],
            "invalid_with_space"     => [ "user name", false ],
            "invalid_with_at"        => [ "user@name", false ],
            "invalid_with_dot"       => [ "user.name", false ],
            "invalid_trailing_dash"  => [ "user-name-", false ],
            "invalid_leading_dash"   => [ "-bad", false ],
            "invalid_has_space"      => [ "has space", false ],
        ];
    }


    /** @dataProvider providerGenerateUsername */
    public function testGenerateUsername(string $domain, string $email, string $expected) {
        $this->assertEquals($expected, Utils::generateUsername($domain, $email));
    }

    public static function providerGenerateUsername() {
        return [
            "simple_domain"         => [ "example.com", "", "example" ],
            "with_email_prefix"     => [ "1domain.com", "e@mail.com", "e1domain" ],
            "unicode_chars"         => [ "niño.com", "", "nino" ],
            "numeric_first"         => [ "1domain.com", "", "1domain" ],
            "empty_domain"          => [ "", "", "" ],
            "invalid_domain"        => [ ".com", "", "" ],
            "dashes_and_unicode"    => [ "a-b-ñ.com", "", "abn" ],
            "numeric_with_email"    => [ "9domain.com", "z@x.com", "z9domain" ],
            "truncation_with_email" => [ "123456789.com", "a@b.com", "a1234567" ],
        ];
    }


    /** @dataProvider providerIsValidEmail */
    public function testIsValidEmail(string $email, bool $expected) {
        $this->assertEquals($expected, Utils::isValidEmail($email));
    }

    public static function providerIsValidEmail() {
        return [
            "valid_simple"       => [ "test@example.com", true ],
            "valid_with_tag"     => [ "user+tag@example.co.uk", true ],
            "valid_with_dot"     => [ "user.name@example.com", true ],
            "valid_with_under"   => [ "USER_123@sub.example-domain.com", true ],
            "invalid_no_at"      => [ "not-an-email", false ],
            "invalid_no_domain"  => [ "user@.com", false ],
            "invalid_no_local"   => [ "@example.com", false ],
            "invalid_no_tld"     => [ "user@com", false ],
            "invalid_double_dot" => [ "user@example..com", false ],
            "invalid_empty"      => [ "", false ],
        ];
    }


    /** @dataProvider providerGetEmailDomain */
    public function testGetEmailDomain(string $email, string $expected) {
        $this->assertEquals($expected, Utils::getEmailDomain($email));
    }

    public static function providerGetEmailDomain() {
        return [
            "valid_simple"         => [ "user@Example.COM", "example.com" ],
            "valid_subdomain"      => [ "user@Sub.Example.Co.UK", "sub.example.co.uk" ],
            "valid_with_tag"       => [ "user+tag@EXAMPLE.COM", "example.com" ],
            "valid_multi_part"     => [ "User.Name+tag@Sub-Example.COM", "sub-example.com" ],
            "valid_numeric_domain" => [ "user@123domain.com", "123domain.com" ],
            "invalid_with_spaces"  => [ " user@Example.COM ", "" ],
            "invalid_localhost"    => [ "user@localhost", "" ],
            "invalid_not_email"    => [ "not-an-email", "" ],
            "invalid_empty"        => [ "", "" ],
        ];
    }


    /** @dataProvider providerExtractEmail */
    public function testExtractEmail(string $input, string $expected) {
        $this->assertEquals($expected, Utils::extractEmail($input));
    }

    public static function providerExtractEmail() {
        return [
            "simple_email"          => [ "contact: foo@bar.com here", "foo@bar.com" ],
            "email_in_brackets"     => [ "Contact: <john.smith@sub.example.com> is listed", "john.smith@sub.example.com" ],
            "multiple_emails"       => [ "Multiple: first@a.com second@b.com", "first@a.com" ],
            "email_with_underscore" => [ "user_1@domain.org is the contact", "user_1@domain.org" ],
            "plus_in_local_part"    => [ "user+tag@example.com", "tag@example.com" ],
            "plus_with_subdomain"   => [ "name+foo@domain.co.uk and more", "foo@domain.co.uk" ],
            "no_email"              => [ "no email here", "" ],
            "empty_string"          => [ "", "" ],
        ];
    }


    /** @dataProvider providerHideEmail */
    public function testHideEmail(string $email, string $expected) {
        $this->assertEquals($expected, Utils::hideEmail($email));
    }

    public static function providerHideEmail() {
        return [
            "simple_email"      => [ "john.doe@example.com", "joh*****@example.com" ],
            "invalid_empty"     => [ "", "" ],
            "invalid_not_email" => [ "not-an-email", "" ],
            "invalid_no_domain" => [ "user@.com", "" ],
            "invalid_no_local"  => [ "@example.com", "" ],
            "invalid_no_tld"    => [ "invalid@", "" ],
            "short_single_char" => [ "a@b.com", "a@b.com" ],
            "short_two_chars"   => [ "ab@b.com", "a*@b.com" ],
            "short_three_chars" => [ "abc@b.com", "a**@b.com" ],
            "short_four_chars"  => [ "abcd@b.com", "abc*@b.com" ],
            "preserves_case"    => [ "User@EXAMPLE.COM", "Use*@example.com" ],
        ];
    }


    /** @dataProvider providerIsValidPhone */
    public function testIsValidPhone(string $phone, bool $expected) {
        $this->assertEquals($expected, Utils::isValidPhone($phone));
    }

    public static function providerIsValidPhone() {
        return [
            "valid_plain"         => [ "1234567890", true ],
            "valid_formatted"     => [ "(123) 456-7890", true ],
            "valid_dashed"        => [ "123-456-7890", true ],
            "valid_international" => [ "+1 (123) 456-7890", true ],
            "invalid_empty"       => [ "", false ],
            "invalid_letters"     => [ "abc", false ],
        ];
    }


    /** @dataProvider providerPhoneToNumber */
    public function testPhoneToNumber(string $phone, string $expected) {
        $this->assertEquals($expected, Utils::phoneToNumber($phone));
    }

    public static function providerPhoneToNumber() {
        return [
            "formatted_parentheses" => [ "(123) 456-7890", "1234567890" ],
            "formatted_dashed"      => [ "123-456-7890", "1234567890" ],
            "international"         => [ "+1 (123) 456-7890", "11234567890" ],
            "invalid_empty"         => [ "", "" ],
            "invalid_letters"       => [ "abc", "" ],
        ];
    }


    /** @dataProvider providerHidePhone */
    public function testHidePhone(string $phone, string $expected) {
        $this->assertEquals($expected, Utils::hidePhone($phone));
    }

    public static function providerHidePhone() {
        return [
            "plain_digits"            => [ "1234567890", "123****890" ],
            "short_two_chars"         => [ "03", "*3" ],
            "empty_string"            => [ "", "" ],
            "very_short_one_char"     => [ "1", "*" ],
            "very_short_two_chars"    => [ "12", "*2" ],
            "small_three_chars"       => [ "123", "1*3" ],
            "small_four_chars"        => [ "1234", "1**4" ],
            "small_five_chars"        => [ "12345", "1***5" ],
            "small_seven_chars"       => [ "1234567", "12***67" ],
            "international_formatted" => [ "+1 (123) 456-7890", "+1 (1*******-7890" ],
            "invalid_letters"         => [ "abc", "" ],
        ];
    }


    /** @dataProvider providerIsValidCUIT */
    public function testIsValidCUIT(string $cuit, bool $expected) {
        $this->assertEquals($expected, Utils::isValidCUIT($cuit));
    }

    public static function providerIsValidCUIT() {
        return [
            "valid_raw_digits_1" => [ "20123456786", true ],
            "valid_raw_digits_2" => [ "20123456840", true ],
            "valid_raw_digits_3" => [ "23123456849", true ],
            "valid_formatted_1"  => [ "20-12345678-6", true ],
            "valid_formatted_2"  => [ "20-12345684-0", true ],
            "valid_formatted_3"  => [ "23-12345684-9", true ],
            "invalid_last_digit" => [ "20123456780", false ],
            "invalid_length"     => [ "1234567", false ],
        ];
    }


    /** @dataProvider providerParseCUIT */
    public function testParseCUIT(string $cuit, string $expected) {
        $this->assertEquals($expected, Utils::parseCUIT($cuit));
    }

    public static function providerParseCUIT() {
        return [
            "valid_formatted_1"    => [ "20-12345678-6", "20-12345678-6" ],
            "valid_raw_digits_1"   => [ "20123456786", "20-12345678-6" ],
            "valid_raw_digits_2"   => [ "20123456840", "20-12345684-0" ],
            "valid_raw_digits_3"   => [ "23123456849", "23-12345684-9" ],
            "invalid_non_11_input" => [ "20-123456789-6", "20-123456789-6" ],
            "invalid_short"        => [ "123", "123" ],
            "invalid_empty"        => [ "", "" ],
            "invalid_with_letter"  => [ "2012345678a", "2012345678a" ],
        ];
    }


    /** @dataProvider providerCuitToNumber */
    public function testCuitToNumber(string $cuit, string $expected) {
        $this->assertEquals($expected, Utils::cuitToNumber($cuit));
    }

    public static function providerCuitToNumber() {
        return [
            "valid_formatted"   => [ "20-12345678-6", "20123456786" ],
            "valid_with_spaces" => [ "20 12345678 6", "20123456786" ],
            "invalid_empty"     => [ "", "" ],
        ];
    }


    /** @dataProvider providerIsValidDNI */
    public function testIsValidDNI(string $dni, bool $expected) {
        $this->assertEquals($expected, Utils::isValidDNI($dni));
    }

    public static function providerIsValidDNI() {
        return [
            "valid_plain_digits"   => [ "12345678", true ],
            "valid_formatted_dots" => [ "12.345.678", true ],
            "valid_with_spaces"    => [ " 12.345.678 ", true ],
            "invalid_too_short"    => [ "123", false ],
            "invalid_too_long"     => [ "123456789012", false ],
            "invalid_non_digits"   => [ "abcdefg", false ],
            "invalid_empty"        => [ "", false ],
        ];
    }


    /** @dataProvider providerDniToNumber */
    public function testDniToNumber(string $dni, string $expected) {
        $this->assertEquals($expected, Utils::dniToNumber($dni));
    }

    public static function providerDniToNumber() {
        return [
            "formatted_dots"          => [ "12.345.678", "12345678" ],
            "formatted_dots_spaces"   => [ " 12.345.678 ", "12345678" ],
            "plain_digits"            => [ "12345678", "12345678" ],
            "leading_zeros_formatted" => [ "00.123.456", "00123456" ],
            "invalid_letters"         => [ "abc", "" ],
            "invalid_empty"           => [ "", "" ],
        ];
    }


    /** @dataProvider providerGetAvatarUrl */
    public function testGetAvatarUrl(string $custom, string $email, string $expected) {
        $this->assertEquals($expected, Utils::getAvatarUrl($custom, $email));
    }

    public static function providerGetAvatarUrl() {
        return [
            "with_email"        => [ "", "me@example.com", "https://gravatar.com/avatar/" . md5("me@example.com") . "?default=mp" ],
            "with_custom"       => [ "custom", "me@example.com", "custom" ],
            "fallback_no_email" => [ "", "", "https://gravatar.com/avatar/" . md5("") . "?default=mp" ],
        ];
    }


    /** @dataProvider providerGetWhatsAppUrl */
    public function testGetWhatsAppUrl(string $phone, string $expected) {
        $this->assertEquals($expected, Utils::getWhatsAppUrl($phone));
    }

    public static function providerGetWhatsAppUrl() {
        return [
            "simple_number"  => [ "12345", "https://wa.me/12345" ],
            "with_plus_sign" => [ "+5412345", "https://wa.me/+5412345" ],
        ];
    }
}
