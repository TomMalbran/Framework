<?php
namespace Tests\Utils;

use Framework\Utils\Encoding;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EncodingTest extends TestCase {

    #[DataProvider("providerToUTF8")]
    public function testToUTF8(string $input, string $expected) {
        $this->assertSame($expected, Encoding::toUTF8($input));
    }

    public static function providerToUTF8() {
        return [
            "latin1_single_byte_to_utf8" => [ "\xE9", "é" ],
            "win1252_0x80_to_euro"       => [ "\x80", "€" ],
            "utf8_unchanged"             => [ "abc", "abc" ],
        ];
    }


    #[DataProvider("providerToWin1252AndAliases")]
    public function testToWin1252AndAliases(string $input, string $expected, string $method) {
        $this->assertSame($expected, Encoding::$method($input));
    }

    public static function providerToWin1252AndAliases() {
        return [
            "euro_to_win1252" => [ "€", "\x80", "toWin1252" ],
            "euro_to_iso8859" => [ "€", "\x80", "toISO8859" ],
            "euro_to_latin1"  => [ "€", "\x80", "toLatin1" ],
        ];
    }


    #[DataProvider("providerFixUTF8")]
    public function testFixUTF8(string $input, string $expected) {
        $this->assertSame($expected, Encoding::fixUTF8($input));
    }

    public static function providerFixUTF8() {
        return [
            "already_correct_text_unchanged" => [ "hello", "hello" ],
        ];
    }


    #[DataProvider("providerUTF8FixWin1252Chars")]
    public function testUTF8FixWin1252Chars(string $input, string $expected) {
        $this->assertSame($expected, Encoding::UTF8FixWin1252Chars($input));
    }

    public static function providerUTF8FixWin1252Chars() {
        return [
            "broken_utf8_to_proper_utf8" => [ "\xC2\x80", "€" ],
        ];
    }


    #[DataProvider("providerRemoveBOM")]
    public function testRemoveBOM(string $input, string $expected) {
        $this->assertSame($expected, Encoding::removeBOM($input));
    }

    public static function providerRemoveBOM() {
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        return [
            "removes_bom_from_string"    => [ $bom . "abc", "abc" ],
            "empty_string_returns_empty" => [ "", "" ],
        ];
    }


    #[DataProvider("providerDecodeUTF8")]
    public function testDecodeUTF8(string $input, string $expected) {
        $this->assertSame($expected, Encoding::decodeUTF8($input));
    }

    public static function providerDecodeUTF8() {
        return [
            "latin1_single_byte_to_utf8" => [ "\xE9", "?" ],
            "utf8_unchanged"             => [ "abc", "abc" ],
        ];
    }
}
