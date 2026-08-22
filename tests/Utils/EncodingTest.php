<?php
namespace Tests\Utils;

use Framework\Utils\Encoding;

use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EncodingTest extends TestCase {
    use TestHelpers;


    #[DataProvider("providerToUTF8")]
    public function testToUTF8(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::toUTF8($input));
    }

    public static function providerToUTF8(): array {
        return [
            "latin1_single_byte_to_utf8" => [ "\xE9", "é" ],
            "win1252_0x80_to_euro"       => [ "\x80", "€" ],
            "utf8_unchanged"             => [ "abc", "abc" ],

            // A valid sequence of every width is left alone
            "two_bytes_unchanged"        => [ "café", "café" ],
            "three_bytes_unchanged"      => [ "a\u{20AC}b", "a\u{20AC}b" ],
            "four_bytes_unchanged"       => [ "a\u{1F600}b", "a\u{1F600}b" ],

            // A lead byte with no continuation is read as latin1
            "broken_two_byte_lead"       => [ "\xC9a", "Éa" ],
            "broken_three_byte_lead"     => [ "\xE9a", "éa" ],
            "broken_four_byte_lead"      => [ "\xF1a", "ña" ],
            "impossible_lead"            => [ "\xF9a", "ùa" ],

            // A stray byte the win1252 table does not name
            "unnamed_win1252_byte"       => [ "\x81", "\xC2\x81" ],
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
            "already_correct_text_unchanged" => [ "hello", "hello" ],
        ];
    }


    #[DataProvider("providerUTF8FixWin1252Chars")]
    public function testUTF8FixWin1252Chars(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::UTF8FixWin1252Chars($input));
    }

    public static function providerUTF8FixWin1252Chars(): array {
        return [
            "broken_utf8_to_proper_utf8" => [ "\xC2\x80", "€" ],
        ];
    }


    #[DataProvider("providerRemoveBOM")]
    public function testRemoveBOM(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::removeBOM($input));
    }

    public static function providerRemoveBOM(): array {
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        return [
            "removes_bom_from_string"    => [ $bom . "abc", "abc" ],
            "empty_string_returns_empty" => [ "", "" ],
        ];
    }


    #[DataProvider("providerDecodeUTF8")]
    public function testDecodeUTF8(string $input, string $expected): void {
        $this->assertSame($expected, Encoding::decodeUTF8($input));
    }

    public static function providerDecodeUTF8(): array {
        return [
            "latin1_single_byte_to_utf8" => [ "\xE9", "?" ],
            "utf8_unchanged"             => [ "abc", "abc" ],
        ];
    }


    /**
     * One case per public method of the class, so a new one is not left untested
     * @param string $method
     * @return void
     */
    #[DataProvider("providerPublicMethods")]
    public function testEveryMethodIsTested(string $method): void {
        $this->assertMethodIsTested($method);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function providerPublicMethods(): array {
        return self::publicMethodsOf(Encoding::class);
    }
}
