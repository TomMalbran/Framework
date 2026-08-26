<?php
namespace Tests\Utils;

use Framework\Utils\Encoding;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EncodingTest extends TestCase {

    #[DataProvider("providerToUTF8")]
    public function testToUTF8(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::toUTF8($input));
    }

    public static function providerToUTF8(): array {
        return [
            "latin1 single byte to utf8" => [ "\xE9", "é" ],
            "win1252 0x80 to euro"       => [ "\x80", "€" ],
            "utf8 unchanged"             => [ "abc", "abc" ],

            // A valid sequence of every width is left alone
            "two bytes unchanged"        => [ "café", "café" ],
            "three bytes unchanged"      => [ "a\u{20AC}b", "a\u{20AC}b" ],
            "four bytes unchanged"       => [ "a\u{1F600}b", "a\u{1F600}b" ],

            // A lead byte with no continuation is read as latin1
            "broken two byte lead"       => [ "\xC9a", "Éa" ],
            "broken three byte lead"     => [ "\xE9a", "éa" ],
            "broken four byte lead"      => [ "\xF1a", "ña" ],
            "impossible lead"            => [ "\xF9a", "ùa" ],

            // A stray byte the win1252 table does not name
            "unnamed win1252 byte"       => [ "\x81", "\xC2\x81" ],
        ];
    }


    public function testToWin1252AndAliases(): void {
        // The three names are one conversion, so what one of them does the others do
        $this->assertSame("\x80", Encoding::toWin1252("€"));
        $this->assertSame("\x80", Encoding::toISO8859("€"));
        $this->assertSame("\x80", Encoding::toLatin1("€"));
    }


    #[DataProvider("providerFixUTF8")]
    public function testFixUTF8(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::fixUTF8($input));
    }

    public static function providerFixUTF8(): array {
        return [
            "already correct text unchanged" => [ "hello", "hello" ],
        ];
    }


    #[DataProvider("providerUTF8FixWin1252Chars")]
    public function testUTF8FixWin1252Chars(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::UTF8FixWin1252Chars($input));
    }

    public static function providerUTF8FixWin1252Chars(): array {
        return [
            "broken utf8 to proper utf8" => [ "\xC2\x80", "€" ],
        ];
    }


    #[DataProvider("providerRemoveBOM")]
    public function testRemoveBOM(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::removeBOM($input));
    }

    public static function providerRemoveBOM(): array {
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        return [
            "removes bom from string"    => [ $bom . "abc", "abc" ],
            "empty string returns empty" => [ "", "" ],
        ];
    }


    #[DataProvider("providerDecodeUTF8")]
    public function testDecodeUTF8(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::decodeUTF8($input));
    }

    public static function providerDecodeUTF8(): array {
        return [
            "latin1 single byte to utf8" => [ "\xE9", "?" ],
            "utf8 unchanged"             => [ "abc", "abc" ],
        ];
    }
}
